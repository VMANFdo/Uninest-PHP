<?php

/**
 * Topics Module — Models
 */

function topics_all_for_subject(int $subjectId): array
{
    return db_fetch_all(
        "SELECT t.*, u.name AS creator_name
         FROM topics t
         LEFT JOIN users u ON u.id = t.created_by
         WHERE t.subject_id = ?
         ORDER BY t.sort_order ASC, t.id ASC",
        [$subjectId]
    );
}

function topics_find_in_subject(int $topicId, int $subjectId): ?array
{
    return db_fetch(
        "SELECT t.*, u.name AS creator_name
         FROM topics t
         LEFT JOIN users u ON u.id = t.created_by
         WHERE t.id = ?
           AND t.subject_id = ?
         LIMIT 1",
        [$topicId, $subjectId]
    );
}

function topics_next_sort_order(int $subjectId): int
{
    $row = db_fetch(
        'SELECT COALESCE(MAX(sort_order), 0) AS max_sort_order FROM topics WHERE subject_id = ?',
        [$subjectId]
    );

    return max(1, ((int) ($row['max_sort_order'] ?? 0)) + 1);
}

function topics_create(int $subjectId, array $data): string
{
    $description = trim((string) ($data['description'] ?? ''));

    return db_insert('topics', [
        'subject_id' => $subjectId,
        'title' => $data['title'],
        'description' => $description === '' ? null : $description,
        'sort_order' => (int) ($data['sort_order'] ?? 1),
        'created_by' => auth_id(),
    ]);
}

function topics_update_data(int $topicId, int $subjectId, array $data): int
{
    $description = trim((string) ($data['description'] ?? ''));

    return db_query(
        "UPDATE topics
         SET title = ?,
             description = ?,
             sort_order = ?
         WHERE id = ?
           AND subject_id = ?",
        [
            $data['title'],
            $description === '' ? null : $description,
            (int) ($data['sort_order'] ?? 1),
            $topicId,
            $subjectId,
        ]
    )->rowCount();
}

function topics_delete_by_id(int $topicId, int $subjectId): int
{
    $pdo = db_connect();
    $pdo->beginTransaction();

    try {
        resources_delete_comments_for_topic($topicId);

        $deleted = db_query(
            'DELETE FROM topics WHERE id = ? AND subject_id = ?',
            [$topicId, $subjectId]
        )->rowCount();

        $pdo->commit();
        return $deleted;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}
