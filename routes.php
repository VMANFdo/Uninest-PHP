<?php

/**
 * Routes
 * 
 * Central route definitions for the entire application.
 * Each route maps to a controller function.
 */

// ──────────────────────────────────────
// Public Routes
// ──────────────────────────────────────

route('GET', '/', 'home_index');

// ──────────────────────────────────────
// Auth Routes (guests only)
// ──────────────────────────────────────

route('GET',  '/login',    'auth_login',         ['middleware_guest']);
route('POST', '/login',    'auth_login_post',     ['middleware_guest']);
route('GET',  '/register', 'auth_register',       ['middleware_guest']);
route('POST', '/register', 'auth_register_post',  ['middleware_guest']);
route('GET',  '/logout',   'auth_logout',         ['middleware_auth']);

// ──────────────────────────────────────
// Dashboard (authenticated)
// ──────────────────────────────────────

route('GET', '/dashboard', 'dashboard_index', ['middleware_auth']);

// ──────────────────────────────────────
// Subjects — Student view (authenticated)
// ──────────────────────────────────────

route('GET', '/dashboard/subjects', 'subjects_student_list', ['middleware_auth']);

// ──────────────────────────────────────
// Subjects — Moderator CRUD
// ──────────────────────────────────────

route('GET',  '/subjects',              'subjects_index',         ['middleware_auth', fn() => middleware_role('moderator')]);
route('GET',  '/subjects/create',       'subjects_create_form',   ['middleware_auth', fn() => middleware_role('moderator')]);
route('POST', '/subjects',              'subjects_store',         ['middleware_auth', fn() => middleware_role('moderator')]);
route('GET',  '/subjects/{id}/edit',    'subjects_edit_form',     ['middleware_auth', fn() => middleware_role('moderator')]);
route('POST', '/subjects/{id}',         'subjects_update_action', ['middleware_auth', fn() => middleware_role('moderator')]);
route('POST', '/subjects/{id}/delete',  'subjects_delete_action', ['middleware_auth', fn() => middleware_role('moderator')]);
