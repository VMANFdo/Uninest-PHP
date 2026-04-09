<?php

/**
 * Kuppi Module — Models
 */

function kuppi_allowed_statuses(): array
{
    return ['open', 'scheduled', 'completed', 'cancelled'];
}

function kuppi_sort_options(): array
{
    return ['most_votes', 'recent'];
}

function kuppi_batch_options_for_admin(): array
{
    return onboarding_approved_batches();
}

function kuppi_find_batch_option_by_id(int $batchId): ?array
{
    if ($batchId <= 0) {
        return null;
    }

    return db_fetch(
        "SELECT b.id, b.batch_code, b.name, b.program, b.intake_year,
                u.name AS university_name
         FROM batches b
         LEFT JOIN universities u ON u.id = b.university_id
         WHERE b.id = ?
           AND b.status = 'approved'
         LIMIT 1",
        [$batchId]
    );
}

function kuppi_subject_options_for_batch(int $batchId): array
{
    if ($batchId <= 0) {
        return [];
    }

    return db_fetch_all(
        "SELECT id, code, name, academic_year, semester
         FROM subjects
         WHERE batch_id = ?
         ORDER BY academic_year DESC, semester DESC, code ASC, name ASC",
        [$batchId]
    );
}

function kuppi_subject_exists_in_batch(int $subjectId, int $batchId): bool
{
    if ($subjectId <= 0 || $batchId <= 0) {
        return false;
    }

    return (bool) db_fetch(
        'SELECT id FROM subjects WHERE id = ? AND batch_id = ? LIMIT 1',
        [$subjectId, $batchId]
    );
}

function kuppi_requests_for_batch(
    int $batchId,
    ?int $subjectId,
    string $sortBy,
    int $viewerUserId,
    string $searchQuery = '',
    int $page = 1,
    int $perPage = 10
): array {
    if ($batchId <= 0) {
        return [
            'requests' => [],
            'has_more' => false,
        ];
    }

    $page = max(1, min(50, $page));
    $perPage = max(1, min(30, $perPage));
    $visibleLimit = $page * $perPage;
    $queryLimit = $visibleLimit + 1;

    $params = [$viewerUserId, $batchId];
    $subjectSql = '';
    if ($subjectId !== null && $subjectId > 0) {
        $subjectSql = ' AND kr.subject_id = ?';
        $params[] = $subjectId;
    }

    $searchSql = '';
    $searchQuery = trim($searchQuery);
    if ($searchQuery !== '') {
        $needle = '%' . $searchQuery . '%';
        $searchSql = ' AND (kr.title LIKE ? OR kr.description LIKE ? OR kr.tags_csv LIKE ? OR s.code LIKE ? OR s.name LIKE ?)';
        $params[] = $needle;
        $params[] = $needle;
        $params[] = $needle;
        $params[] = $needle;
        $params[] = $needle;
    }

    $orderBySql = $sortBy === 'recent'
        ? 'kr.created_at DESC, kr.id DESC'
        : 'COALESCE(vv.score, 0) DESC, COALESCE(vv.upvotes, 0) DESC, kr.created_at DESC, kr.id DESC';

    $rows = db_fetch_all(
        "SELECT kr.*,
                u.name AS requester_name,
                b.batch_code,
                b.name AS batch_name,
                s.code AS subject_code,
                s.name AS subject_name,
                COALESCE(vv.upvotes, 0) AS upvote_count,
                COALESCE(vv.downvotes, 0) AS downvote_count,
                COALESCE(vv.score, 0) AS vote_score,
                COALESCE(ca.conductor_count, 0) AS conductor_count,
                COALESCE(cc.comment_count, 0) AS comment_count,
                uv.vote_type AS viewer_vote
         FROM kuppi_requests kr
         INNER JOIN batches b ON b.id = kr.batch_id
         INNER JOIN subjects s ON s.id = kr.subject_id
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
            SELECT request_id, COUNT(*) AS conductor_count
            FROM kuppi_conductor_applications
            GROUP BY request_id
         ) ca ON ca.request_id = kr.id
         LEFT JOIN (
            SELECT target_id AS request_id, COUNT(*) AS comment_count
            FROM comments
            WHERE target_type = 'kuppi_request'
            GROUP BY target_id
         ) cc ON cc.request_id = kr.id
         LEFT JOIN kuppi_request_votes uv
                ON uv.request_id = kr.id
               AND uv.user_id = ?
         WHERE kr.batch_id = ?
           AND kr.status = 'open'
           {$subjectSql}
           {$searchSql}
         ORDER BY {$orderBySql}
         LIMIT {$queryLimit}",
        $params
    );

    $hasMore = count($rows) > $visibleLimit;
    if ($hasMore) {
        $rows = array_slice($rows, 0, $visibleLimit);
    }

    return [
        'requests' => $rows,
        'has_more' => $hasMore,
    ];
}

