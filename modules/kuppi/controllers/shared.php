<?php

/**
 * Kuppi Module — Controllers (Shared helpers)
 */

function kuppi_feed_per_page(): int
{
    return 10;
}

function kuppi_relative_time_label(string $timestamp): string
{
    $ts = strtotime($timestamp);
    if ($ts === false) {
        return 'just now';
    }

    $delta = time() - $ts;
    if ($delta <= 0) {
        return 'just now';
    }

    if ($delta < 60) {
        return 'just now';
    }

    $units = [
        ['seconds' => 604800, 'label' => 'week'],
        ['seconds' => 86400, 'label' => 'day'],
        ['seconds' => 3600, 'label' => 'hour'],
        ['seconds' => 60, 'label' => 'minute'],
    ];

    foreach ($units as $unit) {
        $seconds = (int) ($unit['seconds'] ?? 0);
        if ($seconds <= 0 || $delta < $seconds) {
            continue;
        }

        $value = (int) floor($delta / $seconds);
        $label = (string) ($unit['label'] ?? 'minute');
        return $value . ' ' . $label . ($value === 1 ? '' : 's') . ' ago';
    }

    return 'just now';
}

function kuppi_user_can_create(): bool
{
    $role = (string) user_role();
    if (!in_array($role, ['student', 'coordinator'], true)) {
        return false;
    }

    return (int) (auth_user()['batch_id'] ?? 0) > 0;
}

function kuppi_user_can_moderate_batch(int $batchId): bool
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

function kuppi_user_can_vote_request(array $request): bool
{
    $requestBatchId = (int) ($request['batch_id'] ?? 0);
    if ($requestBatchId <= 0) {
        return false;
    }

    $role = (string) user_role();
    if ($role === 'admin') {
        $selectedBatchId = (int) request_input('batch_id', 0);
        return $selectedBatchId > 0 && $selectedBatchId === $requestBatchId;
    }

    if (!in_array($role, ['student', 'coordinator', 'moderator'], true)) {
        return false;
    }

    return (int) (auth_user()['batch_id'] ?? 0) === $requestBatchId;
}

function kuppi_user_can_apply_as_conductor(array $request): bool
{
    if (!kuppi_request_is_open($request)) {
        return false;
    }

    $role = (string) user_role();
    if (!in_array($role, ['student', 'coordinator'], true)) {
        return false;
    }

    return (int) (auth_user()['batch_id'] ?? 0) === (int) ($request['batch_id'] ?? 0);
}

function kuppi_user_can_vote_conductor(array $request): bool
{
    if (!kuppi_request_is_open($request)) {
        return false;
    }

    $role = (string) user_role();
    if (!in_array($role, ['student', 'coordinator'], true)) {
        return false;
    }

    return (int) (auth_user()['batch_id'] ?? 0) === (int) ($request['batch_id'] ?? 0);
}

function kuppi_request_is_open(array $request): bool
{
    return (string) ($request['status'] ?? '') === 'open';
}

function kuppi_conductor_availability_options(): array
{
    return [
        'weekday_mornings' => 'Weekday Mornings',
        'weekday_afternoons' => 'Weekday Afternoons',
        'weekday_evenings' => 'Weekday Evenings',
        'weekend_mornings' => 'Weekend Mornings',
        'weekend_afternoons' => 'Weekend Afternoons',
        'weekend_evenings' => 'Weekend Evenings',
    ];
}

function kuppi_conductor_availability_to_csv(array $selected): string
{
    $allowed = array_keys(kuppi_conductor_availability_options());
    $normalized = [];
    foreach ($selected as $value) {
        $value = trim((string) $value);
        if ($value === '' || !in_array($value, $allowed, true)) {
            continue;
        }
        $normalized[] = $value;
    }

    $normalized = array_values(array_unique($normalized));
    sort($normalized);
    return implode(',', $normalized);
}

function kuppi_conductor_availability_from_csv(string $csv): array
{
    $csv = trim($csv);
    if ($csv === '') {
        return [];
    }

    return array_values(array_filter(array_map(
        static fn(string $value): string => trim($value),
        explode(',', $csv)
    ), static fn(string $value): bool => $value !== ''));
}

function kuppi_schedule_normalize_host_ids(array $hostUserIds): array
{
    return array_values(array_filter(array_unique(array_map(
        static fn($value): int => (int) $value,
        $hostUserIds
    )), static fn(int $id): bool => $id > 0));
}

