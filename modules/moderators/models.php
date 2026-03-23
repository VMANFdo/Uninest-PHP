<?php

/**
 * Moderators Module — Models
 */

function moderators_admin_all(): array
{
    return db_fetch_all(
        "SELECT u.id, u.name, u.email, u.academic_year, u.university_id, u.batch_id, u.created_at,
                uni.name AS university_name,
                ab.name AS assigned_batch_name,
                ab.batch_code AS assigned_batch_code,
                ab.status AS assigned_batch_status,
                ob.id AS owned_batch_id,
                ob.name AS owned_batch_name,
                ob.batch_code AS owned_batch_code
         FROM users u
         LEFT JOIN universities uni ON uni.id = u.university_id
         LEFT JOIN batches ab ON ab.id = u.batch_id
         LEFT JOIN batches ob ON ob.moderator_user_id = u.id
         WHERE u.role = 'moderator'
         ORDER BY u.created_at DESC"
    );
}

function moderators_find_admin(int $moderatorId): ?array
{
    return db_fetch(
        "SELECT u.id, u.name, u.email, u.academic_year, u.university_id, u.batch_id, u.created_at,
                uni.name AS university_name,
                ab.name AS assigned_batch_name,
                ab.batch_code AS assigned_batch_code,
                ab.status AS assigned_batch_status,
                ab.university_id AS assigned_batch_university_id,
                ob.id AS owned_batch_id,
                ob.name AS owned_batch_name,
                ob.batch_code AS owned_batch_code,
                ob.university_id AS owned_batch_university_id
         FROM users u
         LEFT JOIN universities uni ON uni.id = u.university_id
         LEFT JOIN batches ab ON ab.id = u.batch_id
         LEFT JOIN batches ob ON ob.moderator_user_id = u.id
         WHERE u.id = ? AND u.role = 'moderator'
         LIMIT 1",
        [$moderatorId]
    );
}

function moderators_email_exists(string $email, ?int $excludeId = null): bool
{
    if ($excludeId !== null) {
        return (bool) db_fetch('SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1', [$email, $excludeId]);
    }

    return (bool) db_fetch('SELECT id FROM users WHERE email = ? LIMIT 1', [$email]);
}

function moderators_create_admin(array $data): int
{
    return (int) db_insert('users', [
        'name'                    => $data['name'],
        'email'                   => $data['email'],
        'password'                => password_hash($data['password'], PASSWORD_DEFAULT),
        'role'                    => 'moderator',
        'academic_year'           => (int) $data['academic_year'],
        'university_id'           => (int) $data['university_id'],
        'batch_id'                => $data['batch_id'] !== null ? (int) $data['batch_id'] : null,
        'first_approved_batch_id' => null,
    ]);
}

function moderators_update_admin(int $moderatorId, array $data): string
{
    $pdo = db_connect();
    $pdo->beginTransaction();

    try {
        $moderator = db_fetch(
            'SELECT id, role, batch_id FROM users WHERE id = ? AND role = ? FOR UPDATE',
            [$moderatorId, 'moderator']
        );

        if (!$moderator) {
            $pdo->rollBack();
            return 'not_found';
        }

        $ownedBatch = db_fetch(
            'SELECT id, university_id FROM batches WHERE moderator_user_id = ? LIMIT 1 FOR UPDATE',
            [$moderatorId]
        );

        $newBatchId = $data['batch_id'] !== null ? (int) $data['batch_id'] : null;
        $newUniversityId = (int) $data['university_id'];

        if ($ownedBatch) {
            $ownedBatchId = (int) $ownedBatch['id'];
            if ($newBatchId === null || $newBatchId !== $ownedBatchId) {
                $pdo->rollBack();
                return 'owned_batch_lock';
            }

            if ((int) $ownedBatch['university_id'] !== $newUniversityId) {
                $pdo->rollBack();
                return 'owned_batch_university_mismatch';
            }
        }

        if ($newBatchId !== null) {
            $assignedBatch = db_fetch(
                'SELECT id, university_id, status FROM batches WHERE id = ? FOR UPDATE',
                [$newBatchId]
            );

            $isOwnedSelection = $ownedBatch && (int) $ownedBatch['id'] === $newBatchId;
            if (!$assignedBatch) {
                $pdo->rollBack();
                return 'invalid_batch';
            }

            if (!$isOwnedSelection && ($assignedBatch['status'] ?? '') !== 'approved') {
                $pdo->rollBack();
                return 'invalid_batch';
            }

            if ((int) $assignedBatch['university_id'] !== $newUniversityId) {
                $pdo->rollBack();
                return 'university_mismatch';
            }
        }

        $password = (string) ($data['password'] ?? '');
        if ($password !== '') {
            db_query(
                'UPDATE users
                 SET name = ?,
                     email = ?,
                     academic_year = ?,
                     university_id = ?,
                     batch_id = ?,
                     password = ?
                 WHERE id = ? AND role = ?',
                [
                    $data['name'],
                    $data['email'],
                    (int) $data['academic_year'],
                    $newUniversityId,
                    $newBatchId,
                    password_hash($password, PASSWORD_DEFAULT),
                    $moderatorId,
                    'moderator',
                ]
            );
        } else {
            db_query(
                'UPDATE users
                 SET name = ?,
                     email = ?,
                     academic_year = ?,
                     university_id = ?,
                     batch_id = ?
                 WHERE id = ? AND role = ?',
                [
                    $data['name'],
                    $data['email'],
                    (int) $data['academic_year'],
                    $newUniversityId,
                    $newBatchId,
                    $moderatorId,
                    'moderator',
                ]
            );
        }

        $pdo->commit();
        return 'updated';
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

function moderators_delete_admin(int $moderatorId): string
{
    $pdo = db_connect();
    $pdo->beginTransaction();

    try {
        $moderator = db_fetch(
            'SELECT id FROM users WHERE id = ? AND role = ? FOR UPDATE',
            [$moderatorId, 'moderator']
        );

        if (!$moderator) {
            $pdo->rollBack();
            return 'not_found';
        }

        $ownedBatch = db_fetch(
            'SELECT id FROM batches WHERE moderator_user_id = ? LIMIT 1 FOR UPDATE',
            [$moderatorId]
        );

        if ($ownedBatch) {
            $pdo->rollBack();
            return 'has_owned_batch';
        }

        $deleted = db_query('DELETE FROM users WHERE id = ? AND role = ?', [$moderatorId, 'moderator'])->rowCount();
        if ($deleted < 1) {
            $pdo->rollBack();
            return 'not_found';
        }

        $pdo->commit();
        return 'deleted';
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}
