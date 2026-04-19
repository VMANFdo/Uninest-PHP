<?php

/**
 * Kuppi Module — Controllers (Schedule wizard)
 */

function kuppi_schedule_draft_key(): string
{
    return 'kuppi_schedule_draft';
}

function kuppi_schedule_get_draft(): array
{
    $draft = $_SESSION[kuppi_schedule_draft_key()] ?? [];
    return is_array($draft) ? $draft : [];
}

function kuppi_schedule_set_draft(array $draft): void
{
    $_SESSION[kuppi_schedule_draft_key()] = $draft;
}

function kuppi_schedule_clear_draft(): void
{
    unset($_SESSION[kuppi_schedule_draft_key()]);
}

function kuppi_schedule_require_draft(): array
{
    $draft = kuppi_schedule_get_draft();
    if (empty($draft)) {
        flash('warning', 'Start scheduling by selecting a request or manual mode.');
        redirect('/dashboard/kuppi/schedule');
    }
    return $draft;
}

function kuppi_schedule_default_draft(): array
{
    return [
        'mode' => 'request',
        'batch_id' => 0,
        'subject_id' => 0,
        'request_id' => 0,
        'title' => '',
        'description' => '',
        'session_date' => '',
        'start_time' => '',
        'end_time' => '',
        'duration_minutes' => 0,
        'max_attendees' => 25,
        'location_type' => 'physical',
        'location_text' => '',
        'meeting_link' => '',
        'notes' => '',
        'host_user_ids' => [],
    ];
}

function kuppi_schedule_resolve_request_for_draft(array $draft): ?array
{
    $requestId = (int) ($draft['request_id'] ?? 0);
    if ($requestId <= 0) {
        return null;
    }

    $request = kuppi_resolve_readable_request($requestId);
    if (!$request) {
        return null;
    }

    if (!kuppi_user_can_schedule_request($request)) {
        return null;
    }

    return $request;
}

function kuppi_schedule_validate_date_time(
    string $sessionDate,
    string $startTime,
    string $endTime
): array {
    $errors = [];
    $durationMinutes = 0;

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $sessionDate) || strtotime($sessionDate) === false) {
        $errors[] = 'Valid session date is required.';
    } elseif ($sessionDate < date('Y-m-d')) {
        $errors[] = 'Session date cannot be in the past.';
    }

    if (!preg_match('/^\d{2}:\d{2}$/', $startTime)) {
        $errors[] = 'Valid start time is required.';
    }

    if (!preg_match('/^\d{2}:\d{2}$/', $endTime)) {
        $errors[] = 'Valid end time is required.';
    }

    if (empty($errors)) {
        $startTs = strtotime($sessionDate . ' ' . $startTime . ':00');
        $endTs = strtotime($sessionDate . ' ' . $endTime . ':00');
        if ($startTs === false || $endTs === false || $endTs <= $startTs) {
            $errors[] = 'End time must be after start time.';
        } else {
            $durationMinutes = (int) floor(($endTs - $startTs) / 60);
        }
    }

    return [
        'errors' => $errors,
        'duration_minutes' => $durationMinutes,
    ];
}