function kuppi_requests_count_for_batch(
    int $batchId,
    ?int $subjectId,
    string $searchQuery = ''
): int {
    if ($batchId <= 0) {
        return 0;
    }

    $params = [$batchId];
    $subjectSql = '';
    if ($subjectId !== null && $subjectId > 0) {
        $subjectSql = ' AND kr.subject_id = ?';
        $params[] = $subjectId;
    }

    $searchSql = '';
    $searchQuery = trim($searchQuery);
    if ($searchQuery !== '') {
        $needle = '%' . $searchQuery . '%';
        $searchSql = ' AND (kr.title LIKE ? OR kr.description LIKE ? OR kr.tags_csv LIKE ? OR s.code LIKE ? OR s.name LIKE ?)';
        $params[] = $needle;
        $params[] = $needle;
        $params[] = $needle;
        $params[] = $needle;
        $params[] = $needle;
    }

    $row = db_fetch(
        "SELECT COUNT(*) AS cnt
         FROM kuppi_requests kr
         INNER JOIN subjects s ON s.id = kr.subject_id
         WHERE kr.batch_id = ?
           AND kr.status = 'open'
           {$subjectSql}
           {$searchSql}",
        $params
    );

    return (int) ($row['cnt'] ?? 0);
}

function kuppi_find_request_for_batch(int $requestId, int $batchId, int $viewerUserId): ?array
{
    if ($requestId <= 0 || $batchId <= 0) {
        return null;
    }

    return db_fetch(
        "SELECT kr.*,
                u.name AS requester_name,
                b.batch_code,
                b.name AS batch_name,
                s.code AS subject_code,
                s.name AS subject_name,
                COALESCE(vv.upvotes, 0) AS upvote_count,
                COALESCE(vv.downvotes, 0) AS downvote_count,
                COALESCE(vv.score, 0) AS vote_score,
                COALESCE(ca.conductor_count, 0) AS conductor_count,
                uv.vote_type AS viewer_vote
         FROM kuppi_requests kr
         INNER JOIN batches b ON b.id = kr.batch_id
         INNER JOIN subjects s ON s.id = kr.subject_id
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
            SELECT request_id, COUNT(*) AS conductor_count
            FROM kuppi_conductor_applications
            GROUP BY request_id
         ) ca ON ca.request_id = kr.id
         LEFT JOIN kuppi_request_votes uv
                ON uv.request_id = kr.id
               AND uv.user_id = ?
         WHERE kr.id = ?
           AND kr.batch_id = ?
         LIMIT 1",
        [$viewerUserId, $requestId, $batchId]
    );
}

function kuppi_find_request_admin(int $requestId, int $viewerUserId): ?array
{
    if ($requestId <= 0) {
        return null;
    }

    return db_fetch(
        "SELECT kr.*,
                u.name AS requester_name,
                b.batch_code,
                b.name AS batch_name,
                s.code AS subject_code,
                s.name AS subject_name,
                COALESCE(vv.upvotes, 0) AS upvote_count,
                COALESCE(vv.downvotes, 0) AS downvote_count,
                COALESCE(vv.score, 0) AS vote_score,
                COALESCE(ca.conductor_count, 0) AS conductor_count,
                uv.vote_type AS viewer_vote
         FROM kuppi_requests kr
         INNER JOIN batches b ON b.id = kr.batch_id
         INNER JOIN subjects s ON s.id = kr.subject_id
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
            SELECT request_id, COUNT(*) AS conductor_count
            FROM kuppi_conductor_applications
            GROUP BY request_id
         ) ca ON ca.request_id = kr.id
         LEFT JOIN kuppi_request_votes uv
                ON uv.request_id = kr.id
               AND uv.user_id = ?
         WHERE kr.id = ?
         LIMIT 1",
        [$viewerUserId, $requestId]
    );
}

