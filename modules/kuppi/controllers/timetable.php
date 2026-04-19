<?php

/**
 * Kuppi Module — Controllers (University timetable)
 */

function kuppi_timetable_day_labels(): array
{
    return [
        1 => 'Monday',
        2 => 'Tuesday',
        3 => 'Wednesday',
        4 => 'Thursday',
        5 => 'Friday',
        6 => 'Saturday',
        7 => 'Sunday',
    ];
}

function kuppi_timetable_day_label(int $dayOfWeek): string
{
    $labels = kuppi_timetable_day_labels();
    return (string) ($labels[$dayOfWeek] ?? 'Unknown Day');
}

function kuppi_timetable_normalize_time_input(string $value): ?string
{
    $value = trim($value);
    if (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $value)) {
        return null;
    }

    return $value . ':00';
}

function kuppi_timetable_time_to_minutes(string $timeValue): ?int
{
    if (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/', $timeValue)) {
        return null;
    }

    $hours = (int) substr($timeValue, 0, 2);
    $minutes = (int) substr($timeValue, 3, 2);
    return ($hours * 60) + $minutes;
}

function kuppi_timetable_time_label(string $timeValue): string
{
    $trimmed = trim($timeValue);
    if (preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/', $trimmed)) {
        return substr($trimmed, 0, 5);
    }

    return $trimmed;
}

function kuppi_timetable_day_of_week_from_date(string $sessionDate): int
{
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $sessionDate)) {
        return 0;
    }

    $dayTs = strtotime($sessionDate . ' 00:00:00');
    if ($dayTs === false) {
        return 0;
    }

    return (int) date('N', $dayTs);
}

function kuppi_timetable_reason_label(array $slot): string
{
    $reason = trim((string) ($slot['reason'] ?? ''));
    return $reason !== '' ? $reason : 'Official lecture slot';
}

function kuppi_timetable_slot_summary(array $slot): string
{
    $dayLabel = kuppi_timetable_day_label((int) ($slot['day_of_week'] ?? 0));
    $startLabel = kuppi_timetable_time_label((string) ($slot['start_time'] ?? ''));
    $endLabel = kuppi_timetable_time_label((string) ($slot['end_time'] ?? ''));
    $reason = kuppi_timetable_reason_label($slot);

    $range = trim($startLabel . ' - ' . $endLabel);
    if ($range === '-') {
        $range = 'Unknown time';
    }

    return $dayLabel . ' ' . $range . ' (' . $reason . ')';
}

function kuppi_user_can_view_timetable_for_batch(int $batchId): bool
{
    $role = (string) user_role();
    if (!in_array($role, ['student', 'coordinator', 'moderator', 'admin'], true)) {
        return false;
    }

    return kuppi_user_can_read_batch($batchId);
}

function kuppi_user_can_manage_timetable_for_batch(int $batchId): bool
{
    if ($batchId <= 0) {
        return false;
    }

    $role = (string) user_role();
    if ($role === 'admin') {
        return true;
    }

    if ($role !== 'moderator') {
        return false;
    }

    return (int) (auth_user()['batch_id'] ?? 0) === $batchId;
}

function kuppi_timetable_url(int $batchId = 0, array $extraQuery = []): string
{
    $query = [];
    if ((string) user_role() === 'admin' && $batchId > 0) {
        $query['batch_id'] = $batchId;
    }

    foreach ($extraQuery as $key => $value) {
        if ($value === null || $value === '') {
            continue;
        }
        $query[$key] = $value;
    }

    return '/dashboard/kuppi/timetable' . (!empty($query) ? '?' . http_build_query($query) : '');
}

