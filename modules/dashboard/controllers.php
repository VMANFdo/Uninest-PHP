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
            $data = array_merge($data, dashboard_admin_build_dashboard_data($user));
            $viewName = 'admin';
            break;

        case 'moderator':
            try {
                $batchId = (int) ($user['batch_id'] ?? 0);
                $batchSubjects = $batchId > 0
                    ? db_fetch_all('SELECT * FROM subjects WHERE batch_id = ? ORDER BY name ASC', [$batchId])
                    : [];

                $data['batch_subjects'] = $batchSubjects;
                $data['recent_subjects'] = array_slice($batchSubjects, 0, 8);
                $data['subjects'] = $data['recent_subjects'];
                $data['subject_count'] = count($batchSubjects);
                $data['pending_student_requests'] = onboarding_moderator_pending_student_request_count((int) $user['id']);
                $data['pending_quiz_requests'] = quizzes_pending_count_for_reviewer((int) $user['id'], 'moderator', $batchId);
                $data['batch'] = onboarding_find_moderator_batch((int) $user['id']);
                $data['open_report_count'] = (int) db_fetch(
                    "SELECT COUNT(*) AS cnt
                     FROM feed_reports
                     WHERE batch_id = ?
                       AND status = 'open'",
                    [$batchId]
                )['cnt'];

                $data = array_merge($data, dashboard_student_build_dashboard_data($user, $batchSubjects));

                if (!empty($data['batch']['batch_code'])) {
                    $data['invite_link'] = base_url('register') . '?role=student&batch_code=' . urlencode($data['batch']['batch_code']);
                    $data['invite_qr_url'] = 'https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=' . rawurlencode($data['invite_link']);
                } else {
                    $data['invite_link'] = null;
                    $data['invite_qr_url'] = null;
                }
            } catch (\PDOException) {
                $data['subjects']      = [];
                $data['batch_subjects'] = [];
                $data['recent_subjects'] = [];
                $data['subject_count'] = 0;
                $data['pending_student_requests'] = 0;
                $data['pending_quiz_requests'] = 0;
                $data['open_report_count'] = 0;
                $data['batch'] = null;
                $data['invite_link'] = null;
                $data['invite_qr_url'] = null;
                $data = array_merge($data, dashboard_student_build_dashboard_data($user, []));
            }
            $viewName = 'moderator';
            break;

        case 'coordinator':
            try {
                $batchId = (int) ($user['batch_id'] ?? 0);
                $data['subjects'] = subjects_all_for_coordinator((int) $user['id']);
                $data['assigned_subjects'] = $data['subjects'];
                $data['batch_subjects'] = $batchId > 0
                    ? db_fetch_all('SELECT * FROM subjects WHERE batch_id = ? ORDER BY name ASC', [$batchId])
                    : [];
                $data['pending_resource_requests'] = resources_coordinator_pending_count((int) $user['id']);
                $data['pending_quiz_requests'] = quizzes_pending_count_for_reviewer((int) $user['id'], 'coordinator', $batchId);
                $data = array_merge($data, dashboard_student_build_dashboard_data($user, (array) $data['batch_subjects']));
            } catch (\PDOException) {
                $data['subjects'] = [];
                $data['assigned_subjects'] = [];
                $data['batch_subjects'] = [];
                $data['pending_resource_requests'] = 0;
                $data['pending_quiz_requests'] = 0;
                $data = array_merge($data, dashboard_student_build_dashboard_data($user, []));
            }
            $viewName = 'coordinator';
            break;

        default: // student
            try {
                $batchId = (int) ($user['batch_id'] ?? 0);
                $data['subjects'] = db_fetch_all('SELECT * FROM subjects WHERE batch_id = ? ORDER BY name ASC', [$batchId]);
                $data = array_merge($data, dashboard_student_build_dashboard_data($user, $data['subjects']));
            } catch (\PDOException) {
                $data['subjects'] = [];
                $data = array_merge($data, dashboard_student_build_dashboard_data($user, []));
            }
            $viewName = 'student';
            break;
    }

    view('dashboard::' . $viewName, $data, 'dashboard');
}

function dashboard_search_query_from_request(): string
{
    $query = trim((string) request_input('q', ''));
    if (strlen($query) > dashboard_search_query_max_length()) {
        $query = substr($query, 0, dashboard_search_query_max_length());
    }

    return $query;
}