function kuppi_schedule_validate_set_input(array $draft): array
{
    $role = (string) user_role();
    $currentUser = (array) auth_user();
    $currentUserId = (int) ($currentUser['id'] ?? 0);
    $currentBatchId = (int) ($currentUser['batch_id'] ?? 0);
    $mode = (string) ($draft['mode'] ?? 'request');
    $errors = [];

    $batchId = $mode === 'manual'
        ? ($role === 'admin' ? (int) request_input('batch_id', 0) : $currentBatchId)
        : (int) ($draft['batch_id'] ?? 0);

    $request = null;
    if ($mode === 'request') {
        $request = kuppi_schedule_resolve_request_for_draft($draft);
        if (!$request) {
            $errors[] = 'Selected request is no longer available.';
        } else {
            $requestId = (int) ($request['id'] ?? 0);
            if ($requestId > 0 && kuppi_scheduled_session_has_active_for_request($requestId)) {
                $errors[] = 'This request already has an active scheduled session.';
            }

            if ((string) ($request['status'] ?? '') !== 'open') {
                $errors[] = 'Only open requests can be scheduled.';
            }
        }
    }

    if ($batchId <= 0) {
        $errors[] = 'Batch is required.';
    }

    $subjectId = $mode === 'request'
        ? (int) (($request['subject_id'] ?? $draft['subject_id']) ?? 0)
        : (int) request_input('subject_id', 0);
    if ($subjectId <= 0) {
        $errors[] = 'Subject is required.';
    } elseif (!kuppi_user_can_schedule_subject($subjectId, $batchId)) {
        $errors[] = 'You do not have permission to schedule this subject.';
    }

    $title = $mode === 'request'
        ? (string) (($request['title'] ?? $draft['title']) ?? '')
        : trim((string) request_input('title', ''));
    $description = $mode === 'request'
        ? (string) (($request['description'] ?? $draft['description']) ?? '')
        : trim((string) request_input('description', ''));

    if ($title === '') {
        $errors[] = 'Session title is required.';
    } elseif (strlen($title) > 200) {
        $errors[] = 'Session title must be at most 200 characters.';
    }

    if ($description === '') {
        $errors[] = 'Session description is required.';
    } elseif (strlen($description) > 2000) {
        $errors[] = 'Session description must be at most 2000 characters.';
    }

    $sessionDate = trim((string) request_input('session_date', ''));
    $startTime = trim((string) request_input('start_time', ''));
    $endTime = trim((string) request_input('end_time', ''));
    $timeValidation = kuppi_schedule_validate_date_time($sessionDate, $startTime, $endTime);
    $errors = array_merge($errors, $timeValidation['errors']);
    $durationMinutes = (int) ($timeValidation['duration_minutes'] ?? 0);
    $timetableConflicts = [];

    if ($batchId > 0 && empty($timeValidation['errors'])) {
        $timetableConflicts = kuppi_university_timetable_conflicts_for_session($batchId, $sessionDate, $startTime, $endTime);
        if (!empty($timetableConflicts)) {
            $preview = array_slice(array_map('kuppi_timetable_slot_summary', $timetableConflicts), 0, 2);
            $suffix = count($timetableConflicts) > 2 ? ' and additional blocked slots.' : '.';
            $errors[] = 'Selected session time conflicts with official university lecture slots: '
                . implode('; ', $preview)
                . $suffix;
        }
    }

    $maxAttendees = (int) request_input('max_attendees', 0);
    if ($maxAttendees <= 0 || $maxAttendees > 2000) {
        $errors[] = 'Maximum attendees must be between 1 and 2000.';
    }

    $locationType = trim((string) request_input('location_type', 'physical'));
    if (!in_array($locationType, kuppi_scheduled_location_types(), true)) {
        $errors[] = 'Valid location type is required.';
    }

    $locationText = trim((string) request_input('location_text', ''));
    $meetingLink = trim((string) request_input('meeting_link', ''));
    if ($locationType === 'physical') {
        if ($locationText === '') {
            $errors[] = 'Physical location is required.';
        } elseif (strlen($locationText) > 255) {
            $errors[] = 'Physical location must be at most 255 characters.';
        }
        $meetingLink = '';
    } else {
        if ($meetingLink === '') {
            $errors[] = 'Meeting link is required for online sessions.';
        } elseif (!filter_var($meetingLink, FILTER_VALIDATE_URL)) {
            $errors[] = 'Meeting link must be a valid URL.';
        } elseif (strlen($meetingLink) > 255) {
            $errors[] = 'Meeting link must be at most 255 characters.';
        }
        $locationText = '';
    }

    $notes = trim((string) request_input('notes', ''));
    if (strlen($notes) > 3000) {
        $errors[] = 'Notes must be at most 3000 characters.';
    }

    return [
        'errors' => $errors,
        'data' => [
            'mode' => $mode,
            'batch_id' => $batchId,
            'subject_id' => $subjectId,
            'request_id' => $mode === 'request' ? (int) (($request['id'] ?? $draft['request_id']) ?? 0) : 0,
            'title' => $title,
            'description' => $description,
            'session_date' => $sessionDate,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'duration_minutes' => $durationMinutes,
            'max_attendees' => $maxAttendees,
            'location_type' => $locationType,
            'location_text' => $locationText,
            'meeting_link' => $meetingLink,
            'notes' => $notes,
            'updated_by_user_id' => $currentUserId,
        ],
        'timetable_conflicts' => $timetableConflicts,
    ];
}