function kuppi_schedule_slot_key_for_datetime(string $sessionDate, string $startTime): ?string
{
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $sessionDate) || !preg_match('/^\d{2}:\d{2}$/', $startTime)) {
        return null;
    }

    $dayTs = strtotime($sessionDate . ' 00:00:00');
    $timeTs = strtotime('1970-01-01 ' . $startTime . ':00');
    if ($dayTs === false || $timeTs === false) {
        return null;
    }

    $prefix = ((int) date('N', $dayTs) >= 6) ? 'weekend' : 'weekday';
    $hour = (int) date('G', $timeTs);
    $suffix = $hour < 12 ? 'mornings' : ($hour < 17 ? 'afternoons' : 'evenings');
    return $prefix . '_' . $suffix;
}

function kuppi_schedule_selected_hosts(array $draft): array
{
    $candidateMap = kuppi_schedule_candidate_map(kuppi_schedule_host_candidates($draft));
    $selectedHostIds = kuppi_schedule_normalize_host_ids((array) ($draft['host_user_ids'] ?? []));
    $selectedHosts = [];

    foreach ($selectedHostIds as $hostUserId) {
        if (isset($candidateMap[$hostUserId])) {
            $selectedHosts[] = $candidateMap[$hostUserId];
        }
    }

    return [
        'candidate_map' => $candidateMap,
        'selected_host_ids' => $selectedHostIds,
        'selected_hosts' => $selectedHosts,
    ];
}

function kuppi_schedule_selected_host_availability_stats(array $selectedHosts): array
{
    $allowedSlots = array_keys(kuppi_conductor_availability_options());
    $counts = array_fill_keys($allowedSlots, 0);
    $hostsWithAvailability = 0;

    foreach ($selectedHosts as $host) {
        $availability = array_values(array_filter(array_unique(array_map(
            static fn($slot): string => trim((string) $slot),
            (array) ($host['availability'] ?? [])
        )), static fn(string $slot): bool => $slot !== '' && isset($counts[$slot])));

        if (empty($availability)) {
            continue;
        }

        $hostsWithAvailability++;
        foreach ($availability as $slot) {
            $counts[$slot]++;
        }
    }

    $nonZero = array_filter($counts, static fn(int $count): bool => $count > 0);
    arsort($nonZero);

    $maxCount = 0;
    $recommendedSlots = [];
    foreach ($nonZero as $slot => $count) {
        if ($count > $maxCount) {
            $maxCount = $count;
            $recommendedSlots = [$slot];
            continue;
        }

        if ($count === $maxCount) {
            $recommendedSlots[] = $slot;
        }
    }

    return [
        'hosts_with_availability' => $hostsWithAvailability,
        'counts' => $counts,
        'ranked_counts' => $nonZero,
        'max_count' => $maxCount,
        'recommended_slots' => $recommendedSlots,
    ];
}

function kuppi_schedule_selected_host_slot_match(array $selectedHosts, ?string $slotKey): array
{
    if ($slotKey === null) {
        return [
            'slot_key' => null,
            'hosts_with_availability' => 0,
            'matched_hosts' => 0,
            'is_full_match' => false,
            'has_any_match' => false,
        ];
    }

    $hostsWithAvailability = 0;
    $matchedHosts = 0;

    foreach ($selectedHosts as $host) {
        $availability = array_values(array_filter(array_unique(array_map(
            static fn($slot): string => trim((string) $slot),
            (array) ($host['availability'] ?? [])
        )), static fn(string $slot): bool => $slot !== ''));

        if (empty($availability)) {
            continue;
        }

        $hostsWithAvailability++;
        if (in_array($slotKey, $availability, true)) {
            $matchedHosts++;
        }
    }

    return [
        'slot_key' => $slotKey,
        'hosts_with_availability' => $hostsWithAvailability,
        'matched_hosts' => $matchedHosts,
        'is_full_match' => $hostsWithAvailability > 0 && $matchedHosts === $hostsWithAvailability,
        'has_any_match' => $matchedHosts > 0,
    ];
}

