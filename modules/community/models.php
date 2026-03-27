<?php

/**
 * Community Module — Models
 */

function community_post_types(): array
{
    return ['general', 'discussion', 'question', 'announcement', 'resource_share'];
}

function community_post_type_label(string $postType): string
{
    return match ($postType) {
        'general' => 'General',
        'discussion' => 'Discussion',
        'question' => 'Question',
        'announcement' => 'Announcement',
        'resource_share' => 'Resource Share',
        default => 'General',
    };
}

function community_allowed_image_extensions(): array
{
    return ['jpg', 'jpeg', 'png', 'webp', 'gif'];
}

function community_batch_options_for_admin(): array
{
    return onboarding_approved_batches();
}

function community_find_batch_option_by_id(int $batchId): ?array
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

function community_subject_options_for_batch(int $batchId): array
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

function community_subject_exists_in_batch(int $subjectId, int $batchId): bool
{
    if ($subjectId <= 0 || $batchId <= 0) {
        return false;
    }

    return (bool) db_fetch(
        'SELECT id FROM subjects WHERE id = ? AND batch_id = ? LIMIT 1',
        [$subjectId, $batchId]
    );
}

function community_posts_for_batch(
    int $batchId,
    ?int $subjectId,
    ?string $postType,
    string $sortBy,
    int $viewerUserId
): array
{
    if ($batchId <= 0) {
        return [];
    }

    $params = [$viewerUserId, $batchId];
    $subjectSql = '';
    if ($subjectId !== null && $subjectId > 0) {
        $subjectSql = ' AND p.subject_id = ?';
        $params[] = $subjectId;
    }

    $postTypeSql = '';
    if ($postType !== null && $postType !== '' && in_array($postType, community_post_types(), true)) {
        $postTypeSql = ' AND p.post_type = ?';
        $params[] = $postType;
    }

    $orderBySql = $sortBy === 'top'
        ? 'COALESCE(lc.like_count, 0) DESC, COALESCE(cc.comment_count, 0) DESC, p.created_at DESC, p.id DESC'
        : 'p.created_at DESC, p.id DESC';

    return db_fetch_all(
        "SELECT p.*,
                u.name AS author_name,
                b.batch_code,
                b.name AS batch_name,
                s.code AS subject_code,
                s.name AS subject_name,
                COALESCE(lc.like_count, 0) AS like_count,
                COALESCE(cc.comment_count, 0) AS comment_count,
                CASE WHEN ul.id IS NULL THEN 0 ELSE 1 END AS is_liked_by_viewer
         FROM feed_posts p
         INNER JOIN batches b ON b.id = p.batch_id
         LEFT JOIN users u ON u.id = p.author_user_id
         LEFT JOIN subjects s ON s.id = p.subject_id
         LEFT JOIN (
            SELECT post_id, COUNT(*) AS like_count
            FROM feed_post_likes
            GROUP BY post_id
         ) lc ON lc.post_id = p.id
         LEFT JOIN (
            SELECT target_id AS post_id, COUNT(*) AS comment_count
            FROM comments
            WHERE target_type = 'feed_post'
            GROUP BY target_id
         ) cc ON cc.post_id = p.id
         LEFT JOIN feed_post_likes ul
                ON ul.post_id = p.id
               AND ul.user_id = ?
         WHERE p.batch_id = ?{$subjectSql}
               {$postTypeSql}
         ORDER BY {$orderBySql}",
        $params
    );
}

function community_post_type_counts_for_batch(int $batchId, ?int $subjectId = null): array
{
    if ($batchId <= 0) {
        return [];
    }

    $params = [$batchId];
    $subjectSql = '';
    if ($subjectId !== null && $subjectId > 0) {
        $subjectSql = ' AND subject_id = ?';
        $params[] = $subjectId;
    }

    $rows = db_fetch_all(
        "SELECT post_type, COUNT(*) AS cnt
         FROM feed_posts
         WHERE batch_id = ?{$subjectSql}
         GROUP BY post_type",
        $params
    );

    $counts = ['_all' => 0];
    foreach (community_post_types() as $type) {
        $counts[$type] = 0;
    }

    foreach ($rows as $row) {
        $type = (string) ($row['post_type'] ?? '');
        $count = (int) ($row['cnt'] ?? 0);
        if (isset($counts[$type])) {
            $counts[$type] = $count;
            $counts['_all'] += $count;
        }
    }

    return $counts;
}

