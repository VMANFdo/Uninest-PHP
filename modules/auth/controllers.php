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

function auth_forgot_password(): void
{
    view('auth::forgot_password', [], 'main');
}

function auth_forgot_password_post(): void
{
    csrf_check();

    $email = trim(request_input('email', ''));
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        flash('error', 'Please enter a valid email address.');
        flash_old_input();
        redirect('/forgot-password');
    }

    try {
        $user = auth_find_by_email($email);

        if ($user) {
            $token = auth_create_password_reset_token((int) $user['id'], $user['email']);
            $resetLink = base_url('reset-password') . '?email=' . urlencode($user['email']) . '&token=' . urlencode($token);

            $subject = config('app.name') . ' Password Reset';
            $textBody = implode("\n", [
                'You requested a password reset.',
                '',
                'Open this link to reset your password:',
                $resetLink,
                '',
                'This link will expire in 60 minutes.',
                '',
                'If you did not request this, you can ignore this email.',
            ]);

            $sent = smtp_send_email($user['email'], $subject, $textBody);
            if (!$sent) {
                error_log('Password reset email failed for: ' . $user['email']);
            }
        }
    } catch (\Throwable) {
        flash('error', 'Unable to process your request right now. Please try again.');
        flash_old_input();
        redirect('/forgot-password');
    }

    clear_old_input();
    flash('success', 'If your email exists in our system, a password reset link has been sent.');
    redirect('/forgot-password');
}

function auth_reset_password(): void
{
    $email = trim((string) request_input('email', ''));
    $token = trim((string) request_input('token', ''));

    $isValid = false;
    if ($email !== '' && $token !== '') {
        try {
            $isValid = (bool) auth_find_valid_password_reset($email, $token);
        } catch (\Throwable) {
            $isValid = false;
        }
    }

    view('auth::reset_password', [
        'email'    => $email,
        'token'    => $token,
        'is_valid' => $isValid,
    ], 'main');
}

function auth_reset_password_post(): void
{
    csrf_check();

    $email    = trim(request_input('email', ''));
    $token    = trim(request_input('token', ''));
    $password = request_input('password', '');
    $confirm  = request_input('password_confirmation', '');

    $errors = [];
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email.';
    if ($token === '') $errors[] = 'Reset token is missing.';
    if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
    if ($password !== $confirm) $errors[] = 'Passwords do not match.';

    if (!empty($errors)) {
        flash('error', implode(' ', $errors));
        flash_old_input();
        redirect('/reset-password?email=' . urlencode($email) . '&token=' . urlencode($token));
    }

    try {
        $reset = auth_find_valid_password_reset($email, $token);
        if (!$reset) {
            flash('error', 'This reset link is invalid or expired. Request a new one.');
            redirect('/forgot-password');
        }

        db_update('users', [
            'password' => password_hash($password, PASSWORD_DEFAULT),
        ], ['id' => (int) $reset['resolved_user_id']]);

        auth_mark_password_reset_used((int) $reset['id']);
        auth_mark_all_password_resets_used_for_user((int) $reset['resolved_user_id']);

        clear_old_input();
        flash('success', 'Password reset successful. You can now sign in.');
        redirect('/login');
    } catch (\Throwable) {
        flash('error', 'Unable to reset password right now. Please try again.');
        flash_old_input();
        redirect('/reset-password?email=' . urlencode($email) . '&token=' . urlencode($token));
    }
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