function kuppi_validate_conductor_application_input(): array
{
    $motivationRaw = (string) request_input('motivation', '');
    $motivation = trim(str_replace(["\r\n", "\r"], "\n", $motivationRaw));
    $availabilityInput = $_POST['availability'] ?? [];
    $availabilityList = is_array($availabilityInput) ? $availabilityInput : [];
    $availabilityCsv = kuppi_conductor_availability_to_csv($availabilityList);
    $availability = kuppi_conductor_availability_from_csv($availabilityCsv);
    $errors = [];

    if ($motivation === '') {
        $errors[] = 'Motivation is required.';
    } elseif (strlen($motivation) > 300) {
        $errors[] = 'Motivation must be at most 300 characters.';
    }

    if (empty($availability)) {
        $errors[] = 'Select at least one availability option.';
    }

    return [
        'errors' => $errors,
        'data' => [
            'motivation' => $motivation,
            'availability_csv' => $availabilityCsv,
            'availability' => $availability,
        ],
    ];
}

function kuppi_tags_to_array(string $tagsCsv): array
{
    $tagsCsv = trim($tagsCsv);
    if ($tagsCsv === '') {
        return [];
    }

    return array_values(array_filter(array_map(
        static fn(string $tag): string => trim($tag),
        explode(',', $tagsCsv)
    ), static fn(string $tag): bool => $tag !== ''));
}

function kuppi_normalize_tags_csv(string $raw): string
{
    $parts = explode(',', strtolower($raw));
    $normalized = [];
    foreach ($parts as $part) {
        $tag = trim($part);
        if ($tag === '') {
            continue;
        }

        $tag = preg_replace('/[\s_]+/', '-', $tag) ?? '';
        $tag = preg_replace('/[^a-z0-9-]/', '', $tag) ?? '';
        $tag = trim($tag, '-');
        if ($tag === '') {
            continue;
        }

        $normalized[] = $tag;
    }

    $normalized = array_values(array_unique($normalized));
    return implode(',', $normalized);
}

function kuppi_validate_request_input(int $batchId): array
{
    $title = trim((string) request_input('title', ''));
    $descriptionRaw = (string) request_input('description', '');
    $description = trim(str_replace(["\r\n", "\r"], "\n", $descriptionRaw));
    $subjectId = (int) request_input('subject_id', 0);
    $tagsCsv = kuppi_normalize_tags_csv((string) request_input('tags_csv', ''));
    $tags = kuppi_tags_to_array($tagsCsv);
    $errors = [];

    if ($subjectId <= 0) {
        $errors[] = 'Subject is required.';
    } elseif (!kuppi_subject_exists_in_batch($subjectId, $batchId)) {
        $errors[] = 'Selected subject is invalid for your batch.';
    }

    if ($title === '') {
        $errors[] = 'Title is required.';
    } elseif (strlen($title) > 200) {
        $errors[] = 'Title must be at most 200 characters.';
    }

    if ($description === '') {
        $errors[] = 'Description is required.';
    } elseif (strlen($description) > 2000) {
        $errors[] = 'Description must be at most 2000 characters.';
    }

    if (count($tags) > 8) {
        $errors[] = 'You can add at most 8 tags.';
    }

    foreach ($tags as $tag) {
        if (strlen($tag) > 24) {
            $errors[] = 'Each tag must be at most 24 characters.';
            break;
        }
    }

    return [
        'errors' => $errors,
        'data' => [
            'subject_id' => $subjectId,
            'title' => $title,
            'description' => $description,
            'tags_csv' => $tagsCsv,
        ],
    ];
}

function kuppi_index_url_for_batch(int $batchId): string
{
    if (user_role() === 'admin' && $batchId > 0) {
        return '/dashboard/kuppi?batch_id=' . $batchId;
    }

    return '/dashboard/kuppi';
}

function kuppi_index_url_for_request(array $request): string
{
    return kuppi_index_url_for_batch((int) ($request['batch_id'] ?? 0));
}

function kuppi_request_url(array $request): string
{
    $requestId = (int) ($request['id'] ?? 0);
    $url = '/dashboard/kuppi/' . $requestId;

    if (user_role() === 'admin') {
        $batchId = (int) ($request['batch_id'] ?? 0);
        if ($batchId > 0) {
            $url .= '?batch_id=' . $batchId;
        }
    }

    return $url;
}

function kuppi_resolve_valid_return_to(string $returnTo, array $request): string
{
    $raw = trim($returnTo);
    if ($raw !== '') {
        $path = (string) parse_url($raw, PHP_URL_PATH);
        if (
            str_starts_with($path, '/dashboard/kuppi')
            || str_starts_with($path, '/dashboard/feed')
            || str_starts_with($path, '/my-kuppi-requests')
        ) {
            return $raw;
        }
    }

    return kuppi_index_url_for_request($request);
}

