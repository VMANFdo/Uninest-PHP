<?php

/**
 * Kuppi Module — Scheduled Session Models
 */

function kuppi_scheduled_statuses(): array
{
    return ['scheduled', 'completed', 'cancelled'];
}

function kuppi_scheduled_location_types(): array
{
    return ['physical', 'online'];
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