function kuppi_timetable_validate_slot_input(int $batchId, ?int $excludeSlotId = null): array
{
    $errors = [];
    $dayOfWeek = (int) request_input('day_of_week', 0);
    $startTimeRaw = trim((string) request_input('start_time', ''));
    $endTimeRaw = trim((string) request_input('end_time', ''));
    $reason = trim((string) request_input('reason', ''));

    if ($batchId <= 0) {
        $errors[] = 'Batch is required.';
    }

    if ($dayOfWeek < 1 || $dayOfWeek > 7) {
        $errors[] = 'Valid day of week is required.';
    }

    $startTime = kuppi_timetable_normalize_time_input($startTimeRaw);
    if ($startTime === null) {
        $errors[] = 'Valid start time is required.';
    }

    $endTime = kuppi_timetable_normalize_time_input($endTimeRaw);
    if ($endTime === null) {
        $errors[] = 'Valid end time is required.';
    }

    if ($reason !== '' && strlen($reason) > 255) {
        $errors[] = 'Reason must be at most 255 characters.';
    }

    if ($startTime !== null && $endTime !== null) {
        $startMinutes = kuppi_timetable_time_to_minutes($startTime);
        $endMinutes = kuppi_timetable_time_to_minutes($endTime);
        if ($startMinutes === null || $endMinutes === null || $endMinutes <= $startMinutes) {
            $errors[] = 'End time must be after start time.';
        }
    }

    if (empty($errors) && kuppi_university_timetable_has_overlap($batchId, $dayOfWeek, (string) $startTime, (string) $endTime, $excludeSlotId)) {
        $errors[] = 'This slot overlaps an existing official lecture slot for the selected day.';
    }

    return [
        'errors' => $errors,
        'data' => [
            'batch_id' => $batchId,
            'day_of_week' => $dayOfWeek,
            'start_time' => (string) $startTime,
            'end_time' => (string) $endTime,
            'reason' => $reason,
        ],
    ];
}

function kuppi_timetable_build_weekly_grid(array $slots): array
{
    $rows = [];
    $blockedCellCount = 0;
    $slotsByDay = [];
    foreach ($slots as $slot) {
        $day = (int) ($slot['day_of_week'] ?? 0);
        if ($day < 1 || $day > 7) {
            continue;
        }
        $slotsByDay[$day][] = $slot;
    }

    for ($hour = 8; $hour < 21; $hour++) {
        $row = [
            'start_hour' => $hour,
            'time_label' => sprintf('%02d:00 - %02d:00', $hour, $hour + 1),
            'cells' => [],
        ];

        $rowStart = $hour * 60;
        $rowEnd = ($hour + 1) * 60;

        foreach (range(1, 7) as $dayOfWeek) {
            $matched = null;
            foreach ((array) ($slotsByDay[$dayOfWeek] ?? []) as $slot) {
                $slotStart = kuppi_timetable_time_to_minutes((string) ($slot['start_time'] ?? ''));
                $slotEnd = kuppi_timetable_time_to_minutes((string) ($slot['end_time'] ?? ''));
                if ($slotStart === null || $slotEnd === null) {
                    continue;
                }

                if ($slotStart < $rowEnd && $slotEnd > $rowStart) {
                    $matched = $slot;
                    break;
                }
            }

            if ($matched !== null) {
                $blockedCellCount++;
            }

            $row['cells'][$dayOfWeek] = $matched;
        }

        $rows[] = $row;
    }

    return [
        'rows' => $rows,
        'blocked_cell_count' => $blockedCellCount,
    ];
}

function kuppi_timetable_metrics(array $slots, int $blockedCellCount): array
{
    $totalBlockedMinutes = 0;
    foreach ($slots as $slot) {
        $startMinutes = kuppi_timetable_time_to_minutes((string) ($slot['start_time'] ?? ''));
        $endMinutes = kuppi_timetable_time_to_minutes((string) ($slot['end_time'] ?? ''));
        if ($startMinutes === null || $endMinutes === null || $endMinutes <= $startMinutes) {
            continue;
        }
        $totalBlockedMinutes += ($endMinutes - $startMinutes);
    }

    $totalGridCells = 13 * 7;
    $availableCells = max(0, $totalGridCells - max(0, $blockedCellCount));

    return [
        'blocked_slot_count' => count($slots),
        'total_blocked_hours' => $totalBlockedMinutes / 60,
        'available_slot_count' => $availableCells,
    ];
}