function kuppi_schedule_host_candidates(array $draft): array
{
    $mode = (string) ($draft['mode'] ?? 'request');
    $candidates = [];

    if ($mode === 'request') {
        $requestId = (int) ($draft['request_id'] ?? 0);
        $requestCandidates = kuppi_schedule_conductor_candidates_for_request($requestId);
        if (!empty($requestCandidates)) {
            foreach ($requestCandidates as $row) {
                $candidates[] = [
                    'host_user_id' => (int) ($row['host_user_id'] ?? 0),
                    'host_name' => (string) ($row['host_name'] ?? 'Unknown User'),
                    'host_email' => (string) ($row['host_email'] ?? ''),
                    'host_role' => (string) ($row['host_role'] ?? 'student'),
                    'host_academic_year' => (int) ($row['host_academic_year'] ?? 0),
                    'source_type' => 'request_conductor',
                    'source_application_id' => (int) ($row['application_id'] ?? 0),
                    'vote_count' => (int) ($row['vote_count'] ?? 0),
                    'availability' => kuppi_conductor_availability_from_csv((string) ($row['availability_csv'] ?? '')),
                ];
            }

            return $candidates;
        }

        $batchId = (int) ($draft['batch_id'] ?? 0);
        if ($batchId <= 0) {
            $resolvedRequest = kuppi_schedule_resolve_request_for_draft($draft);
            $batchId = (int) ($resolvedRequest['batch_id'] ?? 0);
        }

        foreach (kuppi_schedule_manual_host_candidates_for_batch($batchId) as $row) {
            $candidates[] = [
                'host_user_id' => (int) ($row['host_user_id'] ?? 0),
                'host_name' => (string) ($row['host_name'] ?? 'Unknown User'),
                'host_email' => (string) ($row['host_email'] ?? ''),
                'host_role' => (string) ($row['host_role'] ?? 'student'),
                'host_academic_year' => (int) ($row['host_academic_year'] ?? 0),
                'source_type' => 'manual',
                'source_application_id' => null,
                'vote_count' => 0,
                'availability' => [],
            ];
        }

        return $candidates;
    }

    $batchId = (int) ($draft['batch_id'] ?? 0);
    foreach (kuppi_schedule_manual_host_candidates_for_batch($batchId) as $row) {
        $candidates[] = [
            'host_user_id' => (int) ($row['host_user_id'] ?? 0),
            'host_name' => (string) ($row['host_name'] ?? 'Unknown User'),
            'host_email' => (string) ($row['host_email'] ?? ''),
            'host_role' => (string) ($row['host_role'] ?? 'student'),
            'host_academic_year' => (int) ($row['host_academic_year'] ?? 0),
            'source_type' => 'manual',
            'source_application_id' => null,
            'vote_count' => 0,
            'availability' => [],
        ];
    }

    return $candidates;
}

function kuppi_schedule_candidate_map(array $candidates): array
{
    $map = [];
    foreach ($candidates as $candidate) {
        $hostUserId = (int) ($candidate['host_user_id'] ?? 0);
        if ($hostUserId <= 0) {
            continue;
        }
        $map[$hostUserId] = $candidate;
    }
    return $map;
}

function kuppi_schedule_default_host_ids(array $draft, array $candidates): array
{
    $existing = (array) ($draft['host_user_ids'] ?? []);
    if (!empty($existing)) {
        return array_values(array_unique(array_map('intval', $existing)));
    }

    if ((string) ($draft['mode'] ?? 'request') !== 'request') {
        return [];
    }

    $maxVotes = 0;
    foreach ($candidates as $candidate) {
        $votes = (int) ($candidate['vote_count'] ?? 0);
        if ($votes > $maxVotes) {
            $maxVotes = $votes;
        }
    }

    if ($maxVotes <= 0) {
        return [];
    }

    $selected = [];
    foreach ($candidates as $candidate) {
        if ((int) ($candidate['vote_count'] ?? 0) === $maxVotes) {
            $selected[] = (int) ($candidate['host_user_id'] ?? 0);
        }
    }

    return array_values(array_filter(array_unique($selected), static fn(int $id): bool => $id > 0));
}

function kuppi_schedule_selected_hosts_from_input(array $candidateMap): array
{
    $selectedRaw = $_POST['host_user_ids'] ?? [];
    $selectedList = is_array($selectedRaw) ? $selectedRaw : [];
    $selectedIds = kuppi_schedule_normalize_host_ids($selectedList);

    $errors = [];
    if (empty($selectedIds)) {
        $errors[] = 'Select at least one host.';
    }

    $hosts = [];
    foreach ($selectedIds as $hostUserId) {
        if (!isset($candidateMap[$hostUserId])) {
            $errors[] = 'One or more selected hosts are invalid.';
            continue;
        }

        $candidate = $candidateMap[$hostUserId];
        $hosts[] = [
            'host_user_id' => $hostUserId,
            'source_type' => (string) ($candidate['source_type'] ?? 'manual'),
            'source_application_id' => !empty($candidate['source_application_id']) ? (int) $candidate['source_application_id'] : null,
            'assigned_by_user_id' => (int) auth_id(),
        ];
    }

    return [
        'errors' => array_values(array_unique($errors)),
        'selected_ids' => $selectedIds,
        'hosts' => $hosts,
    ];
}