function community_popular_posts_for_batch(
    int $batchId,
    ?int $subjectId,
    int $viewerUserId,
    int $limit = 3
): array {
    if ($batchId <= 0) {
        return [];
    }

    $limit = max(1, min(10, $limit));
    $params = [$viewerUserId, $batchId];
    $subjectSql = '';
    if ($subjectId !== null && $subjectId > 0) {
        $subjectSql = ' AND p.subject_id = ?';
        $params[] = $subjectId;
    }

    return db_fetch_all(
        "SELECT p.*,
                u.name AS author_name,
                s.code AS subject_code,
                s.name AS subject_name,
                COALESCE(lc.like_count, 0) AS like_count,
                COALESCE(cc.comment_count, 0) AS comment_count,
                CASE WHEN ul.id IS NULL THEN 0 ELSE 1 END AS is_liked_by_viewer
         FROM feed_posts p
         LEFT JOIN users u ON u.id = p.author_user_id
         LEFT JOIN subjects s ON s.id = p.subject_id
         LEFT JOIN (
            SELECT post_id, COUNT(*) AS like_count
            FROM feed_post_likes
            GROUP BY post_id
         ) lc ON lc.post_id = p.id
         LEFT JOIN (
            SELECT target_id AS post_id, COUNT(*) AS comment_count
            FROM comments
            WHERE target_type = 'feed_post'
            GROUP BY target_id
         ) cc ON cc.post_id = p.id
         LEFT JOIN feed_post_likes ul
                ON ul.post_id = p.id
               AND ul.user_id = ?
         WHERE p.batch_id = ?{$subjectSql}
         ORDER BY COALESCE(lc.like_count, 0) DESC, COALESCE(cc.comment_count, 0) DESC, p.created_at DESC, p.id DESC
         LIMIT {$limit}",
        $params
    );
}

function community_find_post_for_batch(int $postId, int $batchId, int $viewerUserId): ?array
{
    if ($postId <= 0 || $batchId <= 0) {
        return null;
    }

    return db_fetch(
        "SELECT p.*,
                u.name AS author_name,
                b.batch_code,
                b.name AS batch_name,
                s.code AS subject_code,
                s.name AS subject_name,
                COALESCE(lc.like_count, 0) AS like_count,
                COALESCE(cc.comment_count, 0) AS comment_count,
                CASE WHEN ul.id IS NULL THEN 0 ELSE 1 END AS is_liked_by_viewer
         FROM feed_posts p
         INNER JOIN batches b ON b.id = p.batch_id
         LEFT JOIN users u ON u.id = p.author_user_id
         LEFT JOIN subjects s ON s.id = p.subject_id
         LEFT JOIN (
            SELECT post_id, COUNT(*) AS like_count
            FROM feed_post_likes
            GROUP BY post_id
         ) lc ON lc.post_id = p.id
         LEFT JOIN (
            SELECT target_id AS post_id, COUNT(*) AS comment_count
            FROM comments
            WHERE target_type = 'feed_post'
            GROUP BY target_id
         ) cc ON cc.post_id = p.id
         LEFT JOIN feed_post_likes ul
                ON ul.post_id = p.id
               AND ul.user_id = ?
         WHERE p.id = ?
           AND p.batch_id = ?
         LIMIT 1",
        [$viewerUserId, $postId, $batchId]
    );
}

function community_find_post_admin(int $postId, int $viewerUserId): ?array
{
    if ($postId <= 0) {
        return null;
    }

    return db_fetch(
        "SELECT p.*,
                u.name AS author_name,
                b.batch_code,
                b.name AS batch_name,
                s.code AS subject_code,
                s.name AS subject_name,
                COALESCE(lc.like_count, 0) AS like_count,
                COALESCE(cc.comment_count, 0) AS comment_count,
                CASE WHEN ul.id IS NULL THEN 0 ELSE 1 END AS is_liked_by_viewer
         FROM feed_posts p
         INNER JOIN batches b ON b.id = p.batch_id
         LEFT JOIN users u ON u.id = p.author_user_id
         LEFT JOIN subjects s ON s.id = p.subject_id
         LEFT JOIN (
            SELECT post_id, COUNT(*) AS like_count
            FROM feed_post_likes
            GROUP BY post_id
         ) lc ON lc.post_id = p.id
         LEFT JOIN (
            SELECT target_id AS post_id, COUNT(*) AS comment_count
            FROM comments
            WHERE target_type = 'feed_post'
            GROUP BY target_id
         ) cc ON cc.post_id = p.id
         LEFT JOIN feed_post_likes ul
                ON ul.post_id = p.id
               AND ul.user_id = ?
         WHERE p.id = ?
         LIMIT 1",
        [$viewerUserId, $postId]
    );
}

