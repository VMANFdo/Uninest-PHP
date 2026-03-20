<?php

/**
 * Middleware Functions
 * 
 * Route guards for authentication and role-based access control.
 */

/**
 * Require authentication. Redirects to /login if not logged in.
 */
function middleware_auth(): void
{
    if (!auth_check()) {
        flash('error', 'Please log in to continue.');
        redirect('/login');
    }
}

/**
 * Require guest (not logged in). Redirects to /dashboard if already authenticated.
 */
function middleware_guest(): void
{
    if (auth_check()) {
        redirect('/dashboard');
    }
}

/**
 * Require a specific role. Supports role hierarchy:
 *   admin > moderator > coordinator > student
 * 
 * Usage in routes:
 *   route('GET', '/subjects', 'subjects_index', ['middleware_auth', fn() => middleware_role('moderator')]);
 */
function middleware_role(string $role): void
{
    if (!is_role($role)) {
        abort(403, 'You do not have permission to access this page.');
    }
}
