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

    // Keep session user in sync with DB updates (approval state, role changes, etc.).
    auth_refresh_session_user();
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

/**
 * Require an exact role (no hierarchy fallback).
 */
function middleware_exact_role(string $role): void
{
    if (user_role() !== $role) {
        abort(403, 'You do not have permission to access this page.');
    }
}

/**
 * Require onboarding completion for student/moderator users.
 * Admin and other roles are not blocked.
 */
function middleware_onboarding_complete(): void
{
    $user = auth_user();
    if (!$user) {
        flash('error', 'Please log in to continue.');
        redirect('/login');
    }

    if (!onboarding_complete_for_user($user)) {
        flash('warning', 'Complete onboarding to access this section.');
        redirect('/onboarding');
    }
}