function kuppi_schedule_notify(array $session, array $hosts, string $event): void
{
    if (!smtp_is_configured()) {
        return;
    }

    $batchId = (int) ($session['batch_id'] ?? 0);
    $requestId = (int) ($session['request_id'] ?? 0);
    $title = (string) ($session['title'] ?? 'Kuppi Session');
    $subjectCode = (string) ($session['subject_code'] ?? '');
    $sessionDate = (string) ($session['session_date'] ?? '');
    $startTime = (string) ($session['start_time'] ?? '');
    $endTime = (string) ($session['end_time'] ?? '');
    $locationType = (string) ($session['location_type'] ?? 'physical');
    $location = $locationType === 'online'
        ? (string) ($session['meeting_link'] ?? '')
        : (string) ($session['location_text'] ?? '');

    $recipientMap = [];
    foreach (kuppi_scheduled_notification_batch_recipients($batchId) as $row) {
        $email = strtolower(trim((string) ($row['user_email'] ?? '')));
        if ($email === '') {
            continue;
        }
        $recipientMap[$email] = (string) ($row['user_name'] ?? 'Student');
    }

    if ($requestId > 0) {
        $owner = kuppi_scheduled_notification_request_owner($requestId);
        if ($owner) {
            $ownerEmail = strtolower(trim((string) ($owner['user_email'] ?? '')));
            if ($ownerEmail !== '') {
                $recipientMap[$ownerEmail] = (string) ($owner['user_name'] ?? 'Student');
            }
        }
    }

    foreach ($hosts as $host) {
        $hostEmail = strtolower(trim((string) ($host['host_email'] ?? '')));
        if ($hostEmail !== '') {
            $recipientMap[$hostEmail] = (string) ($host['host_name'] ?? 'Host');
        }
    }

    if (empty($recipientMap)) {
        return;
    }

    $subjectPrefix = match ($event) {
        'created' => 'New Kuppi Session Scheduled',
        'updated' => 'Kuppi Session Updated',
        'cancelled' => 'Kuppi Session Cancelled',
        'deleted' => 'Kuppi Session Removed',
        default => 'Kuppi Session Notification',
    };

    $subjectLine = $subjectPrefix . ': ' . $title;
    $dateLabel = $sessionDate !== '' ? date('F j, Y', strtotime($sessionDate)) : 'TBD';
    $startedAt = microtime(true);
    $timeBudgetSeconds = 8.0;

    foreach ($recipientMap as $email => $name) {
        if ((microtime(true) - $startedAt) >= $timeBudgetSeconds) {
            error_log('Kuppi schedule email budget exceeded; remaining recipients skipped.');
            break;
        }

        $bodyLines = [
            'Hello ' . $name . ',',
            '',
            $subjectPrefix . '.',
            '',
            'Title: ' . $title,
            'Subject: ' . ($subjectCode !== '' ? $subjectCode : 'N/A'),
            'Date: ' . $dateLabel,
            'Time: ' . ($startTime !== '' && $endTime !== '' ? (substr($startTime, 0, 5) . ' - ' . substr($endTime, 0, 5)) : 'TBD'),
            'Location: ' . ($location !== '' ? $location : 'TBD'),
        ];

        if (!smtp_send_email($email, $subjectLine, implode("\n", $bodyLines))) {
            error_log('Kuppi schedule email failed for: ' . $email . ' (' . $event . ')');
        }
    }
}

function kuppi_schedule_select_request_step(): void
{
    if (!kuppi_user_is_scheduler()) {
        abort(403, 'You do not have permission to schedule sessions.');
    }

    $role = (string) user_role();
    $currentUser = (array) auth_user();
    $userId = (int) ($currentUser['id'] ?? 0);
    $userBatchId = (int) ($currentUser['batch_id'] ?? 0);
    $adminBatchId = $role === 'admin' ? (int) request_input('batch_id', 0) : 0;
    $selectedSort = trim((string) request_input('sort', 'most_votes'));
    if (!in_array($selectedSort, kuppi_sort_options(), true)) {
        $selectedSort = 'most_votes';
    }
    $searchQuery = trim((string) request_input('q', ''));
    if (strlen($searchQuery) > 120) {
        $searchQuery = substr($searchQuery, 0, 120);
    }

    $directRequestId = (int) request_input('request_id', 0);
    if ($directRequestId > 0) {
        $request = kuppi_resolve_readable_request($directRequestId);
        if ($request && kuppi_user_can_schedule_request($request) && !kuppi_scheduled_session_has_active_for_request($directRequestId)) {
            $draft = kuppi_schedule_default_draft();
            $draft['mode'] = 'request';
            $draft['request_id'] = $directRequestId;
            $draft['batch_id'] = (int) ($request['batch_id'] ?? 0);
            $draft['subject_id'] = (int) ($request['subject_id'] ?? 0);
            $draft['title'] = (string) ($request['title'] ?? '');
            $draft['description'] = (string) ($request['description'] ?? '');
            kuppi_schedule_set_draft($draft);
            redirect('/dashboard/kuppi/schedule/assign');
        }
    }

    $requests = kuppi_schedule_open_requests_for_scheduler(
        $role,
        $userId,
        $userBatchId,
        $searchQuery,
        $selectedSort,
        $adminBatchId
    );

    view('kuppi::schedule_select_request', [
        'requests' => $requests,
        'selected_sort' => $selectedSort,
        'selected_search_query' => $searchQuery,
        'is_admin' => $role === 'admin',
        'admin_batch_id' => $adminBatchId,
        'batch_options' => $role === 'admin' ? kuppi_batch_options_for_admin() : [],
        'active_batch' => $adminBatchId > 0 ? kuppi_find_batch_option_by_id($adminBatchId) : null,
    ], 'dashboard');
}