function community_find_owned_post(int $postId, int $ownerUserId): ?array
{
    if ($postId <= 0 || $ownerUserId <= 0) {
        return null;
    }

    return db_fetch(
        "SELECT p.*,
                b.batch_code,
                b.name AS batch_name,
                s.code AS subject_code,
                s.name AS subject_name
         FROM feed_posts p
         INNER JOIN batches b ON b.id = p.batch_id
         LEFT JOIN subjects s ON s.id = p.subject_id
         WHERE p.id = ?
           AND p.author_user_id = ?
         LIMIT 1",
        [$postId, $ownerUserId]
    );
}

function community_create_post(array $data): int
{
    return (int) db_insert('feed_posts', [
        'batch_id' => (int) $data['batch_id'],
        'subject_id' => $data['subject_id'] !== null ? (int) $data['subject_id'] : null,
        'author_user_id' => $data['author_user_id'] !== null ? (int) $data['author_user_id'] : null,
        'post_type' => $data['post_type'],
        'body' => $data['body'] ?? null,
        'image_path' => $data['image_path'] ?? null,
        'image_name' => $data['image_name'] ?? null,
        'image_mime' => $data['image_mime'] ?? null,
        'image_size' => $data['image_size'] ?? null,
        'edited_at' => $data['edited_at'] ?? null,
    ]);
}

function community_update_post_by_owner(int $postId, int $ownerUserId, array $data): bool
{
    db_query(
        "UPDATE feed_posts
         SET subject_id = ?,
             post_type = ?,
             body = ?,
             image_path = ?,
             image_name = ?,
             image_mime = ?,
             image_size = ?,
             edited_at = NOW()
         WHERE id = ?
           AND author_user_id = ?",
        [
            $data['subject_id'] !== null ? (int) $data['subject_id'] : null,
            $data['post_type'],
            $data['body'] ?? null,
            $data['image_path'] ?? null,
            $data['image_name'] ?? null,
            $data['image_mime'] ?? null,
            $data['image_size'] ?? null,
            $postId,
            $ownerUserId,
        ]
    );

    return true;
}

function community_my_posts(int $ownerUserId): array
{
    if ($ownerUserId <= 0) {
        return [];
    }

    return db_fetch_all(
        "SELECT p.*,
                b.batch_code,
                b.name AS batch_name,
                s.code AS subject_code,
                s.name AS subject_name,
                COALESCE(lc.like_count, 0) AS like_count,
                COALESCE(cc.comment_count, 0) AS comment_count
         FROM feed_posts p
         INNER JOIN batches b ON b.id = p.batch_id
         LEFT JOIN subjects s ON s.id = p.subject_id
         LEFT JOIN (
            SELECT post_id, COUNT(*) AS like_count
            FROM feed_post_likes
            GROUP BY post_id
         ) lc ON lc.post_id = p.id
         LEFT JOIN (
            SELECT target_id AS post_id, COUNT(*) AS comment_count
            FROM comments
            WHERE target_type = 'feed_post'
            GROUP BY target_id
         ) cc ON cc.post_id = p.id
         WHERE p.author_user_id = ?
         ORDER BY p.updated_at DESC, p.id DESC",
        [$ownerUserId]
    );
}

function community_toggle_like(int $postId, int $userId): bool
{
    if ($postId <= 0 || $userId <= 0) {
        return false;
    }

    $existing = db_fetch(
        'SELECT id FROM feed_post_likes WHERE post_id = ? AND user_id = ? LIMIT 1',
        [$postId, $userId]
    );

    if ($existing) {
        db_query(
            'DELETE FROM feed_post_likes WHERE post_id = ? AND user_id = ?',
            [$postId, $userId]
        );
        return false;
    }

    try {
        db_insert('feed_post_likes', [
            'post_id' => $postId,
            'user_id' => $userId,
        ]);
    } catch (Throwable) {
        return true;
    }

    return true;
}

function community_post_ids_for_batch(int $batchId): array
{
    if ($batchId <= 0) {
        return [];
    }

    $rows = db_fetch_all(
        'SELECT id FROM feed_posts WHERE batch_id = ?',
        [$batchId]
    );

    return array_values(array_map(static fn(array $row): int => (int) $row['id'], $rows));
}

function community_delete_comments_for_post_ids(array $postIds): int
{
    return comments_delete_for_target_ids('feed_post', $postIds);
}

function community_delete_comments_for_post(int $postId): int
{
    return comments_delete_for_target('feed_post', $postId);
}

function community_delete_comments_for_batch(int $batchId): int
{
    return community_delete_comments_for_post_ids(community_post_ids_for_batch($batchId));
}

function community_delete_post_by_id(int $postId): bool
{
    if ($postId <= 0) {
        return false;
    }

    $pdo = db_connect();
    $pdo->beginTransaction();

    try {
        community_delete_comments_for_post($postId);
        $deleted = db_query('DELETE FROM feed_posts WHERE id = ?', [$postId])->rowCount() > 0;
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