function kuppi_find_owned_request(int $requestId, int $ownerUserId): ?array
{
    if ($requestId <= 0 || $ownerUserId <= 0) {
        return null;
    }

    return db_fetch(
        "SELECT kr.*,
                b.batch_code,
                b.name AS batch_name,
                s.code AS subject_code,
                s.name AS subject_name,
                COALESCE(ca.conductor_count, 0) AS conductor_count
         FROM kuppi_requests kr
         INNER JOIN batches b ON b.id = kr.batch_id
         INNER JOIN subjects s ON s.id = kr.subject_id
         LEFT JOIN (
            SELECT request_id, COUNT(*) AS conductor_count
            FROM kuppi_conductor_applications
            GROUP BY request_id
         ) ca ON ca.request_id = kr.id
         WHERE kr.id = ?
           AND kr.requested_by_user_id = ?
         LIMIT 1",
        [$requestId, $ownerUserId]
    );
}

function kuppi_my_requests(int $ownerUserId): array
{
    if ($ownerUserId <= 0) {
        return [];
    }

    return db_fetch_all(
        "SELECT kr.*,
                b.batch_code,
                b.name AS batch_name,
                s.code AS subject_code,
                s.name AS subject_name,
                COALESCE(vv.upvotes, 0) AS upvote_count,
                COALESCE(vv.downvotes, 0) AS downvote_count,
                COALESCE(vv.score, 0) AS vote_score,
                COALESCE(ca.conductor_count, 0) AS conductor_count,
                COALESCE(cc.comment_count, 0) AS comment_count
         FROM kuppi_requests kr
         INNER JOIN batches b ON b.id = kr.batch_id
         INNER JOIN subjects s ON s.id = kr.subject_id
         LEFT JOIN (
            SELECT request_id,
                   SUM(CASE WHEN vote_type = 'up' THEN 1 ELSE 0 END) AS upvotes,
                   SUM(CASE WHEN vote_type = 'down' THEN 1 ELSE 0 END) AS downvotes,
                   SUM(CASE WHEN vote_type = 'up' THEN 1 ELSE -1 END) AS score
            FROM kuppi_request_votes
            GROUP BY request_id
         ) vv ON vv.request_id = kr.id
         LEFT JOIN (
            SELECT request_id, COUNT(*) AS conductor_count
            FROM kuppi_conductor_applications
            GROUP BY request_id
         ) ca ON ca.request_id = kr.id
         LEFT JOIN (
            SELECT target_id AS request_id, COUNT(*) AS comment_count
            FROM comments
            WHERE target_type = 'kuppi_request'
            GROUP BY target_id
         ) cc ON cc.request_id = kr.id
         WHERE kr.requested_by_user_id = ?
         ORDER BY kr.updated_at DESC, kr.id DESC",
        [$ownerUserId]
    );
}

function kuppi_create_request(array $data): int
{
    return (int) db_insert('kuppi_requests', [
        'batch_id' => (int) $data['batch_id'],
        'subject_id' => (int) $data['subject_id'],
        'requested_by_user_id' => $data['requested_by_user_id'] !== null ? (int) $data['requested_by_user_id'] : null,
        'title' => $data['title'],
        'description' => $data['description'],
        'tags_csv' => $data['tags_csv'] !== '' ? $data['tags_csv'] : null,
        'status' => $data['status'] ?? 'open',
    ]);
}

function kuppi_update_request_by_owner(int $requestId, int $ownerUserId, array $data): bool
{
    $stmt = db_query(
        "UPDATE kuppi_requests
         SET subject_id = ?,
             title = ?,
             description = ?,
             tags_csv = ?,
             updated_at = NOW()
         WHERE id = ?
           AND requested_by_user_id = ?
           AND status = 'open'",
        [
            (int) $data['subject_id'],
            $data['title'],
            $data['description'],
            $data['tags_csv'] !== '' ? $data['tags_csv'] : null,
            $requestId,
            $ownerUserId,
        ]
    );

    if ($stmt->rowCount() > 0) {
        return true;
    }

    return (bool) db_fetch(
        "SELECT id
         FROM kuppi_requests
         WHERE id = ?
           AND requested_by_user_id = ?
           AND status = 'open'
         LIMIT 1",
        [$requestId, $ownerUserId]
    );
}