function kuppi_timetable_index(): void
{
    $role = (string) user_role();
    $isAdmin = $role === 'admin';
    $batchOptions = $isAdmin ? kuppi_batch_options_for_admin() : [];
    $selectedBatchId = 0;
    $activeBatch = null;

    if ($isAdmin) {
        $selectedBatchId = (int) request_input('batch_id', 0);
        if ($selectedBatchId > 0) {
            $activeBatch = kuppi_find_batch_option_by_id($selectedBatchId);
            if (!$activeBatch) {
                $selectedBatchId = 0;
                $activeBatch = null;
            }
        }
    } else {
        $selectedBatchId = (int) (auth_user()['batch_id'] ?? 0);
        if ($selectedBatchId <= 0) {
            abort(403, 'You are not assigned to a batch.');
        }

        if (!kuppi_user_can_view_timetable_for_batch($selectedBatchId)) {
            abort(403, 'You do not have permission to view this timetable.');
        }

        $activeBatch = kuppi_find_batch_option_by_id($selectedBatchId);
    }

    if ($selectedBatchId > 0 && !kuppi_user_can_view_timetable_for_batch($selectedBatchId)) {
        abort(403, 'You do not have permission to view this timetable.');
    }

    $canManage = $selectedBatchId > 0 && kuppi_user_can_manage_timetable_for_batch($selectedBatchId);
    $slots = $selectedBatchId > 0
        ? kuppi_university_timetable_slots_for_batch($selectedBatchId)
        : [];

    $editSlot = null;
    $editSlotId = (int) request_input('edit', 0);
    if ($canManage && $editSlotId > 0) {
        $editSlot = kuppi_university_timetable_find_for_batch($editSlotId, $selectedBatchId);
        if (!$editSlot) {
            flash('warning', 'Selected slot was not found for this batch.');
            redirect(kuppi_timetable_url($selectedBatchId));
        }
    }

    $weeklyGridData = kuppi_timetable_build_weekly_grid($slots);
    $metrics = kuppi_timetable_metrics($slots, (int) ($weeklyGridData['blocked_cell_count'] ?? 0));

    view('kuppi::timetable', [
        'is_admin' => $isAdmin,
        'batch_options' => $batchOptions,
        'selected_batch_id' => $selectedBatchId,
        'active_batch' => $activeBatch,
        'can_manage' => $canManage,
        'is_read_only' => !$canManage,
        'slots' => $slots,
        'edit_slot' => $editSlot,
        'day_labels' => kuppi_timetable_day_labels(),
        'grid_rows' => (array) ($weeklyGridData['rows'] ?? []),
        'metrics' => $metrics,
    ], 'dashboard');
}

function kuppi_timetable_store(): void
{
    csrf_check();

    $role = (string) user_role();
    if (!in_array($role, ['moderator', 'admin'], true)) {
        abort(403, 'Only moderators and admins can manage official timetable slots.');
    }

    $batchId = $role === 'admin'
        ? (int) request_input('batch_id', 0)
        : (int) (auth_user()['batch_id'] ?? 0);
    if ($batchId <= 0) {
        flash('error', 'Select a batch before adding timetable slots.');
        redirect(kuppi_timetable_url());
    }

    if (!kuppi_user_can_manage_timetable_for_batch($batchId)) {
        abort(403, 'You do not have permission to manage this timetable.');
    }

    if ($role === 'admin' && !kuppi_find_batch_option_by_id($batchId)) {
        flash('error', 'Selected batch is not available.');
        redirect(kuppi_timetable_url());
    }

    $validated = kuppi_timetable_validate_slot_input($batchId);
    if (!empty($validated['errors'])) {
        flash('error', implode(' ', $validated['errors']));
        flash_old_input();
        redirect(kuppi_timetable_url($batchId));
    }

    try {
        kuppi_university_timetable_create([
            'batch_id' => $batchId,
            'day_of_week' => (int) $validated['data']['day_of_week'],
            'start_time' => (string) $validated['data']['start_time'],
            'end_time' => (string) $validated['data']['end_time'],
            'reason' => (string) $validated['data']['reason'],
            'created_by_user_id' => (int) auth_id(),
            'updated_by_user_id' => (int) auth_id(),
        ]);
    } catch (Throwable) {
        flash('error', 'Unable to add the timetable slot right now.');
        redirect(kuppi_timetable_url($batchId));
    }

    clear_old_input();
    flash('success', 'Official timetable slot added.');
    redirect(kuppi_timetable_url($batchId));
}

