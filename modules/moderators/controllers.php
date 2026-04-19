<?php

/**
 * Moderators Module — Controllers
 */

function moderators_index(): void
{
    middleware_exact_role('admin');

    view('moderators::index', [
        'moderators' => moderators_admin_all(),
    ], 'dashboard');
}

function moderators_create_form(): void
{
    middleware_exact_role('admin');

    view('moderators::create', [
        'universities' => universities_active(),
        'batches' => onboarding_approved_batches(),
    ], 'dashboard');
}

function moderators_store(): void
{
    csrf_check();
    middleware_exact_role('admin');

    $name = trim(request_input('name', ''));
    $email = trim(request_input('email', ''));
    $password = request_input('password', '');
    $passwordConfirmation = request_input('password_confirmation', '');
    $academicYear = (int) request_input('academic_year', 0);
    $universityId = (int) request_input('university_id', 0);
    $batchId = (int) request_input('batch_id', 0);
    $resolvedBatchId = $batchId > 0 ? $batchId : null;

    $errors = [];
    if ($name === '') $errors[] = 'Name is required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email is required.';
    if (moderators_email_exists($email)) $errors[] = 'An account with this email already exists.';
    if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
    if ($password !== $passwordConfirmation) $errors[] = 'Passwords do not match.';
    if ($academicYear < 1 || $academicYear > 4) $errors[] = 'Academic year must be between 1 and 4.';
    if ($universityId <= 0 || !university_is_active($universityId)) $errors[] = 'Select a valid university.';

    if ($resolvedBatchId !== null) {
        $batch = onboarding_batch_by_id($resolvedBatchId);
        if (!$batch || $batch['status'] !== 'approved') {
            $errors[] = 'Select a valid approved batch.';
        } elseif ((int) $batch['university_id'] !== $universityId) {
            $errors[] = 'Selected university does not match the selected batch.';
        }
    }

    if (!empty($errors)) {
        flash('error', implode(' ', $errors));
        flash_old_input();
        redirect('/admin/moderators/create');
    }

    try {
        moderators_create_admin([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'academic_year' => $academicYear,
            'university_id' => $universityId,
            'batch_id' => $resolvedBatchId,
        ]);
    } catch (Throwable) {
        flash('error', 'Unable to create moderator right now. Please try again.');
        flash_old_input();
        redirect('/admin/moderators/create');
    }

    clear_old_input();
    flash('success', $resolvedBatchId !== null
        ? 'Moderator account created and assigned to batch successfully.'
        : 'Moderator account created successfully. You can assign a batch later.');
    redirect('/admin/moderators');
}

function moderators_edit_form(string $id): void
{
    middleware_exact_role('admin');

    $moderatorId = (int) $id;
    $moderator = moderators_find_admin($moderatorId);
    if (!$moderator) {
        abort(404, 'Moderator not found.');
    }

    view('moderators::edit', [
        'moderator' => $moderator,
        'universities' => universities_active(),
        'batches' => onboarding_approved_batches(),
    ], 'dashboard');
}

function moderators_update_action(string $id): void
{
    csrf_check();
    middleware_exact_role('admin');

    $moderatorId = (int) $id;
    $moderator = moderators_find_admin($moderatorId);
    if (!$moderator) {
        abort(404, 'Moderator not found.');
    }

    $name = trim(request_input('name', ''));
    $email = trim(request_input('email', ''));
    $password = request_input('password', '');
    $passwordConfirmation = request_input('password_confirmation', '');
    $academicYear = (int) request_input('academic_year', 0);
    $universityId = (int) request_input('university_id', 0);
    $batchId = (int) request_input('batch_id', 0);
    $resolvedBatchId = $batchId > 0 ? $batchId : null;

    $errors = [];
    if ($name === '') $errors[] = 'Name is required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email is required.';
    if (moderators_email_exists($email, $moderatorId)) $errors[] = 'An account with this email already exists.';
    if ($academicYear < 1 || $academicYear > 4) $errors[] = 'Academic year must be between 1 and 4.';
    if ($universityId <= 0 || !university_is_active($universityId)) $errors[] = 'Select a valid university.';

    if ($password !== '' && strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters when provided.';
    }
    if ($password !== '' && $password !== $passwordConfirmation) {
        $errors[] = 'Passwords do not match.';
    }

    if ($resolvedBatchId !== null) {
        $batch = onboarding_batch_by_id($resolvedBatchId);
        $isOwnedBatch = (int) ($moderator['owned_batch_id'] ?? 0) === $resolvedBatchId;

        if (!$batch) {
            $errors[] = 'Selected batch is invalid.';
        } elseif (!$isOwnedBatch && $batch['status'] !== 'approved') {
            $errors[] = 'Select an approved batch for assignment.';
        } elseif ((int) $batch['university_id'] !== $universityId) {
            $errors[] = 'Selected university does not match the selected batch.';
        }
    }

    if (!empty($errors)) {
        flash('error', implode(' ', $errors));
        flash_old_input();
        redirect('/admin/moderators/' . $moderatorId . '/edit');
    }

    try {
        $result = moderators_update_admin($moderatorId, [
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'academic_year' => $academicYear,
            'university_id' => $universityId,
            'batch_id' => $resolvedBatchId,
        ]);
    } catch (Throwable) {
        flash('error', 'Unable to update moderator right now. Please try again.');
        flash_old_input();
        redirect('/admin/moderators/' . $moderatorId . '/edit');
    }

    if ($result === 'not_found') {
        abort(404, 'Moderator not found.');
    }

    if ($result === 'owned_batch_lock') {
        flash('error', 'This moderator is the primary owner of a batch. Reassign the batch owner before changing their batch assignment.');
        flash_old_input();
        redirect('/admin/moderators/' . $moderatorId . '/edit');
    }

    if ($result === 'owned_batch_university_mismatch') {
        flash('error', 'Moderator university must match the university of their owned batch.');
        flash_old_input();
        redirect('/admin/moderators/' . $moderatorId . '/edit');
    }

    if ($result === 'invalid_batch' || $result === 'university_mismatch') {
        flash('error', 'Invalid batch assignment for this moderator.');
        flash_old_input();
        redirect('/admin/moderators/' . $moderatorId . '/edit');
    }

    clear_old_input();
    flash('success', 'Moderator updated successfully.');
    redirect('/admin/moderators');
}

function moderators_delete_action(string $id): void
{
    csrf_check();
    middleware_exact_role('admin');

    $moderatorId = (int) $id;
    $moderator = moderators_find_admin($moderatorId);
    if (!$moderator) {
        abort(404, 'Moderator not found.');
    }

    try {
        $result = moderators_delete_admin($moderatorId);
    } catch (Throwable) {
        flash('error', 'Unable to delete moderator right now. Please try again.');
        redirect('/admin/moderators');
    }

    if ($result === 'has_owned_batch') {
        flash('error', 'Cannot delete this moderator because they are the primary owner of a batch. Reassign or delete that batch first.');
        redirect('/admin/moderators');
    }

    if ($result === 'not_found') {
        abort(404, 'Moderator not found.');
    }

    flash('success', 'Moderator "' . $moderator['name'] . '" deleted.');
    redirect('/admin/moderators');
}
