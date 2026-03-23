<?php

/**
 * Batches Module — Controllers
 */

function batches_index(): void
{
    middleware_exact_role('admin');

    view('batches::index', [
        'batches' => batches_admin_all(),
    ], 'dashboard');
}

function batches_create_form(): void
{
    middleware_exact_role('admin');

    view('batches::create', [
        'universities' => universities_active(),
        'moderators' => batches_available_primary_moderators(),
    ], 'dashboard');
}

function batches_store(): void
{
    csrf_check();
    middleware_exact_role('admin');

    $name = trim(request_input('name', ''));
    $program = trim(request_input('program', ''));
    $intakeYear = (int) request_input('intake_year', 0);
    $universityId = (int) request_input('university_id', 0);
    $moderatorUserId = (int) request_input('moderator_user_id', 0);
    $batchCode = strtoupper(trim(request_input('batch_code', '')));

    $errors = [];
    if ($name === '') $errors[] = 'Batch name is required.';
    if ($program === '') $errors[] = 'Program is required.';
    if ($intakeYear < 2000 || $intakeYear > 2100) $errors[] = 'Intake year is invalid.';
    if ($universityId <= 0 || !university_is_active($universityId)) $errors[] = 'Select a valid university.';

    $moderator = batches_find_available_primary_moderator($moderatorUserId);
    if (!$moderator) {
        $errors[] = 'Select an available moderator who is not assigned to any batch.';
    } elseif ((int) ($moderator['university_id'] ?? 0) !== $universityId) {
        $errors[] = 'Selected moderator university does not match the batch university.';
    }

    if ($batchCode !== '' && onboarding_batch_code_exists($batchCode)) {
        $errors[] = 'Batch ID already exists. Use a unique value or leave it blank for auto-generation.';
    }
    if ($batchCode !== '' && !preg_match('/^[A-Z0-9-]{4,20}$/', $batchCode)) {
        $errors[] = 'Batch ID format is invalid. Use 4-20 characters: A-Z, 0-9, and dashes only.';
    }

    if (!empty($errors)) {
        flash('error', implode(' ', $errors));
        flash_old_input();
        redirect('/admin/batches/create');
    }

    try {
        $batchId = batches_create_admin([
            'name' => $name,
            'program' => $program,
            'intake_year' => $intakeYear,
            'university_id' => $universityId,
            'moderator_user_id' => $moderatorUserId,
            'batch_code' => $batchCode,
        ], (int) auth_id());

        if ($batchId <= 0) {
            flash('error', 'Unable to create batch. Please verify moderator assignment and try again.');
            flash_old_input();
            redirect('/admin/batches/create');
        }
    } catch (Throwable) {
        flash('error', 'Unable to create batch right now. Please try again.');
        flash_old_input();
        redirect('/admin/batches/create');
    }

    clear_old_input();
    flash('success', 'Batch created and approved successfully. Primary moderator assigned.');
    redirect('/admin/batches');
}

function batches_edit_form(string $id): void
{
    middleware_exact_role('admin');

    $batchId = (int) $id;
    $batch = batches_find_admin($batchId);
    if (!$batch) {
        abort(404, 'Batch not found.');
    }

    view('batches::edit', [
        'batch' => $batch,
        'universities' => universities_active(),
        'moderators' => batches_primary_moderator_candidates($batchId),
    ], 'dashboard');
}

function batches_update_action(string $id): void
{
    csrf_check();
    middleware_exact_role('admin');

    $batchId = (int) $id;
    $batch = batches_find_admin($batchId);
    if (!$batch) {
        abort(404, 'Batch not found.');
    }

    $name = trim(request_input('name', ''));
    $program = trim(request_input('program', ''));
    $intakeYear = (int) request_input('intake_year', 0);
    $universityId = (int) request_input('university_id', 0);
    $moderatorUserId = (int) request_input('moderator_user_id', 0);
    $batchCode = strtoupper(trim(request_input('batch_code', '')));
    $status = trim(request_input('status', 'approved'));
    $rejectionReason = trim(request_input('rejection_reason', ''));

    $errors = [];
    if ($name === '') $errors[] = 'Batch name is required.';
    if ($program === '') $errors[] = 'Program is required.';
    if ($intakeYear < 2000 || $intakeYear > 2100) $errors[] = 'Intake year is invalid.';
    if ($universityId <= 0 || !university_is_active($universityId)) $errors[] = 'Select a valid university.';

    if ($batchCode === '') {
        $errors[] = 'Batch ID is required.';
    } elseif (!preg_match('/^[A-Z0-9-]{4,20}$/', $batchCode)) {
        $errors[] = 'Batch ID format is invalid. Use 4-20 characters: A-Z, 0-9, and dashes only.';
    } elseif (batches_batch_code_exists_for_other($batchCode, $batchId)) {
        $errors[] = 'Batch ID already exists. Use a unique value.';
    }

    $allowedStatuses = ['pending', 'approved', 'rejected', 'inactive'];
    if (!in_array($status, $allowedStatuses, true)) {
        $errors[] = 'Selected status is invalid.';
    }

    if ($status === 'rejected' && $rejectionReason === '') {
        $errors[] = 'Rejection reason is required when status is rejected.';
    }

    $moderator = batches_find_primary_moderator_candidate($moderatorUserId, $batchId);
    if (!$moderator) {
        $errors[] = 'Select a valid moderator candidate for this batch.';
    } elseif ((int) ($moderator['university_id'] ?? 0) !== $universityId) {
        $errors[] = 'Selected moderator university does not match the batch university.';
    }

    if (!empty($errors)) {
        flash('error', implode(' ', $errors));
        flash_old_input();
        redirect('/admin/batches/' . $batchId . '/edit');
    }

    try {
        if (!batches_update_admin($batchId, [
            'name' => $name,
            'program' => $program,
            'intake_year' => $intakeYear,
            'university_id' => $universityId,
            'moderator_user_id' => $moderatorUserId,
            'batch_code' => $batchCode,
            'status' => $status,
            'rejection_reason' => $rejectionReason,
        ], (int) auth_id())) {
            flash('error', 'Unable to update batch. Check moderator and university assignments.');
            flash_old_input();
            redirect('/admin/batches/' . $batchId . '/edit');
        }
    } catch (Throwable) {
        flash('error', 'Unable to update batch right now. Please try again.');
        flash_old_input();
        redirect('/admin/batches/' . $batchId . '/edit');
    }

    clear_old_input();
    flash('success', 'Batch updated successfully.');
    redirect('/admin/batches');
}

function batches_delete_action(string $id): void
{
    csrf_check();
    middleware_exact_role('admin');

    $batchId = (int) $id;
    $batch = batches_find_admin($batchId);
    if (!$batch) {
        abort(404, 'Batch not found.');
    }

    if (!batches_delete_admin($batchId)) {
        flash('error', 'Unable to delete this batch.');
        redirect('/admin/batches');
    }

    flash('success', 'Batch "' . $batch['name'] . '" deleted.');
    redirect('/admin/batches');
}
