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
    } catch (\PDOException $e) {
        flash('error', 'Database error. Please ensure the database is set up. Run database/schema.sql.');
        flash_old_input();
        redirect('/login');
    }

    if (!$user || !password_verify($password, $user['password'])) {
        flash('error', 'Invalid email or password.');
        flash_old_input();
        redirect('/login');
    }

    // Store user in session (without password)
    unset($user['password']);
    $_SESSION['user'] = $user;
    clear_old_input();

    flash('success', 'Welcome back, ' . e($user['name']) . '!');
    redirect('/dashboard');
}

function auth_register(): void
{
    view('auth::register', [], 'main');
}

function auth_register_post(): void
{
    csrf_check();

    $name     = trim(request_input('name', ''));
    $email    = trim(request_input('email', ''));
    $password = request_input('password', '');
    $confirm  = request_input('password_confirmation', '');

    // Validation
    $errors = [];
    if (empty($name))     $errors[] = 'Name is required.';
    if (empty($email))    $errors[] = 'Email is required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email format.';
    if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
    if ($password !== $confirm) $errors[] = 'Passwords do not match.';

    if (!empty($errors)) {
        flash('error', implode(' ', $errors));
        flash_old_input();
        redirect('/register');
    }

    try {
        // Check if email already exists
        if (auth_find_by_email($email)) {
            flash('error', 'An account with this email already exists.');
            flash_old_input();
            redirect('/register');
        }

        $userId = auth_create_user([
            'name'     => $name,
            'email'    => $email,
            'password' => $password,
        ]);

        // Auto-login after registration
        $user = db_fetch('SELECT id, name, email, role, created_at FROM users WHERE id = ?', [$userId]);
        $_SESSION['user'] = $user;
        clear_old_input();

        flash('success', 'Account created successfully!');
        redirect('/dashboard');
    } catch (\PDOException $e) {
        flash('error', 'Database error. Please ensure the database is set up. Run database/schema.sql.');
        flash_old_input();
        redirect('/register');
    }
}

function auth_logout(): void
{
    session_destroy();
    // Start a new session for flash messages
    session_start();
    flash('success', 'You have been logged out.');
    redirect('/login');
}
