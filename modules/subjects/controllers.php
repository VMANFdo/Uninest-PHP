<?php

/**
 * Subjects Module — Controllers
 */

function subjects_index(): void
{
    $isAdmin = user_role() === 'admin';

    if ($isAdmin) {
        $subjects = subjects_all_admin();
    } else {
        $batchId = (int) (auth_user()['batch_id'] ?? 0);
        $subjects = subjects_all_for_batch($batchId);
    }

    view('subjects::index', [
        'subjects'  => $subjects,
        'is_admin'  => $isAdmin,
    ], 'dashboard');
}

function subjects_create_form(): void
{
    $isAdmin = user_role() === 'admin';
    $batches = $isAdmin ? onboarding_approved_batches() : [];

    view('subjects::create', [
        'is_admin' => $isAdmin,
        'batches'  => $batches,
    ], 'dashboard');
}

function subjects_store(): void
{
    csrf_check();

    $isAdmin     = user_role() === 'admin';
    $code        = strtoupper(trim(request_input('code', '')));
    $name        = trim(request_input('name', ''));
    $description = trim(request_input('description', ''));
    $credits     = (int) request_input('credits', 3);
    $batchId     = $isAdmin ? (int) request_input('batch_id', 0) : (int) (auth_user()['batch_id'] ?? 0);

    $errors = [];
    if (empty($code)) $errors[] = 'Subject code is required.';
    if (empty($name)) $errors[] = 'Subject name is required.';
    if ($credits < 1 || $credits > 10) $errors[] = 'Credits must be between 1 and 10.';

    if ($batchId <= 0) {
        $errors[] = 'A valid batch is required.';
    } else {
        $batch = onboarding_batch_by_id($batchId);
        if (!$batch || $batch['status'] !== 'approved') {
            $errors[] = 'Selected batch is invalid or inactive.';
        }
    }

    if (!empty($code) && $batchId > 0 && subjects_code_exists_in_batch($code, $batchId)) {
        $errors[] = 'A subject with this code already exists in the selected batch.';
    }

    if (!empty($errors)) {
        flash('error', implode(' ', $errors));
        flash_old_input();
        redirect('/subjects/create');
    }

    subjects_create([
        'batch_id'     => $batchId,
        'code'         => $code,
        'name'         => $name,
        'description'  => $description,
        'credits'      => $credits,
    ]);

    clear_old_input();
    flash('success', 'Subject created successfully.');
    redirect('/subjects');
}

function subjects_edit_form(string $id): void
{
    $subjectId = (int) $id;
    $isAdmin   = user_role() === 'admin';
    $batchId   = (int) (auth_user()['batch_id'] ?? 0);
    $subject   = $isAdmin ? subjects_find_admin($subjectId) : subjects_find_for_batch($subjectId, $batchId);

    if (!$subject) {
        abort(404, 'Subject not found.');
    }

    view('subjects::edit', [
        'subject'   => $subject,
        'is_admin'  => $isAdmin,
        'batches'   => $isAdmin ? onboarding_approved_batches() : [],
    ], 'dashboard');
}

function subjects_update_action(string $id): void
{
    csrf_check();

    $subjectId    = (int) $id;
    $isAdmin      = user_role() === 'admin';
    $sessionBatch = (int) (auth_user()['batch_id'] ?? 0);
    $subject      = $isAdmin ? subjects_find_admin($subjectId) : subjects_find_for_batch($subjectId, $sessionBatch);

    if (!$subject) {
        abort(404, 'Subject not found.');
    }

    $code        = strtoupper(trim(request_input('code', '')));
    $name        = trim(request_input('name', ''));
    $description = trim(request_input('description', ''));
    $credits     = (int) request_input('credits', 3);
    $batchId     = $isAdmin ? (int) request_input('batch_id', 0) : (int) $subject['batch_id'];

    $errors = [];
    if (empty($code)) $errors[] = 'Subject code is required.';
    if (empty($name)) $errors[] = 'Subject name is required.';
    if ($credits < 1 || $credits > 10) $errors[] = 'Credits must be between 1 and 10.';

    if ($batchId <= 0) {
        $errors[] = 'A valid batch is required.';
    } else {
        $batch = onboarding_batch_by_id($batchId);
        if (!$batch || $batch['status'] !== 'approved') {
            $errors[] = 'Selected batch is invalid or inactive.';
        }
    }

    if (!empty($code) && $batchId > 0 && subjects_code_exists_in_batch($code, $batchId, $subjectId)) {
        $errors[] = 'A subject with this code already exists in the selected batch.';
    }

    if (!empty($errors)) {
        flash('error', implode(' ', $errors));
        flash_old_input();
        redirect('/subjects/' . $subjectId . '/edit');
    }

    subjects_update_data($subjectId, [
        'batch_id'     => $batchId,
        'code'         => $code,
        'name'         => $name,
        'description'  => $description,
        'credits'      => $credits,
    ]);

    clear_old_input();
    flash('success', 'Subject updated successfully.');
    redirect('/subjects');
}

function subjects_delete_action(string $id): void
{
    csrf_check();

    $subjectId = (int) $id;
    $isAdmin   = user_role() === 'admin';
    $batchId   = (int) (auth_user()['batch_id'] ?? 0);
    $subject   = $isAdmin ? subjects_find_admin($subjectId) : subjects_find_for_batch($subjectId, $batchId);

    if (!$subject) {
        abort(404, 'Subject not found.');
    }

    subjects_delete_by_id($subjectId);
    flash('success', 'Subject "' . $subject['name'] . '" deleted.');
    redirect('/subjects');
}

function subjects_student_list(): void
{
    $batchId = (int) (auth_user()['batch_id'] ?? 0);
    $subjects = subjects_all_for_batch($batchId);

    view('subjects::student_list', ['subjects' => $subjects], 'dashboard');
}
