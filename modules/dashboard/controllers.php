<?php

/**
 * Dashboard Module — Controllers
 * 
 * Routes to the correct dashboard view based on user role.
 */

function dashboard_index(): void
{
    $user = auth_user();
    $role = $user['role'] ?? 'student';
    $data = ['user' => $user];

    // Load role-specific dashboard data
    switch ($role) {
        case 'admin':
            try {
                $data['user_count']    = db_count('users');
                $data['subject_count'] = db_count('subjects');
                $onboardingCounts = onboarding_admin_counts();
                $data['pending_batch_requests'] = $onboardingCounts['pending_batch_requests'];
                $data['pending_student_requests'] = $onboardingCounts['pending_student_requests'];
            } catch (\PDOException) {
                $data['user_count']    = 0;
                $data['subject_count'] = 0;
                $data['pending_batch_requests'] = 0;
                $data['pending_student_requests'] = 0;
            }
            $viewName = 'admin';
            break;

        case 'moderator':
            try {
                $batchId = (int) ($user['batch_id'] ?? 0);
                $data['subjects']      = db_fetch_all('SELECT * FROM subjects WHERE batch_id = ? ORDER BY created_at DESC LIMIT 10', [$batchId]);
                $data['subject_count'] = (int) db_fetch('SELECT COUNT(*) AS cnt FROM subjects WHERE batch_id = ?', [$batchId])['cnt'];
                $data['pending_student_requests'] = onboarding_moderator_pending_student_request_count((int) $user['id']);
            } catch (\PDOException) {
                $data['subjects']      = [];
                $data['subject_count'] = 0;
                $data['pending_student_requests'] = 0;
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
                $batchId = (int) ($user['batch_id'] ?? 0);
                $data['subjects'] = db_fetch_all('SELECT * FROM subjects WHERE batch_id = ? ORDER BY name ASC', [$batchId]);
            } catch (\PDOException) {
                $data['subjects'] = [];
            }
            $viewName = 'student';
            break;
    }

    view('dashboard::' . $viewName, $data, 'dashboard');
}