function kuppi_schedule_manual_start(): void
{
    if (!kuppi_user_is_scheduler()) {
        abort(403, 'You do not have permission to schedule sessions.');
    }

    $role = (string) user_role();
    $userBatchId = (int) (auth_user()['batch_id'] ?? 0);
    $selectedBatchId = $role === 'admin' ? (int) request_input('batch_id', 0) : $userBatchId;

    if ($role === 'admin' && $selectedBatchId <= 0) {
        flash('warning', 'Select a batch first before starting a manual session.');
        redirect('/dashboard/kuppi/schedule');
    }

    $draft = kuppi_schedule_default_draft();
    $draft['mode'] = 'manual';
    $draft['batch_id'] = $selectedBatchId;
    kuppi_schedule_set_draft($draft);
    redirect('/dashboard/kuppi/schedule/assign');
}

function kuppi_schedule_select_request_action(): void
{
    csrf_check();

    if (!kuppi_user_is_scheduler()) {
        abort(403, 'You do not have permission to schedule sessions.');
    }

    $requestId = (int) request_input('request_id', 0);
    $request = kuppi_resolve_readable_request($requestId);
    if (!$request || !kuppi_user_can_schedule_request($request)) {
        abort(403, 'Selected request is not available for scheduling.');
    }

    if (kuppi_scheduled_session_has_active_for_request($requestId)) {
        flash('error', 'This request already has an active scheduled session.');
        redirect('/dashboard/kuppi/schedule');
    }

    $draft = kuppi_schedule_default_draft();
    $draft['mode'] = 'request';
    $draft['request_id'] = $requestId;
    $draft['batch_id'] = (int) ($request['batch_id'] ?? 0);
    $draft['subject_id'] = (int) ($request['subject_id'] ?? 0);
    $draft['title'] = (string) ($request['title'] ?? '');
    $draft['description'] = (string) ($request['description'] ?? '');
    kuppi_schedule_set_draft($draft);

    redirect('/dashboard/kuppi/schedule/assign');
}

