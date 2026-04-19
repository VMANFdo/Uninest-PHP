<?php

/**
 * Kuppi Module — University Timetable Models
 */

function kuppi_university_timetable_slots_for_batch(int $batchId): array
{
    if ($batchId <= 0) {
        return [];
    }

    return db_fetch_all(
        "SELECT s.*,
                creator.name AS created_by_name,
                updater.name AS updated_by_name
         FROM kuppi_university_timetable_slots s
         LEFT JOIN users creator ON creator.id = s.created_by_user_id
         LEFT JOIN users updater ON updater.id = s.updated_by_user_id
         WHERE s.batch_id = ?
         ORDER BY s.day_of_week ASC, s.start_time ASC, s.id ASC",
        [$batchId]
    );
}

function kuppi_university_timetable_find_by_id(int $slotId): ?array
{
    if ($slotId <= 0) {
        return null;
    }

    return db_fetch(
        "SELECT s.*,
                creator.name AS created_by_name,
                updater.name AS updated_by_name
         FROM kuppi_university_timetable_slots s
         LEFT JOIN users creator ON creator.id = s.created_by_user_id
         LEFT JOIN users updater ON updater.id = s.updated_by_user_id
         WHERE s.id = ?
         LIMIT 1",
        [$slotId]
    );
}

function kuppi_university_timetable_find_for_batch(int $slotId, int $batchId): ?array
{
    if ($slotId <= 0 || $batchId <= 0) {
        return null;
    }

    return db_fetch(
        "SELECT s.*,
                creator.name AS created_by_name,
                updater.name AS updated_by_name
         FROM kuppi_university_timetable_slots s
         LEFT JOIN users creator ON creator.id = s.created_by_user_id
         LEFT JOIN users updater ON updater.id = s.updated_by_user_id
         WHERE s.id = ?
           AND s.batch_id = ?
         LIMIT 1",
        [$slotId, $batchId]
    );
}

function kuppi_university_timetable_conflicts_for_range(
    int $batchId,
    int $dayOfWeek,
    string $startTime,
    string $endTime,
    ?int $excludeSlotId = null
): array {
    if ($batchId <= 0 || $dayOfWeek < 1 || $dayOfWeek > 7) {
        return [];
    }

    $params = [$batchId, $dayOfWeek, $endTime, $startTime];
    $excludeSql = '';
    if ($excludeSlotId !== null && $excludeSlotId > 0) {
        $excludeSql = ' AND id <> ?';
        $params[] = $excludeSlotId;
    }

    return db_fetch_all(
        "SELECT id,
                batch_id,
                day_of_week,
                start_time,
                end_time,
                reason
         FROM kuppi_university_timetable_slots
         WHERE batch_id = ?
           AND day_of_week = ?
           AND start_time < ?
           AND end_time > ?
           {$excludeSql}
         ORDER BY start_time ASC, id ASC",
        $params
    );
}

function kuppi_university_timetable_has_overlap(
    int $batchId,
    int $dayOfWeek,
    string $startTime,
    string $endTime,
    ?int $excludeSlotId = null
): bool {
    return !empty(kuppi_university_timetable_conflicts_for_range(
        $batchId,
        $dayOfWeek,
        $startTime,
        $endTime,
        $excludeSlotId
    ));
}

function kuppi_university_timetable_conflicts_for_session(
    int $batchId,
    string $sessionDate,
    string $startTime,
    string $endTime
): array {
    if (
        $batchId <= 0
        || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $sessionDate)
        || strtotime($sessionDate . ' 00:00:00') === false
    ) {
        return [];
    }

    $normalizedStart = preg_match('/^\d{2}:\d{2}$/', $startTime) ? ($startTime . ':00') : $startTime;
    $normalizedEnd = preg_match('/^\d{2}:\d{2}$/', $endTime) ? ($endTime . ':00') : $endTime;

    if (!preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $normalizedStart) || !preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $normalizedEnd)) {
        return [];
    }

    $dayTs = strtotime($sessionDate . ' 00:00:00');
    if ($dayTs === false) {
        return [];
    }

    return kuppi_university_timetable_conflicts_for_range(
        $batchId,
        (int) date('N', $dayTs),
        $normalizedStart,
        $normalizedEnd
    );
}

function kuppi_university_timetable_create(array $data): int
{
    return (int) db_insert('kuppi_university_timetable_slots', [
        'batch_id' => (int) $data['batch_id'],
        'day_of_week' => (int) $data['day_of_week'],
        'start_time' => $data['start_time'],
        'end_time' => $data['end_time'],
        'reason' => $data['reason'] !== '' ? $data['reason'] : null,
        'created_by_user_id' => (int) $data['created_by_user_id'],
        'updated_by_user_id' => (int) $data['updated_by_user_id'],
    ]);
}

function kuppi_university_timetable_update(int $slotId, int $batchId, array $data): bool
{
    if ($slotId <= 0 || $batchId <= 0) {
        return false;
    }

    $stmt = db_query(
        "UPDATE kuppi_university_timetable_slots
         SET day_of_week = ?,
             start_time = ?,
             end_time = ?,
             reason = ?,
             updated_by_user_id = ?,
             updated_at = NOW()
         WHERE id = ?
           AND batch_id = ?",
        [
            (int) $data['day_of_week'],
            $data['start_time'],
            $data['end_time'],
            $data['reason'] !== '' ? $data['reason'] : null,
            (int) $data['updated_by_user_id'],
            $slotId,
            $batchId,
        ]
    );

    if ($stmt->rowCount() > 0) {
        return true;
    }

    return (bool) db_fetch(
        'SELECT id FROM kuppi_university_timetable_slots WHERE id = ? AND batch_id = ? LIMIT 1',
        [$slotId, $batchId]
    );
}

function kuppi_university_timetable_delete(int $slotId, int $batchId): bool
{
    if ($slotId <= 0 || $batchId <= 0) {
        return false;
    }

    $stmt = db_query(
        'DELETE FROM kuppi_university_timetable_slots WHERE id = ? AND batch_id = ?',
        [$slotId, $batchId]
    );

    return $stmt->rowCount() > 0;
}

