<?php

/**
 * Subjects Module — Models
 */

function subjects_all_admin(): array
{
    return db_fetch_all(
        "SELECT s.*, b.batch_code, b.name AS batch_name, u.name AS creator_name
         FROM subjects s
         INNER JOIN batches b ON b.id = s.batch_id
         LEFT JOIN users u ON u.id = s.created_by
         ORDER BY s.name ASC"
    );
}

function subjects_all_for_batch(int $batchId): array
{
    return db_fetch_all(
        "SELECT s.*, b.batch_code, b.name AS batch_name, u.name AS creator_name
         FROM subjects s
         INNER JOIN batches b ON b.id = s.batch_id
         LEFT JOIN users u ON u.id = s.created_by
         WHERE s.batch_id = ?
         ORDER BY s.name ASC",
        [$batchId]
    );
}

function subjects_find_admin(int $id): ?array
{
    return db_fetch(
        "SELECT s.*, b.batch_code, b.name AS batch_name
         FROM subjects s
         INNER JOIN batches b ON b.id = s.batch_id
         WHERE s.id = ?",
        [$id]
    );
}

function subjects_find_for_batch(int $id, int $batchId): ?array
{
    return db_fetch(
        "SELECT s.*, b.batch_code, b.name AS batch_name
         FROM subjects s
         INNER JOIN batches b ON b.id = s.batch_id
         WHERE s.id = ? AND s.batch_id = ?",
        [$id, $batchId]
    );
}

function subjects_code_exists_in_batch(string $code, int $batchId, ?int $excludeId = null): bool
{
    if ($excludeId !== null) {
        return (bool) db_fetch(
            'SELECT id FROM subjects WHERE code = ? AND batch_id = ? AND id != ?',
            [$code, $batchId, $excludeId]
        );
    }

    return (bool) db_fetch('SELECT id FROM subjects WHERE code = ? AND batch_id = ?', [$code, $batchId]);
}

function subjects_create(array $data): string
{
    return db_insert('subjects', [
        'batch_id'    => (int) $data['batch_id'],
        'code'        => $data['code'],
        'name'        => $data['name'],
        'description' => $data['description'] ?? '',
        'credits'     => (int) ($data['credits'] ?? 3),
        'created_by'  => $data['created_by'] ?? auth_id(),
    ]);
}

function subjects_update_data(int $id, array $data): int
{
    return db_update('subjects', [
        'code'        => $data['code'],
        'name'        => $data['name'],
        'description' => $data['description'] ?? '',
        'credits'     => (int) ($data['credits'] ?? 3),
        'batch_id'    => (int) $data['batch_id'],
    ], ['id' => $id]);
}

function subjects_delete_by_id(int $id): int
{
    return db_delete('subjects', ['id' => $id]);
}