function kuppi_schedule_set_step(): void
{
    if (!kuppi_user_is_scheduler()) {
        abort(403, 'You do not have permission to schedule sessions.');
    }

    $draft = kuppi_schedule_require_draft();
    $mode = (string) ($draft['mode'] ?? 'request');
    $role = (string) user_role();
    $currentUser = (array) auth_user();
    $userId = (int) ($currentUser['id'] ?? 0);
    $userBatchId = (int) ($currentUser['batch_id'] ?? 0);
    $linkedRequest = null;

    if ($mode === 'request') {
        $linkedRequest = kuppi_schedule_resolve_request_for_draft($draft);
        if (!$linkedRequest) {
            kuppi_schedule_clear_draft();
            flash('error', 'Selected request is no longer available for scheduling.');
            redirect('/dashboard/kuppi/schedule');
        }

        if (kuppi_scheduled_session_has_active_for_request((int) ($linkedRequest['id'] ?? 0))) {
            kuppi_schedule_clear_draft();
            flash('error', 'This request already has an active scheduled session.');
            redirect('/dashboard/kuppi/schedule');
        }

        $draft['batch_id'] = (int) ($linkedRequest['batch_id'] ?? 0);
        $draft['subject_id'] = (int) ($linkedRequest['subject_id'] ?? 0);
        $draft['title'] = (string) ($linkedRequest['title'] ?? '');
        $draft['description'] = (string) ($linkedRequest['description'] ?? '');
        kuppi_schedule_set_draft($draft);
    }

    $selectedHostData = kuppi_schedule_selected_hosts($draft);
    if (empty($selectedHostData['selected_host_ids'])) {
        flash('warning', 'Select one or more hosts first.');
        redirect('/dashboard/kuppi/schedule/assign');
    }

    if (empty($selectedHostData['selected_hosts'])) {
        $draft['host_user_ids'] = [];
        kuppi_schedule_set_draft($draft);
        flash('warning', 'Selected hosts are no longer available. Please select hosts again.');
        redirect('/dashboard/kuppi/schedule/assign');
    }

    if (count($selectedHostData['selected_host_ids']) !== count($selectedHostData['selected_hosts'])) {
        $draft['host_user_ids'] = array_values(array_map(
            static fn(array $host): int => (int) ($host['host_user_id'] ?? 0),
            $selectedHostData['selected_hosts']
        ));
        kuppi_schedule_set_draft($draft);
    }

    $adminBatchId = $role === 'admin' ? (int) ($draft['batch_id'] ?? 0) : 0;
    $subjectOptions = kuppi_scheduler_subject_options_for_user($role, $userId, $userBatchId, $adminBatchId);
    $availabilityStats = kuppi_schedule_selected_host_availability_stats((array) $selectedHostData['selected_hosts']);
    $selectedSlotKey = kuppi_schedule_slot_key_for_datetime(
        trim((string) ($draft['session_date'] ?? '')),
        trim((string) ($draft['start_time'] ?? ''))
    );
    $slotMatch = kuppi_schedule_selected_host_slot_match((array) $selectedHostData['selected_hosts'], $selectedSlotKey);
    $timetableSlots = [];
    $timetableSelectedDaySlots = [];
    $timetableConflicts = [];
    $timetableBatchId = (int) ($draft['batch_id'] ?? 0);
    $selectedDayOfWeek = kuppi_timetable_day_of_week_from_date(trim((string) ($draft['session_date'] ?? '')));
    if ($timetableBatchId > 0 && kuppi_user_can_view_timetable_for_batch($timetableBatchId)) {
        $timetableSlots = kuppi_university_timetable_slots_for_batch($timetableBatchId);
        if ($selectedDayOfWeek > 0) {
            $timetableSelectedDaySlots = array_values(array_filter($timetableSlots, static function (array $slot) use ($selectedDayOfWeek): bool {
                return (int) ($slot['day_of_week'] ?? 0) === $selectedDayOfWeek;
            }));
        }

        $sessionDateDraft = trim((string) ($draft['session_date'] ?? ''));
        $startTimeDraft = trim((string) ($draft['start_time'] ?? ''));
        $endTimeDraft = trim((string) ($draft['end_time'] ?? ''));
        if ($sessionDateDraft !== '' && $startTimeDraft !== '' && $endTimeDraft !== '') {
            $timetableConflicts = kuppi_university_timetable_conflicts_for_session(
                $timetableBatchId,
                $sessionDateDraft,
                $startTimeDraft,
                $endTimeDraft
            );
        }
    }

    view('kuppi::schedule_set', [
        'draft' => $draft,
        'mode' => $mode,
        'linked_request' => $linkedRequest,
        'selected_hosts' => $selectedHostData['selected_hosts'],
        'availability_stats' => $availabilityStats,
        'selected_slot_key' => $selectedSlotKey,
        'selected_slot_match' => $slotMatch,
        'availability_options' => kuppi_conductor_availability_options(),
        'subject_options' => $subjectOptions,
        'is_admin' => $role === 'admin',
        'batch_options' => $role === 'admin' ? kuppi_batch_options_for_admin() : [],
        'timetable_slots' => $timetableSlots,
        'timetable_selected_day_slots' => $timetableSelectedDaySlots,
        'timetable_conflicts' => $timetableConflicts,
        'timetable_day_labels' => kuppi_timetable_day_labels(),
    ], 'dashboard');
}

function kuppi_schedule_set_action(): void
{
    csrf_check();

    if (!kuppi_user_is_scheduler()) {
        abort(403, 'You do not have permission to schedule sessions.');
    }

    $draft = kuppi_schedule_require_draft();
    $selectedHostData = kuppi_schedule_selected_hosts($draft);
    if (empty($selectedHostData['selected_host_ids']) || empty($selectedHostData['selected_hosts'])) {
        flash('warning', 'Select one or more hosts first.');
        redirect('/dashboard/kuppi/schedule/assign');
    }

    if (count((array) $selectedHostData['selected_host_ids']) !== count((array) $selectedHostData['selected_hosts'])) {
        $draft['host_user_ids'] = array_values(array_map(
            static fn(array $host): int => (int) ($host['host_user_id'] ?? 0),
            (array) $selectedHostData['selected_hosts']
        ));
        kuppi_schedule_set_draft($draft);
        flash('warning', 'Some selected hosts are no longer available. Please review host selection.');
        redirect('/dashboard/kuppi/schedule/assign');
    }

    $validation = kuppi_schedule_validate_set_input($draft);
    if (!empty($validation['errors'])) {
        flash('error', implode(' ', $validation['errors']));
        redirect('/dashboard/kuppi/schedule/set');
    }

    $nextDraft = array_merge($draft, $validation['data']);
    kuppi_schedule_set_draft($nextDraft);

    redirect('/dashboard/kuppi/schedule/review');
}