function kuppi_delete_request_by_id(int $requestId): bool
{
    if ($requestId <= 0) {
        return false;
    }

    $pdo = db_connect();
    $pdo->beginTransaction();

    try {
        comments_delete_for_target('kuppi_request', $requestId);
        $deleted = db_query(
            'DELETE FROM kuppi_requests WHERE id = ?',
            [$requestId]
        )->rowCount() > 0;

        if (!$deleted) {
            $pdo->rollBack();
            return false;
        }

        $pdo->commit();
        return true;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

function kuppi_request_ids_for_batch(int $batchId): array
{
    if ($batchId <= 0) {
        return [];
    }

    $rows = db_fetch_all(
        'SELECT id FROM kuppi_requests WHERE batch_id = ?',
        [$batchId]
    );

    return array_values(array_filter(array_map(
        static fn(array $row): int => (int) ($row['id'] ?? 0),
        $rows
    ), static fn(int $id): bool => $id > 0));
}

function kuppi_delete_comments_for_request(int $requestId): int
{
    if ($requestId <= 0) {
        return 0;
    }

    return comments_delete_for_target('kuppi_request', $requestId);
}

function kuppi_delete_comments_for_request_ids(array $requestIds): int
{
    return comments_delete_for_target_ids('kuppi_request', $requestIds);
}

function kuppi_delete_comments_for_batch(int $batchId): int
{
    return kuppi_delete_comments_for_request_ids(kuppi_request_ids_for_batch($batchId));
}

function kuppi_apply_vote(int $requestId, int $userId, string $direction): ?string
{
    if ($requestId <= 0 || $userId <= 0 || !in_array($direction, ['up', 'down'], true)) {
        return null;
    }

    $existing = db_fetch(
        "SELECT id, vote_type
         FROM kuppi_request_votes
         WHERE request_id = ?
           AND user_id = ?
         LIMIT 1",
        [$requestId, $userId]
    );

    if ($existing) {
        $existingDirection = (string) ($existing['vote_type'] ?? '');
        if ($existingDirection === $direction) {
            db_query(
                'DELETE FROM kuppi_request_votes WHERE request_id = ? AND user_id = ?',
                [$requestId, $userId]
            );
            return null;
        }

        db_query(
            "UPDATE kuppi_request_votes
             SET vote_type = ?,
                 updated_at = NOW()
             WHERE request_id = ?
               AND user_id = ?",
            [$direction, $requestId, $userId]
        );
        return $direction;
    }

    db_insert('kuppi_request_votes', [
        'request_id' => $requestId,
        'user_id' => $userId,
        'vote_type' => $direction,
    ]);

    return $direction;
}

function kuppi_vote_totals_for_request(int $requestId): array
{
    if ($requestId <= 0) {
        return ['upvote_count' => 0, 'downvote_count' => 0, 'vote_score' => 0];
    }

    $row = db_fetch(
        "SELECT
            COALESCE(SUM(CASE WHEN vote_type = 'up' THEN 1 ELSE 0 END), 0) AS upvote_count,
            COALESCE(SUM(CASE WHEN vote_type = 'down' THEN 1 ELSE 0 END), 0) AS downvote_count,
            COALESCE(SUM(CASE WHEN vote_type = 'up' THEN 1 ELSE -1 END), 0) AS vote_score
         FROM kuppi_request_votes
         WHERE request_id = ?",
        [$requestId]
    ) ?? [];

    return [
        'upvote_count' => (int) ($row['upvote_count'] ?? 0),
        'downvote_count' => (int) ($row['downvote_count'] ?? 0),
        'vote_score' => (int) ($row['vote_score'] ?? 0),
    ];
}

function kuppi_find_conductor_application_for_request(int $applicationId, int $requestId): ?array
{
    if ($applicationId <= 0 || $requestId <= 0) {
        return null;
    }

    return db_fetch(
        "SELECT a.*,
                u.name AS applicant_name,
                u.role AS applicant_role,
                u.academic_year AS applicant_academic_year
         FROM kuppi_conductor_applications a
         INNER JOIN users u ON u.id = a.applicant_user_id
         WHERE a.id = ?
           AND a.request_id = ?
         LIMIT 1",
        [$applicationId, $requestId]
    );
}

function kuppi_find_user_conductor_application(int $requestId, int $userId): ?array
{
    if ($requestId <= 0 || $userId <= 0) {
        return null;
    }

    return db_fetch(
        "SELECT a.*,
                u.name AS applicant_name,
                u.role AS applicant_role,
                u.academic_year AS applicant_academic_year
         FROM kuppi_conductor_applications a
         INNER JOIN users u ON u.id = a.applicant_user_id
         WHERE a.request_id = ?
           AND a.applicant_user_id = ?
         LIMIT 1",
        [$requestId, $userId]
    );
}

function kuppi_conductor_applications_for_request(int $requestId, int $viewerUserId): array
{
    if ($requestId <= 0) {
        return [];
    }

    return db_fetch_all(
        "SELECT a.*,
                u.name AS applicant_name,
                u.role AS applicant_role,
                u.academic_year AS applicant_academic_year,
                COALESCE(cv.vote_count, 0) AS vote_count,
                CASE WHEN uv.id IS NULL THEN 0 ELSE 1 END AS is_voted_by_viewer
         FROM kuppi_conductor_applications a
         INNER JOIN users u ON u.id = a.applicant_user_id
         LEFT JOIN (
            SELECT application_id, COUNT(*) AS vote_count
            FROM kuppi_conductor_votes
            GROUP BY application_id
         ) cv ON cv.application_id = a.id
         LEFT JOIN kuppi_conductor_votes uv
                ON uv.application_id = a.id
               AND uv.voter_user_id = ?
         WHERE a.request_id = ?
         ORDER BY COALESCE(cv.vote_count, 0) DESC, a.created_at ASC, a.id ASC",
        [$viewerUserId, $requestId]
    );
}

function kuppi_conductor_application_count_for_request(int $requestId): int
{
    if ($requestId <= 0) {
        return 0;
    }

    $row = db_fetch(
        'SELECT COUNT(*) AS cnt FROM kuppi_conductor_applications WHERE request_id = ?',
        [$requestId]
    );

    return (int) ($row['cnt'] ?? 0);
}

function kuppi_create_conductor_application(array $data): int
{
    return (int) db_insert('kuppi_conductor_applications', [
        'request_id' => (int) $data['request_id'],
        'applicant_user_id' => (int) $data['applicant_user_id'],
        'motivation' => $data['motivation'],
        'availability_csv' => $data['availability_csv'],
    ]);
}

function kuppi_toggle_conductor_vote(int $applicationId, int $voterUserId): bool
{
    if ($applicationId <= 0 || $voterUserId <= 0) {
        return false;
    }

    $existing = db_fetch(
        "SELECT id
         FROM kuppi_conductor_votes
         WHERE application_id = ?
           AND voter_user_id = ?
         LIMIT 1",
        [$applicationId, $voterUserId]
    );

    if ($existing) {
        db_query(
            "DELETE FROM kuppi_conductor_votes
             WHERE application_id = ?
               AND voter_user_id = ?",
            [$applicationId, $voterUserId]
        );
        return false;
    }

    db_insert('kuppi_conductor_votes', [
        'application_id' => $applicationId,
        'voter_user_id' => $voterUserId,
    ]);

    return true;
}

function kuppi_scheduled_statuses(): array
{
    return ['scheduled', 'completed', 'cancelled'];
}

function kuppi_scheduled_location_types(): array
{
    return ['physical', 'online'];
}

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

function kuppi_scheduled_sessions_for_scope(
    string $role,
    int $userBatchId,
    string $searchQuery = '',
    int $subjectId = 0,
    string $statusFilter = '',
    int $adminBatchId = 0
): array {
    $where = ['1=1'];
    $params = [];

    if ($role === 'admin') {
        if ($adminBatchId > 0) {
            $where[] = 'ks.batch_id = ?';
            $params[] = $adminBatchId;
        }
    } else {
        if ($userBatchId <= 0) {
            return [];
        }

        $where[] = 'ks.batch_id = ?';
        $params[] = $userBatchId;
    }

    if ($subjectId > 0) {
        $where[] = 'ks.subject_id = ?';
        $params[] = $subjectId;
    }

    if (in_array($statusFilter, kuppi_scheduled_statuses(), true)) {
        $where[] = 'ks.status = ?';
        $params[] = $statusFilter;
    }

    $searchQuery = trim($searchQuery);
    if ($searchQuery !== '') {
        $needle = '%' . $searchQuery . '%';
        $where[] = '(ks.title LIKE ? OR ks.description LIKE ? OR s.code LIKE ? OR s.name LIKE ? OR b.batch_code LIKE ?)';
        $params[] = $needle;
        $params[] = $needle;
        $params[] = $needle;
        $params[] = $needle;
        $params[] = $needle;
    }

    $whereSql = implode(' AND ', $where);

    return db_fetch_all(
        "SELECT ks.*,
                s.code AS subject_code,
                s.name AS subject_name,
                b.batch_code,
                b.name AS batch_name,
                u.name AS creator_name,
                COALESCE(hc.host_count, 0) AS host_count,
                req.title AS request_title
         FROM kuppi_scheduled_sessions ks
         INNER JOIN subjects s ON s.id = ks.subject_id
         INNER JOIN batches b ON b.id = ks.batch_id
         LEFT JOIN users u ON u.id = ks.created_by_user_id
         LEFT JOIN kuppi_requests req ON req.id = ks.request_id
         LEFT JOIN (
            SELECT session_id, COUNT(*) AS host_count
            FROM kuppi_scheduled_session_hosts
            GROUP BY session_id
         ) hc ON hc.session_id = ks.id
         WHERE {$whereSql}
         ORDER BY
            CASE ks.status
                WHEN 'scheduled' THEN 1
                WHEN 'completed' THEN 2
                ELSE 3
            END ASC,
            ks.session_date ASC,
            ks.start_time ASC,
            ks.id DESC
         LIMIT 500",
        $params
    );
}

function kuppi_find_scheduled_session_readable(int $sessionId, string $role, int $userBatchId): ?array
{
    if ($sessionId <= 0) {
        return null;
    }

    $params = [$sessionId];
    $scopeSql = '';
    if ($role !== 'admin') {
        if ($userBatchId <= 0) {
            return null;
        }
        $scopeSql = ' AND ks.batch_id = ?';
        $params[] = $userBatchId;
    }

    return db_fetch(
        "SELECT ks.*,
                s.code AS subject_code,
                s.name AS subject_name,
                b.batch_code,
                b.name AS batch_name,
                req.requested_by_user_id,
                req.title AS request_title,
                req.status AS request_status,
                req.description AS request_description
         FROM kuppi_scheduled_sessions ks
         INNER JOIN subjects s ON s.id = ks.subject_id
         INNER JOIN batches b ON b.id = ks.batch_id
         LEFT JOIN kuppi_requests req ON req.id = ks.request_id
         WHERE ks.id = ?
           {$scopeSql}
         LIMIT 1",
        $params
    );
}

function kuppi_scheduled_hosts_for_session(int $sessionId): array
{
    if ($sessionId <= 0) {
        return [];
    }

    return db_fetch_all(
        "SELECT sh.*,
                u.name AS host_name,
                u.email AS host_email,
                u.role AS host_role,
                u.academic_year AS host_academic_year,
                a.availability_csv,
                COALESCE(cv.vote_count, 0) AS conductor_vote_count
         FROM kuppi_scheduled_session_hosts sh
         INNER JOIN users u ON u.id = sh.host_user_id
         LEFT JOIN kuppi_conductor_applications a ON a.id = sh.source_application_id
         LEFT JOIN (
            SELECT application_id, COUNT(*) AS vote_count
            FROM kuppi_conductor_votes
            GROUP BY application_id
         ) cv ON cv.application_id = a.id
         WHERE sh.session_id = ?
         ORDER BY u.name ASC, sh.id ASC",
        [$sessionId]
    );
}

function kuppi_scheduled_create_with_hosts(array $data, array $hosts): int
{
    $pdo = db_connect();
    $pdo->beginTransaction();

    try {
        $requestId = (int) ($data['request_id'] ?? 0);
        if ($requestId > 0) {
            $active = db_fetch(
                "SELECT id
                 FROM kuppi_scheduled_sessions
                 WHERE request_id = ?
                   AND status = 'scheduled'
                 FOR UPDATE",
                [$requestId]
            );
            if ($active) {
                $pdo->rollBack();
                return 0;
            }
        }

        $sessionId = (int) db_insert('kuppi_scheduled_sessions', [
            'batch_id' => (int) $data['batch_id'],
            'subject_id' => (int) $data['subject_id'],
            'request_id' => $requestId > 0 ? $requestId : null,
            'title' => $data['title'],
            'description' => $data['description'],
            'session_date' => $data['session_date'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'duration_minutes' => (int) $data['duration_minutes'],
            'max_attendees' => (int) $data['max_attendees'],
            'location_type' => $data['location_type'],
            'location_text' => $data['location_text'] !== '' ? $data['location_text'] : null,
            'meeting_link' => $data['meeting_link'] !== '' ? $data['meeting_link'] : null,
            'notes' => $data['notes'] !== '' ? $data['notes'] : null,
            'status' => $data['status'] ?? 'scheduled',
            'created_by_user_id' => (int) $data['created_by_user_id'],
            'cancelled_by_user_id' => null,
            'cancelled_at' => null,
        ]);

        foreach ($hosts as $host) {
            db_insert('kuppi_scheduled_session_hosts', [
                'session_id' => $sessionId,
                'host_user_id' => (int) $host['host_user_id'],
                'source_type' => $host['source_type'],
                'source_application_id' => !empty($host['source_application_id']) ? (int) $host['source_application_id'] : null,
                'assigned_by_user_id' => (int) $host['assigned_by_user_id'],
            ]);
        }

        if ($requestId > 0) {
            db_query(
                "UPDATE kuppi_requests
                 SET status = 'scheduled',
                     updated_at = NOW()
                 WHERE id = ?",
                [$requestId]
            );
        }

        $pdo->commit();
        return $sessionId;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

function kuppi_scheduled_replace_hosts(int $sessionId, array $hosts): void
{
    db_query(
        'DELETE FROM kuppi_scheduled_session_hosts WHERE session_id = ?',
        [$sessionId]
    );

    foreach ($hosts as $host) {
        db_insert('kuppi_scheduled_session_hosts', [
            'session_id' => $sessionId,
            'host_user_id' => (int) $host['host_user_id'],
            'source_type' => $host['source_type'],
            'source_application_id' => !empty($host['source_application_id']) ? (int) $host['source_application_id'] : null,
            'assigned_by_user_id' => (int) $host['assigned_by_user_id'],
        ]);
    }
}

function kuppi_scheduled_update_with_hosts(int $sessionId, array $data, array $hosts): bool
{
    if ($sessionId <= 0) {
        return false;
    }

    $pdo = db_connect();
    $pdo->beginTransaction();

    try {
        $existing = db_fetch(
            "SELECT id, request_id
             FROM kuppi_scheduled_sessions
             WHERE id = ?
             FOR UPDATE",
            [$sessionId]
        );
        if (!$existing) {
            $pdo->rollBack();
            return false;
        }

        $requestId = (int) ($existing['request_id'] ?? 0);
        $status = (string) ($data['status'] ?? 'scheduled');
        if (!in_array($status, ['scheduled', 'completed'], true)) {
            $status = 'scheduled';
        }

        if ($requestId > 0 && $status === 'scheduled') {
            $conflict = db_fetch(
                "SELECT id
                 FROM kuppi_scheduled_sessions
                 WHERE request_id = ?
                   AND status = 'scheduled'
                   AND id <> ?
                 FOR UPDATE",
                [$requestId, $sessionId]
            );
            if ($conflict) {
                $pdo->rollBack();
                return false;
            }
        }

        db_query(
            "UPDATE kuppi_scheduled_sessions
             SET title = ?,
                 description = ?,
                 session_date = ?,
                 start_time = ?,
                 end_time = ?,
                 duration_minutes = ?,
                 max_attendees = ?,
                 location_type = ?,
                 location_text = ?,
                 meeting_link = ?,
                 notes = ?,
                 status = ?,
                 cancelled_by_user_id = CASE WHEN ? = 'cancelled' THEN cancelled_by_user_id ELSE NULL END,
                 cancelled_at = CASE WHEN ? = 'cancelled' THEN cancelled_at ELSE NULL END,
                 updated_at = NOW()
             WHERE id = ?",
            [
                $data['title'],
                $data['description'],
                $data['session_date'],
                $data['start_time'],
                $data['end_time'],
                (int) $data['duration_minutes'],
                (int) $data['max_attendees'],
                $data['location_type'],
                $data['location_text'] !== '' ? $data['location_text'] : null,
                $data['meeting_link'] !== '' ? $data['meeting_link'] : null,
                $data['notes'] !== '' ? $data['notes'] : null,
                $status,
                $status,
                $status,
                $sessionId,
            ]
        );

        kuppi_scheduled_replace_hosts($sessionId, $hosts);

        if ($requestId > 0) {
            $requestStatus = $status === 'completed' ? 'completed' : 'scheduled';
            db_query(
                "UPDATE kuppi_requests
                 SET status = ?,
                     updated_at = NOW()
                 WHERE id = ?",
                [$requestStatus, $requestId]
            );
        }

        $pdo->commit();
        return true;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

function kuppi_scheduled_cancel(int $sessionId, int $cancelledByUserId): bool
{
    if ($sessionId <= 0) {
        return false;
    }

    $pdo = db_connect();
    $pdo->beginTransaction();

    try {
        $row = db_fetch(
            "SELECT id, request_id, status
             FROM kuppi_scheduled_sessions
             WHERE id = ?
             FOR UPDATE",
            [$sessionId]
        );

        if (!$row) {
            $pdo->rollBack();
            return false;
        }

        db_query(
            "UPDATE kuppi_scheduled_sessions
             SET status = 'cancelled',
                 cancelled_by_user_id = ?,
                 cancelled_at = NOW(),
                 updated_at = NOW()
             WHERE id = ?",
            [$cancelledByUserId, $sessionId]
        );

        $requestId = (int) ($row['request_id'] ?? 0);
        if ($requestId > 0) {
            db_query(
                "UPDATE kuppi_requests
                 SET status = 'open',
                     updated_at = NOW()
                 WHERE id = ?",
                [$requestId]
            );
        }

        $pdo->commit();
        return true;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

function kuppi_scheduled_delete(int $sessionId): bool
{
    if ($sessionId <= 0) {
        return false;
    }

    $pdo = db_connect();
    $pdo->beginTransaction();

    try {
        $row = db_fetch(
            "SELECT id, request_id
             FROM kuppi_scheduled_sessions
             WHERE id = ?
             FOR UPDATE",
            [$sessionId]
        );
        if (!$row) {
            $pdo->rollBack();
            return false;
        }

        $requestId = (int) ($row['request_id'] ?? 0);
        if ($requestId > 0) {
            db_query(
                "UPDATE kuppi_requests
                 SET status = 'open',
                     updated_at = NOW()
                 WHERE id = ?",
                [$requestId]
            );
        }

        db_query(
            'DELETE FROM kuppi_scheduled_sessions WHERE id = ?',
            [$sessionId]
        );

        $pdo->commit();
        return true;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

function kuppi_scheduled_notification_batch_recipients(int $batchId): array
{
    if ($batchId <= 0) {
        return [];
    }

    return db_fetch_all(
        "SELECT id AS user_id,
                name AS user_name,
                email AS user_email
         FROM users
         WHERE batch_id = ?
           AND email IS NOT NULL
           AND email <> ''",
        [$batchId]
    );
}

function kuppi_scheduled_notification_request_owner(int $requestId): ?array
{
    if ($requestId <= 0) {
        return null;
    }

    return db_fetch(
        "SELECT u.id AS user_id,
                u.name AS user_name,
                u.email AS user_email
         FROM kuppi_requests kr
         INNER JOIN users u ON u.id = kr.requested_by_user_id
         WHERE kr.id = ?
           AND u.email IS NOT NULL
           AND u.email <> ''
         LIMIT 1",
        [$requestId]
    );
}
