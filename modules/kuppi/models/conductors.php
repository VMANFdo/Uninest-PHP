<?php

/**
 * Kuppi Module — Conductor Application Models
 */

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

function kuppi_update_conductor_application_by_owner(int $applicationId, int $requestId, int $ownerUserId, array $data): bool
{
    if ($applicationId <= 0 || $requestId <= 0 || $ownerUserId <= 0) {
        return false;
    }

    $stmt = db_query(
        "UPDATE kuppi_conductor_applications a
         INNER JOIN kuppi_requests r ON r.id = a.request_id
         SET a.motivation = ?,
             a.availability_csv = ?,
             a.updated_at = NOW()
         WHERE a.id = ?
           AND a.request_id = ?
           AND a.applicant_user_id = ?
           AND r.status = 'open'",
        [
            $data['motivation'],
            $data['availability_csv'],
            $applicationId,
            $requestId,
            $ownerUserId,
        ]
    );

    if ($stmt->rowCount() > 0) {
        return true;
    }

    return (bool) db_fetch(
        "SELECT a.id
         FROM kuppi_conductor_applications a
         INNER JOIN kuppi_requests r ON r.id = a.request_id
         WHERE a.id = ?
           AND a.request_id = ?
           AND a.applicant_user_id = ?
           AND r.status = 'open'
         LIMIT 1",
        [$applicationId, $requestId, $ownerUserId]
    );
}

function kuppi_delete_conductor_application_by_owner(int $applicationId, int $requestId, int $ownerUserId): bool
{
    if ($applicationId <= 0 || $requestId <= 0 || $ownerUserId <= 0) {
        return false;
    }

    return db_query(
        "DELETE a
         FROM kuppi_conductor_applications a
         INNER JOIN kuppi_requests r ON r.id = a.request_id
         WHERE a.id = ?
           AND a.request_id = ?
           AND a.applicant_user_id = ?
           AND r.status = 'open'",
        [$applicationId, $requestId, $ownerUserId]
    )->rowCount() > 0;
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

function kuppi_remove_conductor_vote(int $applicationId, int $voterUserId): bool
{
    if ($applicationId <= 0 || $voterUserId <= 0) {
        return false;
    }

    return db_query(
        "DELETE FROM kuppi_conductor_votes
         WHERE application_id = ?
           AND voter_user_id = ?",
        [$applicationId, $voterUserId]
    )->rowCount() > 0;
}

