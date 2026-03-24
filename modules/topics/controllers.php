<?php

/**
 * Topics Module — Controllers
 */

function topics_role_label(): string
{
    return match (user_role()) {
        'admin' => 'Admin',
        'moderator' => 'Moderator',
        'coordinator' => 'Coordinator',
        default => 'Student',
    };
}

function topics_subjects_manage_back_url(): string
{
    return user_role() === 'coordinator' ? '/coordinator/subjects' : '/subjects';
}

function topics_resolve_manageable_subject(int $subjectId): ?array
{
    $role = user_role();

    if ($role === 'admin') {
        return subjects_find_admin($subjectId);
    }

    if ($role === 'moderator') {
        $batchId = (int) (auth_user()['batch_id'] ?? 0);
        if ($batchId <= 0) {
            return null;
        }

        return subjects_find_for_batch($subjectId, $batchId);
    }

    if ($role === 'coordinator') {
        return subjects_find_for_coordinator($subjectId, (int) auth_id());
    }

    return null;
}

function topics_resolve_readable_subject(int $subjectId): ?array
{
    if (user_role() === 'admin') {
        return subjects_find_admin($subjectId);
    }

    $batchId = (int) (auth_user()['batch_id'] ?? 0);
    if ($batchId <= 0) {
        return null;
    }

    return subjects_find_for_batch($subjectId, $batchId);
}

function topics_validate_input(string $title, int $sortOrder): array
{
    $errors = [];

    if ($title === '') {
        $errors[] = 'Topic title is required.';
    }

    if (strlen($title) > 200) {
        $errors[] = 'Topic title must be at most 200 characters.';
    }

    if ($sortOrder < 1) {
        $errors[] = 'Sort order must be 1 or greater.';
    }

    return $errors;
}

function topics_index(string $id): void
{
    $subjectId = (int) $id;
    $subject = topics_resolve_manageable_subject($subjectId);
    if (!$subject) {
        abort(404, 'Subject not found.');
    }

    view('topics::index', [
        'subject' => $subject,
        'topics' => topics_all_for_subject($subjectId),
        'role_label' => topics_role_label(),
        'back_subjects_url' => topics_subjects_manage_back_url(),
    ], 'dashboard');
}

function topics_create_form(string $id): void
{
    $subjectId = (int) $id;
    $subject = topics_resolve_manageable_subject($subjectId);
    if (!$subject) {
        abort(404, 'Subject not found.');
    }

    view('topics::create', [
        'subject' => $subject,
        'role_label' => topics_role_label(),
        'back_subjects_url' => topics_subjects_manage_back_url(),
        'next_sort_order' => topics_next_sort_order($subjectId),
    ], 'dashboard');
}

function topics_store(string $id): void
{
    csrf_check();

    $subjectId = (int) $id;
    $subject = topics_resolve_manageable_subject($subjectId);
    if (!$subject) {
        abort(404, 'Subject not found.');
    }

    $title = trim((string) request_input('title', ''));
    $description = trim((string) request_input('description', ''));
    $sortOrder = (int) request_input('sort_order', 1);
    $errors = topics_validate_input($title, $sortOrder);

    if (!empty($errors)) {
        flash('error', implode(' ', $errors));
        flash_old_input();
        redirect('/subjects/' . $subjectId . '/topics/create');
    }

    topics_create($subjectId, [
        'title' => $title,
        'description' => $description,
        'sort_order' => $sortOrder,
    ]);

    clear_old_input();
    flash('success', 'Topic created successfully.');
    redirect('/subjects/' . $subjectId . '/topics');
}

function topics_edit_form(string $id, string $topicId): void
{
    $subjectId = (int) $id;
    $subject = topics_resolve_manageable_subject($subjectId);
    if (!$subject) {
        abort(404, 'Subject not found.');
    }

    $topic = topics_find_in_subject((int) $topicId, $subjectId);
    if (!$topic) {
        abort(404, 'Topic not found.');
    }

    view('topics::edit', [
        'subject' => $subject,
        'topic' => $topic,
        'role_label' => topics_role_label(),
        'back_subjects_url' => topics_subjects_manage_back_url(),
    ], 'dashboard');
}

function topics_update_action(string $id, string $topicId): void
{
    csrf_check();

    $subjectId = (int) $id;
    $subject = topics_resolve_manageable_subject($subjectId);
    if (!$subject) {
        abort(404, 'Subject not found.');
    }

    $topic = topics_find_in_subject((int) $topicId, $subjectId);
    if (!$topic) {
        abort(404, 'Topic not found.');
    }

    $title = trim((string) request_input('title', ''));
    $description = trim((string) request_input('description', ''));
    $sortOrder = (int) request_input('sort_order', 1);
    $errors = topics_validate_input($title, $sortOrder);

    if (!empty($errors)) {
        flash('error', implode(' ', $errors));
        flash_old_input();
        redirect('/subjects/' . $subjectId . '/topics/' . (int) $topicId . '/edit');
    }

    topics_update_data((int) $topicId, $subjectId, [
        'title' => $title,
        'description' => $description,
        'sort_order' => $sortOrder,
    ]);

    clear_old_input();
    flash('success', 'Topic updated successfully.');
    redirect('/subjects/' . $subjectId . '/topics');
}

function topics_delete_action(string $id, string $topicId): void
{
    csrf_check();

    $subjectId = (int) $id;
    $subject = topics_resolve_manageable_subject($subjectId);
    if (!$subject) {
        abort(404, 'Subject not found.');
    }

    $topic = topics_find_in_subject((int) $topicId, $subjectId);
    if (!$topic) {
        abort(404, 'Topic not found.');
    }

    topics_delete_by_id((int) $topicId, $subjectId);
    flash('success', 'Topic "' . (string) $topic['title'] . '" deleted.');
    redirect('/subjects/' . $subjectId . '/topics');
}

function topics_dashboard_index(string $id): void
{
    $subjectId = (int) $id;
    $subject = topics_resolve_readable_subject($subjectId);
    if (!$subject) {
        abort(404, 'Subject not found.');
    }

    view('topics::dashboard_index', [
        'subject' => $subject,
        'topics' => topics_all_for_subject($subjectId),
        'can_manage' => topics_resolve_manageable_subject($subjectId) !== null,
        'role_label' => topics_role_label(),
    ], 'dashboard');
}
