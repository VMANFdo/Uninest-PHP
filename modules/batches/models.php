<?php

/**
 * Batches Module — Models
 */

function batches_admin_all(): array
{
    return db_fetch_all(
        "SELECT b.*, uni.name AS university_name,
                pm.name AS primary_moderator_name,
                pm.email AS primary_moderator_email,
                (SELECT COUNT(*) FROM users m WHERE m.role = 'moderator' AND m.batch_id = b.id) AS moderators_count,
                (SELECT COUNT(*) FROM users s WHERE s.role = 'student' AND s.batch_id = b.id) AS students_count,
                (SELECT COUNT(*) FROM subjects sub WHERE sub.batch_id = b.id) AS subjects_count
         FROM batches b
         LEFT JOIN universities uni ON uni.id = b.university_id
         LEFT JOIN users pm ON pm.id = b.moderator_user_id
         ORDER BY b.created_at DESC"
    );
}

function batches_find_admin(int $batchId): ?array
{
    return db_fetch(
        "SELECT b.*, uni.name AS university_name,
                pm.name AS primary_moderator_name,
                pm.email AS primary_moderator_email,
                (SELECT COUNT(*) FROM users m WHERE m.role = 'moderator' AND m.batch_id = b.id) AS moderators_count,
                (SELECT COUNT(*) FROM users s WHERE s.role = 'student' AND s.batch_id = b.id) AS students_count,
                (SELECT COUNT(*) FROM subjects sub WHERE sub.batch_id = b.id) AS subjects_count
         FROM batches b
         LEFT JOIN universities uni ON uni.id = b.university_id
         LEFT JOIN users pm ON pm.id = b.moderator_user_id
         WHERE b.id = ?
         LIMIT 1",
        [$batchId]
    );
}

function batches_primary_moderator_candidates(int $currentBatchId = 0): array
{
    return db_fetch_all(
        "SELECT u.id, u.name, u.email, u.university_id, uni.name AS university_name
         FROM users u
         LEFT JOIN universities uni ON uni.id = u.university_id
         LEFT JOIN batches owned ON owned.moderator_user_id = u.id AND owned.id != ?
         WHERE u.role = 'moderator'
           AND owned.id IS NULL
           AND (u.batch_id IS NULL OR u.batch_id = 0 OR u.batch_id = ?)
         ORDER BY u.name ASC",
        [$currentBatchId, $currentBatchId]
    );
}

function batches_find_primary_moderator_candidate(int $moderatorUserId, int $currentBatchId = 0): ?array
{
    return db_fetch(
        "SELECT u.id, u.name, u.email, u.university_id, u.batch_id,
                uni.name AS university_name,
                owned.id AS owned_batch_id
         FROM users u
         LEFT JOIN universities uni ON uni.id = u.university_id
         LEFT JOIN batches owned ON owned.moderator_user_id = u.id AND owned.id != ?
         WHERE u.id = ?
           AND u.role = 'moderator'
           AND owned.id IS NULL
           AND (u.batch_id IS NULL OR u.batch_id = 0 OR u.batch_id = ?)
         LIMIT 1",
        [$currentBatchId, $moderatorUserId, $currentBatchId]
    );
}

function batches_available_primary_moderators(): array
{
    return batches_primary_moderator_candidates(0);
}

function batches_find_available_primary_moderator(int $moderatorUserId): ?array
{
    return batches_find_primary_moderator_candidate($moderatorUserId, 0);
}

function batches_batch_code_exists_for_other(string $batchCode, int $excludeBatchId): bool
{
    return (bool) db_fetch(
        'SELECT id FROM batches WHERE batch_code = ? AND id != ? LIMIT 1',
        [$batchCode, $excludeBatchId]
    );
}