function kuppi_schedule_assign_step(): void
{
    if (!kuppi_user_is_scheduler()) {
        abort(403, 'You do not have permission to schedule sessions.');
    }

    $draft = kuppi_schedule_require_draft();
    $mode = (string) ($draft['mode'] ?? 'request');
    $linkedRequest = null;
    if ($mode === 'request') {
        $linkedRequest = kuppi_schedule_resolve_request_for_draft($draft);
        if (!$linkedRequest) {
            kuppi_schedule_clear_draft();
            flash('error', 'Selected request is no longer available for scheduling.');
            redirect('/dashboard/kuppi/schedule');
        }

        if (kuppi_scheduled_session_has_active_for_request((int) ($linkedRequest['id'] ?? 0))) {
            kuppi_schedule_clear_draft();
            flash('error', 'This request already has an active scheduled session.');
            redirect('/dashboard/kuppi/schedule');
        }

        $draft['batch_id'] = (int) ($linkedRequest['batch_id'] ?? 0);
        $draft['subject_id'] = (int) ($linkedRequest['subject_id'] ?? 0);
        $draft['title'] = (string) ($linkedRequest['title'] ?? '');
        $draft['description'] = (string) ($linkedRequest['description'] ?? '');
        kuppi_schedule_set_draft($draft);
    }

    $candidates = kuppi_schedule_host_candidates($draft);
    $selectedHostIds = kuppi_schedule_default_host_ids($draft, $candidates);

    view('kuppi::schedule_assign', [
        'draft' => $draft,
        'mode' => $mode,
        'linked_request' => $linkedRequest,
        'candidates' => $candidates,
        'selected_host_ids' => $selectedHostIds,
        'availability_options' => kuppi_conductor_availability_options(),
    ], 'dashboard');
}

function kuppi_schedule_assign_action(): void
{
    csrf_check();

    if (!kuppi_user_is_scheduler()) {
        abort(403, 'You do not have permission to schedule sessions.');
    }

    $draft = kuppi_schedule_require_draft();
    $candidateMap = kuppi_schedule_candidate_map(kuppi_schedule_host_candidates($draft));
    $selection = kuppi_schedule_selected_hosts_from_input($candidateMap);

    if (!empty($selection['errors'])) {
        flash('error', implode(' ', $selection['errors']));
        redirect('/dashboard/kuppi/schedule/assign');
    }

    $draft['host_user_ids'] = $selection['selected_ids'];
    kuppi_schedule_set_draft($draft);
    redirect('/dashboard/kuppi/schedule/set');
}

function kuppi_schedule_review_step(): void
{
    if (!kuppi_user_is_scheduler()) {
        abort(403, 'You do not have permission to schedule sessions.');
    }

    $draft = kuppi_schedule_require_draft();
    $selectedHostData = kuppi_schedule_selected_hosts($draft);
    if (empty($selectedHostData['selected_host_ids']) || empty($selectedHostData['selected_hosts'])) {
        flash('warning', 'Select at least one host.');
        redirect('/dashboard/kuppi/schedule/assign');
    }

    if (count((array) $selectedHostData['selected_host_ids']) !== count((array) $selectedHostData['selected_hosts'])) {
        $draft['host_user_ids'] = array_values(array_map(
            static fn(array $host): int => (int) ($host['host_user_id'] ?? 0),
            (array) $selectedHostData['selected_hosts']
        ));
        kuppi_schedule_set_draft($draft);
        flash('warning', 'Some selected hosts are no longer available. Please review host selection.');
        redirect('/dashboard/kuppi/schedule/assign');
    }

    if (trim((string) ($draft['session_date'] ?? '')) === '') {
        flash('warning', 'Set schedule details before review.');
        redirect('/dashboard/kuppi/schedule/set');
    }

    view('kuppi::schedule_review', [
        'draft' => $draft,
        'selected_hosts' => $selectedHostData['selected_hosts'],
        'linked_request' => kuppi_schedule_resolve_request_for_draft($draft),
    ], 'dashboard');
}

