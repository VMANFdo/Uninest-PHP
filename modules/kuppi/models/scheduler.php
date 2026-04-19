<?php

/**
 * Kuppi Module — Scheduling Query Models
 */

function kuppi_schedule_open_requests_for_scheduler(
    string $role,
    int $userId,
    int $userBatchId,
    string $searchQuery = '',
    string $sortBy = 'most_votes',
    int $adminBatchId = 0
): array {
    $where = ["kr.status = 'open'"];
    $params = [];

    if ($role === 'admin') {
        if ($adminBatchId > 0) {
            $where[] = 'kr.batch_id = ?';
            $params[] = $adminBatchId;
        }
    } else {
        if ($userBatchId <= 0) {
            return [];
        }

        $where[] = 'kr.batch_id = ?';
        $params[] = $userBatchId;
    }

    if ($role === 'coordinator') {
        $where[] = 'EXISTS (SELECT 1 FROM subject_coordinators sc WHERE sc.subject_id = kr.subject_id AND sc.student_user_id = ?)';
        $params[] = $userId;
    }

    $searchQuery = trim($searchQuery);
    if ($searchQuery !== '') {
        $needle = '%' . $searchQuery . '%';
        $where[] = '(kr.title LIKE ? OR kr.description LIKE ? OR s.code LIKE ? OR s.name LIKE ? OR u.name LIKE ?)';
        $params[] = $needle;
        $params[] = $needle;
        $params[] = $needle;
        $params[] = $needle;
        $params[] = $needle;
    }

    $orderBy = $sortBy === 'recent'
        ? 'kr.created_at DESC, kr.id DESC'
        : 'COALESCE(vv.score, 0) DESC, COALESCE(vv.upvotes, 0) DESC, kr.created_at DESC, kr.id DESC';

    $whereSql = implode(' AND ', $where);

    return db_fetch_all(
        "SELECT kr.id,
                kr.batch_id,
                kr.subject_id,
                kr.requested_by_user_id,
                kr.title,
                kr.description,
                kr.status,
                kr.created_at,
                s.code AS subject_code,
                s.name AS subject_name,
                b.batch_code,
                u.name AS requester_name,
                COALESCE(vv.upvotes, 0) AS upvote_count,
                COALESCE(vv.downvotes, 0) AS downvote_count,
                COALESCE(vv.score, 0) AS vote_score,
                COALESCE(ss.active_count, 0) AS active_session_count
         FROM kuppi_requests kr
         INNER JOIN subjects s ON s.id = kr.subject_id
         INNER JOIN batches b ON b.id = kr.batch_id
         LEFT JOIN users u ON u.id = kr.requested_by_user_id
         LEFT JOIN (
            SELECT request_id,
                   SUM(CASE WHEN vote_type = 'up' THEN 1 ELSE 0 END) AS upvotes,
                   SUM(CASE WHEN vote_type = 'down' THEN 1 ELSE 0 END) AS downvotes,
                   SUM(CASE WHEN vote_type = 'up' THEN 1 ELSE -1 END) AS score
            FROM kuppi_request_votes
            GROUP BY request_id
         ) vv ON vv.request_id = kr.id
         LEFT JOIN (
            SELECT request_id, COUNT(*) AS active_count
            FROM kuppi_scheduled_sessions
            WHERE status = 'scheduled'
            GROUP BY request_id
         ) ss ON ss.request_id = kr.id
         WHERE {$whereSql}
         ORDER BY {$orderBy}
         LIMIT 200",
        $params
    );
}

function kuppi_schedule_conductor_candidates_for_request(int $requestId): array
{
    if ($requestId <= 0) {
        return [];
    }

    return db_fetch_all(
        "SELECT a.id AS application_id,
                a.request_id,
                a.applicant_user_id AS host_user_id,
                a.motivation,
                a.availability_csv,
                a.created_at,
                u.name AS host_name,
                u.email AS host_email,
                u.role AS host_role,
                u.academic_year AS host_academic_year,
                COALESCE(cv.vote_count, 0) AS vote_count
         FROM kuppi_conductor_applications a
         INNER JOIN users u ON u.id = a.applicant_user_id
         LEFT JOIN (
            SELECT application_id, COUNT(*) AS vote_count
            FROM kuppi_conductor_votes
            GROUP BY application_id
         ) cv ON cv.application_id = a.id
         WHERE a.request_id = ?
         ORDER BY COALESCE(cv.vote_count, 0) DESC, a.created_at ASC, a.id ASC",
        [$requestId]
    );
}

function kuppi_schedule_manual_host_candidates_for_batch(int $batchId): array
{
    if ($batchId <= 0) {
        return [];
    }

    return db_fetch_all(
        "SELECT id AS host_user_id,
                name AS host_name,
                email AS host_email,
                role AS host_role,
                academic_year AS host_academic_year
         FROM users
         WHERE batch_id = ?
           AND role IN ('student', 'coordinator')
         ORDER BY name ASC, id ASC",
        [$batchId]
    );
}

function kuppi_scheduled_session_has_active_for_request(int $requestId, ?int $excludeSessionId = null): bool
{
    if ($requestId <= 0) {
        return false;
    }

    if ($excludeSessionId !== null && $excludeSessionId > 0) {
        return (bool) db_fetch(
            "SELECT id
             FROM kuppi_scheduled_sessions
             WHERE request_id = ?
               AND status = 'scheduled'
               AND id <> ?
             LIMIT 1",
            [$requestId, $excludeSessionId]
        );
    }

    return (bool) db_fetch(
        "SELECT id
         FROM kuppi_scheduled_sessions
         WHERE request_id = ?
           AND status = 'scheduled'
         LIMIT 1",
        [$requestId]
    );
}

function kuppi_scheduler_subject_options_for_user(
    string $role,
    int $userId,
    int $userBatchId,
    int $adminBatchId = 0
): array {
    if ($role === 'admin') {
        return $adminBatchId > 0 ? kuppi_subject_options_for_batch($adminBatchId) : [];
    }

    if ($role === 'moderator') {
        return kuppi_subject_options_for_batch($userBatchId);
    }

    if ($role === 'coordinator') {
        $subjects = subjects_all_for_coordinator($userId);
        return array_values(array_filter($subjects, static function (array $subject) use ($userBatchId): bool {
            return (int) ($subject['batch_id'] ?? 0) === $userBatchId;
        }));
    }

    return [];
}

