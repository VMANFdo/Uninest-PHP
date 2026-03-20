<?php

/**
 * Subjects Module — Models
 */

function subjects_all(): array
{
    return db_fetch_all('SELECT s.*, u.name as creator_name FROM subjects s LEFT JOIN users u ON s.created_by = u.id ORDER BY s.name ASC');
}

function subjects_find(int $id): ?array
{
    return db_fetch('SELECT s.*, u.name as creator_name FROM subjects s LEFT JOIN users u ON s.created_by = u.id WHERE s.id = ?', [$id]);
}

function subjects_create(array $data): string
{
    return db_insert('subjects', [
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
    ], ['id' => $id]);
}

function subjects_delete_by_id(int $id): int
{
    return db_delete('subjects', ['id' => $id]);
}
