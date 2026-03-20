<?php

/**
 * Auth Module — Controllers
 */

function auth_login(): void
{
    view('auth::login', [], 'main');
}

function auth_login_post(): void
{
    csrf_check();

    $email    = trim(request_input('email', ''));
    $password = request_input('password', '');

    if (empty($email) || empty($password)) {
        flash('error', 'Please fill in all fields.');
        flash_old_input();
        redirect('/login');
    }

    try {
        $user = auth_find_by_email($email);
    } catch (\PDOException) {
        flash('error', 'Database error. Please ensure the database is set up. Run database/schema.sql.');
        flash_old_input();
        redirect('/login');
    }

    if (!$user || !password_verify($password, $user['password'])) {
        flash('error', 'Invalid email or password.');
        flash_old_input();
        redirect('/login');
    }

    unset($user['password']);
    $_SESSION['user'] = $user;
    clear_old_input();

    flash('success', 'Welcome back, ' . e($user['name']) . '!');
    if (onboarding_complete_for_user($user)) {
        redirect('/dashboard');
    }

    redirect('/onboarding');
}

function auth_register(): void
{
    $universities = [];
    try {
        $universities = universities_active();
    } catch (\PDOException) {
        // Ignore and render with empty list; submit handler will return DB error.
    }

    view('auth::register', ['universities' => $universities], 'main');
}

function auth_register_post(): void
{
    csrf_check();

    $name        = trim(request_input('name', ''));
    $email       = trim(request_input('email', ''));
    $password    = request_input('password', '');
    $confirm     = request_input('password_confirmation', '');
    $role        = trim(request_input('role', 'student'));
    $academic    = (int) request_input('academic_year', 0);
    $university  = (int) request_input('university_id', 0);

    $batchName   = trim(request_input('batch_name', ''));
    $program     = trim(request_input('program', ''));
    $intakeYear  = (int) request_input('intake_year', 0);
    $batchCode   = strtoupper(trim(request_input('batch_code', '')));

    $errors = [];
    if ($name === '') $errors[] = 'Name is required.';
    if ($email === '') $errors[] = 'Email is required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email format.';
    if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
    if ($password !== $confirm) $errors[] = 'Passwords do not match.';
    if (!in_array($role, ['student', 'moderator'], true)) $errors[] = 'Invalid role selected.';
    if ($academic < 1 || $academic > 8) $errors[] = 'Academic year must be between 1 and 8.';

    $studentBatch = null;
    try {
        if ($university <= 0 || !university_is_active($university)) {
            $errors[] = 'Please select a valid university.';
        }

        if ($role === 'moderator') {
            if ($batchName === '') $errors[] = 'Batch name is required for moderators.';
            if ($program === '') $errors[] = 'Program is required for moderators.';
            if ($intakeYear < 2000 || $intakeYear > 2100) $errors[] = 'Intake year is invalid.';
        } elseif ($role === 'student') {
            if ($batchCode === '') {
                $errors[] = 'Active batch ID is required for students.';
            } else {
                $studentBatch = onboarding_find_batch_by_code($batchCode);
                if (!$studentBatch) {
                    $errors[] = 'Invalid or inactive batch ID.';
                } elseif ((int) $studentBatch['university_id'] !== $university) {
                    $errors[] = 'Selected university does not match the batch.';
                }
            }
        }
    } catch (\Throwable) {
        flash('error', 'Database error. Please ensure the database is set up. Run database/schema.sql.');
        flash_old_input();
        redirect('/register');
    }

    if (!empty($errors)) {
        flash('error', implode(' ', $errors));
        flash_old_input();
        redirect('/register');
    }

    $pdo = db_connect();
    $pdo->beginTransaction();

    try {
        if (auth_find_by_email($email)) {
            flash('error', 'An account with this email already exists.');
            flash_old_input();
            $pdo->rollBack();
            redirect('/register');
        }

        $userId = (int) auth_create_user([
            'name'          => $name,
            'email'         => $email,
            'password'      => $password,
            'role'          => $role,
            'academic_year' => $academic,
            'university_id' => $university,
        ]);

        if ($role === 'moderator') {
            onboarding_create_moderator_batch_request([
                'name'              => $batchName,
                'program'           => $program,
                'intake_year'       => $intakeYear,
                'university_id'     => $university,
                'moderator_user_id' => $userId,
            ]);
        } else {
            onboarding_create_student_request($userId, (int) $studentBatch['id']);
        }

        $pdo->commit();

        auth_set_session_user_by_id($userId);
        clear_old_input();

        if ($role === 'moderator') {
            flash('success', 'Account created. Your batch request is pending admin approval.');
        } else {
            flash('success', 'Account created. Your join request is pending moderator approval.');
        }

        redirect('/onboarding');
    } catch (\Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        flash('error', 'Database error. Please ensure the database is set up. Run database/schema.sql.');
        flash_old_input();
        redirect('/register');
    }
}

function auth_logout(): void
{
    session_destroy();
    session_start();
    flash('success', 'You have been logged out.');
    redirect('/login');
}