function kuppi_schedule_confirm_action(): void
{
    csrf_check();

    if (!kuppi_user_is_scheduler()) {
        abort(403, 'You do not have permission to schedule sessions.');
    }

    $draft = kuppi_schedule_require_draft();
    $selectedHostData = kuppi_schedule_selected_hosts($draft);
    if (empty($selectedHostData['selected_host_ids']) || empty($selectedHostData['selected_hosts'])) {
        flash('error', 'At least one host is required.');
        redirect('/dashboard/kuppi/schedule/assign');
    }

    if (trim((string) ($draft['session_date'] ?? '')) === '') {
        flash('warning', 'Set schedule details first.');
        redirect('/dashboard/kuppi/schedule/set');
    }

    if (count((array) $selectedHostData['selected_host_ids']) !== count((array) $selectedHostData['selected_hosts'])) {
        flash('error', 'Selected hosts are no longer available.');
        redirect('/dashboard/kuppi/schedule/assign');
    }

    $hosts = [];
    foreach ((array) $selectedHostData['selected_hosts'] as $candidate) {
        $hostUserId = (int) ($candidate['host_user_id'] ?? 0);
        if ($hostUserId <= 0) {
            continue;
        }

        $hosts[] = [
            'host_user_id' => $hostUserId,
            'source_type' => (string) ($candidate['source_type'] ?? 'manual'),
            'source_application_id' => !empty($candidate['source_application_id']) ? (int) $candidate['source_application_id'] : null,
            'assigned_by_user_id' => (int) auth_id(),
        ];
    }

    if (empty($hosts)) {
        flash('error', 'At least one host is required.');
        redirect('/dashboard/kuppi/schedule/assign');
    }

    $request = null;
    $requestId = (int) ($draft['request_id'] ?? 0);
    if ((string) ($draft['mode'] ?? '') === 'request') {
        $request = kuppi_schedule_resolve_request_for_draft($draft);
        if (!$request) {
            kuppi_schedule_clear_draft();
            flash('error', 'Selected request is no longer available.');
            redirect('/dashboard/kuppi/schedule');
        }

        if (kuppi_scheduled_session_has_active_for_request($requestId)) {
            kuppi_schedule_clear_draft();
            flash('error', 'This request already has an active scheduled session.');
            redirect('/dashboard/kuppi/schedule');
        }
    } else {
        $requestId = 0;
    }

    try {
        $sessionId = kuppi_scheduled_create_with_hosts([
            'batch_id' => (int) ($draft['batch_id'] ?? 0),
            'subject_id' => (int) ($draft['subject_id'] ?? 0),
            'request_id' => $requestId,
            'title' => (string) ($draft['title'] ?? ''),
            'description' => (string) ($draft['description'] ?? ''),
            'session_date' => (string) ($draft['session_date'] ?? ''),
            'start_time' => (string) ($draft['start_time'] ?? ''),
            'end_time' => (string) ($draft['end_time'] ?? ''),
            'duration_minutes' => (int) ($draft['duration_minutes'] ?? 0),
            'max_attendees' => (int) ($draft['max_attendees'] ?? 0),
            'location_type' => (string) ($draft['location_type'] ?? 'physical'),
            'location_text' => (string) ($draft['location_text'] ?? ''),
            'meeting_link' => (string) ($draft['meeting_link'] ?? ''),
            'notes' => (string) ($draft['notes'] ?? ''),
            'status' => 'scheduled',
            'created_by_user_id' => (int) auth_id(),
        ], $hosts);
    } catch (Throwable) {
        flash('error', 'Unable to schedule this session right now.');
        redirect('/dashboard/kuppi/schedule/review');
    }

    if ($sessionId <= 0) {
        flash('error', 'This request already has an active scheduled session.');
        redirect('/dashboard/kuppi/schedule');
    }

    $session = kuppi_find_scheduled_session_readable($sessionId, (string) user_role(), (int) (auth_user()['batch_id'] ?? 0));
    $sessionHosts = kuppi_scheduled_hosts_for_session($sessionId);
    if ($session) {
        kuppi_schedule_notify($session, $sessionHosts, 'created');
    }

    kuppi_schedule_clear_draft();
    flash('success', 'Kuppi session scheduled successfully.');
    redirect('/dashboard/kuppi/schedule/success?id=' . $sessionId);
}

function kuppi_schedule_success(): void
{
    if (!kuppi_user_is_scheduler()) {
        abort(403, 'You do not have permission to view this page.');
    }

    $sessionId = (int) request_input('id', 0);
    $session = null;
    if ($sessionId > 0) {
        $session = kuppi_find_scheduled_session_readable($sessionId, (string) user_role(), (int) (auth_user()['batch_id'] ?? 0));
    }

    view('kuppi::schedule_success', [
        'session' => $session,
    ], 'dashboard');
}