function batches_create_admin(array $data, int $adminUserId): int
{
    $pdo = db_connect();
    $pdo->beginTransaction();

    try {
        $moderatorUserId = (int) $data['moderator_user_id'];
        $moderator = db_fetch(
            "SELECT u.id, u.role, u.university_id, u.batch_id, owned.id AS owned_batch_id
             FROM users u
             LEFT JOIN batches owned ON owned.moderator_user_id = u.id
             WHERE u.id = ?
             FOR UPDATE",
            [$moderatorUserId]
        );

        if (!$moderator || ($moderator['role'] ?? '') !== 'moderator') {
            $pdo->rollBack();
            return 0;
        }

        if (!empty($moderator['owned_batch_id'])) {
            $pdo->rollBack();
            return 0;
        }

        if (!empty($moderator['batch_id'])) {
            $pdo->rollBack();
            return 0;
        }

        $universityId = (int) $data['university_id'];
        if ((int) ($moderator['university_id'] ?? 0) !== $universityId) {
            $pdo->rollBack();
            return 0;
        }

        $batchCode = strtoupper(trim((string) ($data['batch_code'] ?? '')));
        if ($batchCode === '') {
            $batchCode = onboarding_generate_batch_code();
        }

        if (onboarding_batch_code_exists($batchCode)) {
            $pdo->rollBack();
            return 0;
        }

        $batchId = (int) db_insert('batches', [
            'batch_code'        => $batchCode,
            'name'              => $data['name'],
            'program'           => $data['program'],
            'intake_year'       => (int) $data['intake_year'],
            'university_id'     => $universityId,
            'moderator_user_id' => $moderatorUserId,
            'status'            => 'approved',
            'rejection_reason'  => null,
            'reviewed_by'       => $adminUserId,
            'reviewed_at'       => date('Y-m-d H:i:s'),
        ]);

        db_query(
            'UPDATE users SET batch_id = ? WHERE id = ? AND role = ?',
            [$batchId, $moderatorUserId, 'moderator']
        );

        $pdo->commit();
        return $batchId;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

function batches_update_admin(int $batchId, array $data, int $adminUserId): bool
{
    $pdo = db_connect();
    $pdo->beginTransaction();

    try {
        $batch = db_fetch('SELECT * FROM batches WHERE id = ? FOR UPDATE', [$batchId]);
        if (!$batch) {
            $pdo->rollBack();
            return false;
        }

        $moderatorUserId = (int) $data['moderator_user_id'];
        $moderator = db_fetch(
            "SELECT u.id, u.role, u.university_id, u.batch_id, owned.id AS owned_batch_id
             FROM users u
             LEFT JOIN batches owned ON owned.moderator_user_id = u.id AND owned.id != ?
             WHERE u.id = ?
             FOR UPDATE",
            [$batchId, $moderatorUserId]
        );

        if (!$moderator || ($moderator['role'] ?? '') !== 'moderator') {
            $pdo->rollBack();
            return false;
        }

        if (!empty($moderator['owned_batch_id'])) {
            $pdo->rollBack();
            return false;
        }

        $moderatorBatchId = (int) ($moderator['batch_id'] ?? 0);
        if ($moderatorBatchId > 0 && $moderatorBatchId !== $batchId) {
            $pdo->rollBack();
            return false;
        }

        $universityId = (int) $data['university_id'];
        if ((int) ($moderator['university_id'] ?? 0) !== $universityId) {
            $pdo->rollBack();
            return false;
        }

        $mismatchedModerators = (int) (db_fetch(
            "SELECT COUNT(*) AS cnt
             FROM users
             WHERE role = 'moderator'
               AND batch_id = ?
               AND university_id != ?
               AND id != ?",
            [$batchId, $universityId, $moderatorUserId]
        )['cnt'] ?? 0);

        if ($mismatchedModerators > 0) {
            $pdo->rollBack();
            return false;
        }

        $batchCode = strtoupper(trim((string) $data['batch_code']));
        if ($batchCode !== '' && batches_batch_code_exists_for_other($batchCode, $batchId)) {
            $pdo->rollBack();
            return false;
        }

        $status = (string) $data['status'];
        $allowedStatuses = ['pending', 'approved', 'rejected', 'inactive'];
        if (!in_array($status, $allowedStatuses, true)) {
            $pdo->rollBack();
            return false;
        }

        $rejectionReason = trim((string) ($data['rejection_reason'] ?? ''));
        if ($status !== 'rejected') {
            $rejectionReason = '';
        }

        $reviewedBy = $status === 'pending' ? null : $adminUserId;
        $reviewedAt = $status === 'pending' ? null : date('Y-m-d H:i:s');

        db_query(
            "UPDATE batches
             SET batch_code = ?,
                 name = ?,
                 program = ?,
                 intake_year = ?,
                 university_id = ?,
                 moderator_user_id = ?,
                 status = ?,
                 rejection_reason = ?,
                 reviewed_by = ?,
                 reviewed_at = ?
             WHERE id = ?",
            [
                $batchCode,
                $data['name'],
                $data['program'],
                (int) $data['intake_year'],
                $universityId,
                $moderatorUserId,
                $status,
                $rejectionReason !== '' ? $rejectionReason : null,
                $reviewedBy,
                $reviewedAt,
                $batchId,
            ]
        );

        db_query(
            'UPDATE users SET batch_id = ? WHERE id = ? AND role = ?',
            [$batchId, $moderatorUserId, 'moderator']
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

function batches_delete_admin(int $batchId): bool
{
    $pdo = db_connect();
    $pdo->beginTransaction();

    try {
        resources_delete_comments_for_batch($batchId);
        community_delete_comments_for_batch($batchId);
        kuppi_delete_comments_for_batch($batchId);
        $deleted = db_query('DELETE FROM batches WHERE id = ?', [$batchId])->rowCount() > 0;
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

function batches_delete_has_locked_students(int $batchId): bool
{
    return (bool) db_fetch(
        'SELECT id FROM users WHERE first_approved_batch_id = ? LIMIT 1',
        [$batchId]
    );
}