function kuppi_timetable_update(string $id): void
{
    csrf_check();

    $role = (string) user_role();
    if (!in_array($role, ['moderator', 'admin'], true)) {
        abort(403, 'Only moderators and admins can manage official timetable slots.');
    }

    $slotId = (int) $id;
    $slot = kuppi_university_timetable_find_by_id($slotId);
    if (!$slot) {
        abort(404, 'Timetable slot not found.');
    }

    $batchId = (int) ($slot['batch_id'] ?? 0);
    if (!kuppi_user_can_manage_timetable_for_batch($batchId)) {
        abort(403, 'You do not have permission to update this timetable slot.');
    }
    if ($role === 'admin') {
        $contextBatchId = (int) request_input('batch_id', 0);
        if ($contextBatchId <= 0 || $contextBatchId !== $batchId) {
            abort(403, 'Select the correct batch context before updating this slot.');
        }
    }

    $validated = kuppi_timetable_validate_slot_input($batchId, $slotId);
    if (!empty($validated['errors'])) {
        flash('error', implode(' ', $validated['errors']));
        flash_old_input();
        redirect(kuppi_timetable_url($batchId, ['edit' => $slotId]));
    }

    try {
        $updated = kuppi_university_timetable_update($slotId, $batchId, [
            'day_of_week' => (int) $validated['data']['day_of_week'],
            'start_time' => (string) $validated['data']['start_time'],
            'end_time' => (string) $validated['data']['end_time'],
            'reason' => (string) $validated['data']['reason'],
            'updated_by_user_id' => (int) auth_id(),
        ]);
    } catch (Throwable) {
        flash('error', 'Unable to update the timetable slot right now.');
        redirect(kuppi_timetable_url($batchId, ['edit' => $slotId]));
    }

    if (!$updated) {
        flash('error', 'Unable to update this timetable slot.');
        redirect(kuppi_timetable_url($batchId, ['edit' => $slotId]));
    }

    clear_old_input();
    flash('success', 'Official timetable slot updated.');
    redirect(kuppi_timetable_url($batchId));
}

function kuppi_timetable_delete(string $id): void
{
    csrf_check();

    $role = (string) user_role();
    if (!in_array($role, ['moderator', 'admin'], true)) {
        abort(403, 'Only moderators and admins can manage official timetable slots.');
    }

    $slotId = (int) $id;
    $slot = kuppi_university_timetable_find_by_id($slotId);
    if (!$slot) {
        abort(404, 'Timetable slot not found.');
    }

    $batchId = (int) ($slot['batch_id'] ?? 0);
    if (!kuppi_user_can_manage_timetable_for_batch($batchId)) {
        abort(403, 'You do not have permission to delete this timetable slot.');
    }
    if ($role === 'admin') {
        $contextBatchId = (int) request_input('batch_id', 0);
        if ($contextBatchId <= 0 || $contextBatchId !== $batchId) {
            abort(403, 'Select the correct batch context before deleting this slot.');
        }
    }

    try {
        $deleted = kuppi_university_timetable_delete($slotId, $batchId);
    } catch (Throwable) {
        flash('error', 'Unable to delete the timetable slot right now.');
        redirect(kuppi_timetable_url($batchId));
    }

    if (!$deleted) {
        flash('error', 'Unable to delete this timetable slot.');
        redirect(kuppi_timetable_url($batchId));
    }

    flash('success', 'Official timetable slot removed.');
    redirect(kuppi_timetable_url($batchId));
}
