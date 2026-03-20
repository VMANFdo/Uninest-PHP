<?php

/**
 * Dashboard Module — Controllers
 * 
 * Routes to the correct dashboard view based on user role.
 */

function dashboard_index(): void
{
    $user = auth_user();
    $role = $user['role'];
    $data = ['user' => $user];

    // Load role-specific dashboard data
    switch ($role) {
        case 'admin':
            try {
                $data['user_count']    = db_count('users');
                $data['subject_count'] = db_count('subjects');
            } catch (\PDOException) {
                $data['user_count']    = 0;
                $data['subject_count'] = 0;
            }
            $viewName = 'admin';
            break;

        case 'moderator':
            try {
                $data['subjects']      = db_fetch_all('SELECT * FROM subjects ORDER BY created_at DESC LIMIT 10');
                $data['subject_count'] = db_count('subjects');
            } catch (\PDOException) {
                $data['subjects']      = [];
                $data['subject_count'] = 0;
            }
            $viewName = 'moderator';
            break;

        case 'coordinator':
            try {
                $data['subjects'] = db_fetch_all('SELECT * FROM subjects ORDER BY name ASC');
            } catch (\PDOException) {
                $data['subjects'] = [];
            }
            $viewName = 'coordinator';
            break;

        default: // student
            try {
                $data['subjects'] = db_fetch_all('SELECT * FROM subjects ORDER BY name ASC');
            } catch (\PDOException) {
                $data['subjects'] = [];
            }
            $viewName = 'student';
            break;
    }

    view('dashboard::' . $viewName, $data, 'dashboard');
}