function kuppi_resolve_readable_request(int $requestId): ?array
{
    if (user_role() === 'admin') {
        return kuppi_find_request_admin($requestId, (int) auth_id());
    }

    $batchId = (int) (auth_user()['batch_id'] ?? 0);
    if ($batchId <= 0) {
        return null;
    }

    return kuppi_find_request_for_batch($requestId, $batchId, (int) auth_id());
}

function kuppi_can_edit_request(array $request): bool
{
    return (int) ($request['requested_by_user_id'] ?? 0) === (int) auth_id()
        && kuppi_request_is_open($request);
}

function kuppi_can_delete_request(array $request): bool
{
    if ((int) ($request['requested_by_user_id'] ?? 0) === (int) auth_id()) {
        return true;
    }

    return kuppi_user_can_moderate_batch((int) ($request['batch_id'] ?? 0));
}

function kuppi_comment_target_type(): string
{
    return 'kuppi_request';
}

function kuppi_comment_can_delete(array $request, array $comment): bool
{
    $currentUserId = (int) auth_id();
    $commentAuthorId = (int) ($comment['user_id'] ?? 0);
    if ($commentAuthorId > 0 && $commentAuthorId === $currentUserId) {
        return true;
    }

    if (kuppi_user_can_moderate_batch((int) ($request['batch_id'] ?? 0))) {
        return true;
    }

    if ((string) user_role() === 'coordinator') {
        return subjects_find_for_coordinator((int) ($request['subject_id'] ?? 0), $currentUserId) !== null;
    }

    return false;
}

function kuppi_enrich_comment_tree(array $nodes, array $request): array
{
    $currentUserId = (int) auth_id();
    $maxDepth = comments_max_depth_for_target(kuppi_comment_target_type());
    $enriched = [];

    foreach ($nodes as $node) {
        $authorId = (int) ($node['user_id'] ?? 0);
        $depth = (int) ($node['depth'] ?? 0);
        $node['can_edit'] = $authorId > 0 && $authorId === $currentUserId;
        $node['can_delete'] = kuppi_comment_can_delete($request, $node);
        $node['can_reply'] = auth_check() && $depth < $maxDepth;
        $node['children'] = kuppi_enrich_comment_tree((array) ($node['children'] ?? []), $request);
        $enriched[] = $node;
    }

    return $enriched;
}

function kuppi_user_can_read_batch(int $batchId): bool
{
    if ($batchId <= 0) {
        return false;
    }

    if ((string) user_role() === 'admin') {
        return true;
    }

    return (int) (auth_user()['batch_id'] ?? 0) === $batchId;
}

function kuppi_user_is_scheduler(): bool
{
    return in_array((string) user_role(), ['coordinator', 'moderator', 'admin'], true);
}

function kuppi_user_can_schedule_subject(int $subjectId, int $batchId): bool
{
    if ($subjectId <= 0 || $batchId <= 0) {
        return false;
    }

    $role = (string) user_role();
    $currentUser = (array) auth_user();
    $currentUserId = (int) ($currentUser['id'] ?? 0);
    $currentBatchId = (int) ($currentUser['batch_id'] ?? 0);

    if ($role === 'admin') {
        return kuppi_subject_exists_in_batch($subjectId, $batchId);
    }

    if ($currentBatchId !== $batchId) {
        return false;
    }

    if ($role === 'moderator') {
        return kuppi_subject_exists_in_batch($subjectId, $batchId);
    }

    if ($role === 'coordinator') {
        return subjects_find_for_coordinator($subjectId, $currentUserId) !== null;
    }

    return false;
}

function kuppi_user_can_schedule_request(array $request): bool
{
    if (!kuppi_user_is_scheduler()) {
        return false;
    }

    return kuppi_user_can_schedule_subject(
        (int) ($request['subject_id'] ?? 0),
        (int) ($request['batch_id'] ?? 0)
    );
}

function kuppi_user_can_manage_scheduled_session(array $session): bool
{
    if (!kuppi_user_is_scheduler()) {
        return false;
    }

    return kuppi_user_can_schedule_subject(
        (int) ($session['subject_id'] ?? 0),
        (int) ($session['batch_id'] ?? 0)
    );
}