function dashboard_search_type_from_request(): string
{
    $type = trim((string) request_input('type', 'all'));
    return array_key_exists($type, dashboard_search_type_options()) ? $type : 'all';
}

function dashboard_search_is_ajax_request(): bool
{
    if ((int) request_input('ajax', 0) === 1) {
        return true;
    }

    $xrw = strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? ''));
    if ($xrw === 'xmlhttprequest') {
        return true;
    }

    $accept = strtolower((string) ($_SERVER['HTTP_ACCEPT'] ?? ''));
    return str_contains($accept, 'application/json');
}

function dashboard_search_index(): void
{
    $role = (string) user_role();
    $isAdmin = $role === 'admin';
    $viewer = auth_user() ?? [];

    $batchOptions = $isAdmin ? dashboard_search_batch_options_for_admin() : [];
    $selectedBatchId = 0;
    $activeBatch = null;

    if ($isAdmin) {
        $selectedBatchId = (int) request_input('batch_id', 0);
        if ($selectedBatchId > 0) {
            $activeBatch = dashboard_search_find_batch_option_by_id($selectedBatchId);
            if (!$activeBatch) {
                $selectedBatchId = 0;
            }
        }
    } else {
        $selectedBatchId = (int) ($viewer['batch_id'] ?? 0);
        if ($selectedBatchId <= 0) {
            abort(403, 'You are not assigned to a batch.');
        }
        $activeBatch = dashboard_search_find_batch_option_by_id($selectedBatchId);
    }

    $query = dashboard_search_query_from_request();
    $selectedType = dashboard_search_type_from_request();
    $subjectOptions = $selectedBatchId > 0
        ? dashboard_search_subject_options_for_batch($selectedBatchId)
        : [];

    $selectedSubjectId = (int) request_input('subject_id', 0);
    if ($selectedSubjectId > 0 && !dashboard_search_subject_exists_in_batch($selectedSubjectId, $selectedBatchId)) {
        $selectedSubjectId = 0;
    }

    $hasSearchQuery = strlen($query) >= dashboard_search_min_query_length();
    $subjectFilter = $selectedSubjectId > 0 ? $selectedSubjectId : null;
    $limit = max(6, min(80, (int) request_input('limit', 24)));

    $rawItems = [];
    if ($selectedBatchId > 0 && $hasSearchQuery) {
        $rawItems = dashboard_search_fetch_items(
            $selectedBatchId,
            $query,
            $selectedType,
            $subjectFilter,
            $limit
        );
    }

    $items = [];
    foreach ($rawItems as $item) {
        $items[] = dashboard_search_present_item($item, $isAdmin, $selectedBatchId);
    }

    $counts = dashboard_search_counts_by_type($items);

    if (dashboard_search_is_ajax_request()) {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'query' => $query,
            'requires_batch' => $isAdmin && $selectedBatchId <= 0,
            'selected_batch_id' => $selectedBatchId,
            'items' => $items,
            'counts' => $counts,
            'min_query_length' => dashboard_search_min_query_length(),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    view('dashboard::search', [
        'is_admin' => $isAdmin,
        'batch_options' => $batchOptions,
        'selected_batch_id' => $selectedBatchId,
        'active_batch' => $activeBatch,
        'query' => $query,
        'selected_type' => $selectedType,
        'selected_subject_id' => $selectedSubjectId,
        'subject_options' => $subjectOptions,
        'type_options' => dashboard_search_type_options(),
        'items' => $items,
        'counts' => $counts,
        'min_query_length' => dashboard_search_min_query_length(),
        'has_search_query' => $hasSearchQuery,
    ], 'dashboard');
}

function dashboard_student_build_dashboard_data(array $user, array $subjects): array
{
    $userId = (int) ($user['id'] ?? 0);
    $batchId = (int) ($user['batch_id'] ?? 0);

    $defaults = [
        'batch_meta' => null,
        'subject_count' => count($subjects),
        'resource_count' => 0,
        'quiz_count' => 0,
        'community_count' => 0,
        'open_kuppi_count' => 0,
        'upcoming_session_count' => 0,
        'my_post_count' => 0,
        'my_resource_count' => 0,
        'my_quiz_count' => 0,
        'my_kuppi_request_count' => 0,
        'today_blocked_count' => 0,
        'today_blocked_minutes' => 0,
        'next_session' => null,
        'recent_activity_items' => [],
        'quiz_summary' => [
            'attempt_count' => 0,
            'avg_score' => null,
            'best_score' => null,
            'total_correct' => 0,
            'total_questions' => 0,
        ],
        'gpa_summary' => [
            'record_count' => 0,
            'best_gpa' => null,
            'average_gpa' => null,
            'total_credits' => 0.0,
            'total_subjects' => 0,
            'latest_gpa' => null,
            'latest_academic_year' => null,
            'latest_semester' => null,
            'latest_updated_at' => null,
        ],
    ];

    if ($userId <= 0 || $batchId <= 0) {
        return $defaults;
    }

    try {
        $batchMeta = db_fetch(
            "SELECT b.id, b.batch_code, b.name, b.program, b.intake_year,
                    u.name AS university_name
             FROM batches b
             LEFT JOIN universities u ON u.id = b.university_id
             WHERE b.id = ?
             LIMIT 1",
            [$batchId]
        );

        $counts = db_fetch(
            "SELECT
                (SELECT COUNT(*)
                 FROM resources r
                 INNER JOIN topics t ON t.id = r.topic_id
                 INNER JOIN subjects s ON s.id = t.subject_id
                 WHERE r.status = 'published'
                   AND s.batch_id = ?) AS resource_count,
                (SELECT COUNT(*)
                 FROM quizzes q
                 INNER JOIN subjects s ON s.id = q.subject_id
                 WHERE q.status = 'approved'
                   AND s.batch_id = ?) AS quiz_count,
                (SELECT COUNT(*)
                 FROM feed_posts fp
                 WHERE fp.batch_id = ?) AS community_count,
                (SELECT COUNT(*)
                 FROM kuppi_requests kr
                 WHERE kr.batch_id = ?
                   AND kr.status = 'open') AS open_kuppi_count,
                (SELECT COUNT(*)
                 FROM kuppi_scheduled_sessions ks
                 WHERE ks.batch_id = ?
                   AND ks.status = 'scheduled'
                   AND ks.session_date >= CURDATE()) AS upcoming_session_count",
            [$batchId, $batchId, $batchId, $batchId, $batchId]
        ) ?? [];

        $myCounts = db_fetch(
            "SELECT
                (SELECT COUNT(*) FROM feed_posts WHERE author_user_id = ?) AS my_post_count,
                (SELECT COUNT(*) FROM resources WHERE uploaded_by_user_id = ?) AS my_resource_count,
                (SELECT COUNT(*) FROM kuppi_requests WHERE requested_by_user_id = ?) AS my_kuppi_request_count",
            [$userId, $userId, $userId]
        ) ?? [];

        $nextSession = db_fetch(
            "SELECT ks.id,
                    ks.title,
                    ks.session_date,
                    ks.start_time,
                    ks.end_time,
                    ks.location_type,
                    ks.location_text,
                    ks.meeting_link,
                    s.id AS subject_id,
                    s.code AS subject_code,
                    s.name AS subject_name,
                    (SELECT COUNT(*)
                     FROM kuppi_scheduled_session_hosts sh
                     WHERE sh.session_id = ks.id) AS host_count
             FROM kuppi_scheduled_sessions ks
             INNER JOIN subjects s ON s.id = ks.subject_id
             WHERE ks.batch_id = ?
               AND ks.status = 'scheduled'
               AND ks.session_date >= CURDATE()
             ORDER BY ks.session_date ASC, ks.start_time ASC, ks.id ASC
             LIMIT 1",
            [$batchId]
        );

        $todayDow = (int) date('N');
        $todayBlocked = db_fetch(
            "SELECT COUNT(*) AS blocked_count,
                    COALESCE(SUM(TIMESTAMPDIFF(MINUTE, start_time, end_time)), 0) AS blocked_minutes
             FROM kuppi_university_timetable_slots
             WHERE batch_id = ?
               AND day_of_week = ?",
            [$batchId, $todayDow]
        ) ?? [];

        $recentActivity = [];
        if (function_exists('feed_fetch_page')) {
            $feedPage = feed_fetch_page($batchId, $userId, 'all', null, '', 1, 5);
            $recentActivity = (array) ($feedPage['items'] ?? []);
            if (!empty($recentActivity) && function_exists('feed_present_items')) {
                $recentActivity = feed_present_items($recentActivity, false, $batchId, $userId);
            }
        }

        $quizSummary = $defaults['quiz_summary'];
        if (function_exists('quizzes_student_analytics_summary')) {
            $quizSummary = quizzes_student_analytics_summary($userId);
        }

        $gpaSummary = $defaults['gpa_summary'];
        if (function_exists('gpa_summary_for_user')) {
            $gpaSummary = gpa_summary_for_user($userId, $batchId);
        }

        $myQuizCount = 0;
        if (function_exists('quizzes_count_created_by_user')) {
            $myQuizCount = quizzes_count_created_by_user($userId);
        } else {
            $row = db_fetch(
                'SELECT COUNT(*) AS cnt FROM quizzes WHERE created_by_user_id = ?',
                [$userId]
            );
            $myQuizCount = (int) ($row['cnt'] ?? 0);
        }

        return array_merge($defaults, [
            'batch_meta' => $batchMeta ?: null,
            'subject_count' => count($subjects),
            'resource_count' => (int) ($counts['resource_count'] ?? 0),
            'quiz_count' => (int) ($counts['quiz_count'] ?? 0),
            'community_count' => (int) ($counts['community_count'] ?? 0),
            'open_kuppi_count' => (int) ($counts['open_kuppi_count'] ?? 0),
            'upcoming_session_count' => (int) ($counts['upcoming_session_count'] ?? 0),
            'my_post_count' => (int) ($myCounts['my_post_count'] ?? 0),
            'my_resource_count' => (int) ($myCounts['my_resource_count'] ?? 0),
            'my_quiz_count' => (int) $myQuizCount,
            'my_kuppi_request_count' => (int) ($myCounts['my_kuppi_request_count'] ?? 0),
            'today_blocked_count' => (int) ($todayBlocked['blocked_count'] ?? 0),
            'today_blocked_minutes' => (int) ($todayBlocked['blocked_minutes'] ?? 0),
            'next_session' => $nextSession ?: null,
            'recent_activity_items' => $recentActivity,
            'quiz_summary' => $quizSummary,
            'gpa_summary' => $gpaSummary,
        ]);
    } catch (\PDOException) {
        return $defaults;
    }
}

function dashboard_admin_build_dashboard_data(array $user): array
{
    $adminUserId = (int) ($user['id'] ?? 0);
    $defaults = [
        'user_count' => 0,
        'student_count' => 0,
        'coordinator_count' => 0,
        'moderator_count' => 0,
        'batch_count' => 0,
        'subject_count' => 0,
        'resource_count' => 0,
        'quiz_count' => 0,
        'open_kuppi_count' => 0,
        'upcoming_session_count' => 0,
        'announcement_count' => 0,
        'pending_batch_requests' => 0,
        'pending_student_requests' => 0,
        'pending_quiz_requests' => 0,
        'open_report_count' => 0,
        'pending_resource_requests' => 0,
        'latest_pending_batches' => [],
        'latest_pending_students' => [],
        'latest_pending_quizzes' => [],
        'latest_open_reports' => [],
    ];

    try {
        $onboardingCounts = onboarding_admin_counts();
        $counts = db_fetch(
            "SELECT
                (SELECT COUNT(*) FROM users) AS user_count,
                (SELECT COUNT(*) FROM users WHERE role = 'student') AS student_count,
                (SELECT COUNT(*) FROM users WHERE role = 'coordinator') AS coordinator_count,
                (SELECT COUNT(*) FROM users WHERE role = 'moderator') AS moderator_count,
                (SELECT COUNT(*) FROM batches WHERE status = 'approved') AS batch_count,
                (SELECT COUNT(*) FROM subjects) AS subject_count,
                (SELECT COUNT(*) FROM resources WHERE status = 'published') AS resource_count,
                (SELECT COUNT(*) FROM quizzes WHERE status = 'approved') AS quiz_count,
                (SELECT COUNT(*) FROM kuppi_requests WHERE status = 'open') AS open_kuppi_count,
                (SELECT COUNT(*) FROM kuppi_scheduled_sessions WHERE status = 'scheduled' AND session_date >= CURDATE()) AS upcoming_session_count,
                (SELECT COUNT(*) FROM announcements) AS announcement_count,
                (SELECT COUNT(*) FROM feed_reports WHERE status = 'open') AS open_report_count,
                (
                    (SELECT COUNT(*) FROM resources WHERE status = 'pending')
                    + (SELECT COUNT(*) FROM resource_update_requests WHERE status = 'pending')
                ) AS pending_resource_requests",
            []
        ) ?? [];

        $latestPendingBatches = db_fetch_all(
            "SELECT b.id,
                    b.name,
                    b.batch_code,
                    b.program,
                    b.intake_year,
                    b.created_at,
                    u.name AS moderator_name
             FROM batches b
             LEFT JOIN users u ON u.id = b.moderator_user_id
             WHERE b.status = 'pending'
             ORDER BY b.created_at DESC, b.id DESC
             LIMIT 5"
        );

        $latestPendingStudents = db_fetch_all(
            "SELECT r.id,
                    r.created_at,
                    s.id AS student_id,
                    s.name AS student_name,
                    s.email AS student_email,
                    b.id AS batch_id,
                    b.batch_code,
                    b.name AS batch_name
             FROM student_batch_requests r
             INNER JOIN users s ON s.id = r.student_user_id
             INNER JOIN batches b ON b.id = r.requested_batch_id
             WHERE r.status = 'pending'
             ORDER BY r.created_at DESC, r.id DESC
             LIMIT 5"
        );

        $latestPendingQuizzes = db_fetch_all(
            "SELECT q.id,
                    q.title,
                    q.mode,
                    q.created_at,
                    s.id AS subject_id,
                    s.code AS subject_code,
                    s.name AS subject_name,
                    b.id AS batch_id,
                    b.batch_code,
                    u.name AS creator_name
             FROM quizzes q
             INNER JOIN subjects s ON s.id = q.subject_id
             INNER JOIN batches b ON b.id = s.batch_id
             LEFT JOIN users u ON u.id = q.created_by_user_id
             WHERE q.status = 'pending'
             ORDER BY q.created_at DESC, q.id DESC
             LIMIT 5"
        );

        $latestOpenReports = db_fetch_all(
            "SELECT r.id,
                    r.target_type,
                    r.reason,
                    r.created_at,
                    b.id AS batch_id,
                    b.batch_code,
                    reporter.name AS reporter_name
             FROM feed_reports r
             LEFT JOIN batches b ON b.id = r.batch_id
             LEFT JOIN users reporter ON reporter.id = r.reporter_user_id
             WHERE r.status = 'open'
             ORDER BY r.created_at DESC, r.id DESC
             LIMIT 5"
        );

        return array_merge($defaults, [
            'user_count' => (int) ($counts['user_count'] ?? 0),
            'student_count' => (int) ($counts['student_count'] ?? 0),
            'coordinator_count' => (int) ($counts['coordinator_count'] ?? 0),
            'moderator_count' => (int) ($counts['moderator_count'] ?? 0),
            'batch_count' => (int) ($counts['batch_count'] ?? 0),
            'subject_count' => (int) ($counts['subject_count'] ?? 0),
            'resource_count' => (int) ($counts['resource_count'] ?? 0),
            'quiz_count' => (int) ($counts['quiz_count'] ?? 0),
            'open_kuppi_count' => (int) ($counts['open_kuppi_count'] ?? 0),
            'upcoming_session_count' => (int) ($counts['upcoming_session_count'] ?? 0),
            'announcement_count' => (int) ($counts['announcement_count'] ?? 0),
            'pending_batch_requests' => (int) ($onboardingCounts['pending_batch_requests'] ?? 0),
            'pending_student_requests' => (int) ($onboardingCounts['pending_student_requests'] ?? 0),
            'pending_quiz_requests' => $adminUserId > 0 ? quizzes_pending_count_for_reviewer($adminUserId, 'admin', 0) : 0,
            'open_report_count' => (int) ($counts['open_report_count'] ?? 0),
            'pending_resource_requests' => (int) ($counts['pending_resource_requests'] ?? 0),
            'latest_pending_batches' => $latestPendingBatches,
            'latest_pending_students' => $latestPendingStudents,
            'latest_pending_quizzes' => $latestPendingQuizzes,
            'latest_open_reports' => $latestOpenReports,
        ]);
    } catch (\PDOException) {
        return $defaults;
    }
}
