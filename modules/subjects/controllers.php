<?php

/**
 * Subjects Module — Controllers
 */

// ──────────────────────────────────────
// Moderator CRUD
// ──────────────────────────────────────

function subjects_index(): void
{
    $subjects = subjects_all();
    view('subjects::index', ['subjects' => $subjects], 'dashboard');
}

function subjects_create_form(): void
{
    view('subjects::create', [], 'dashboard');
}

function subjects_store(): void
{
    csrf_check();

    $code        = strtoupper(trim(request_input('code', '')));
    $name        = trim(request_input('name', ''));
    $description = trim(request_input('description', ''));
    $credits     = (int) request_input('credits', 3);

    // Validation
    $errors = [];
    if (empty($code))   $errors[] = 'Subject code is required.';
    if (empty($name))   $errors[] = 'Subject name is required.';
    if ($credits < 1 || $credits > 10) $errors[] = 'Credits must be between 1 and 10.';

    // Check for duplicate code
    if (!empty($code)) {
        $existing = db_fetch('SELECT id FROM subjects WHERE code = ?', [$code]);
        if ($existing) $errors[] = 'A subject with this code already exists.';
    }

    if (!empty($errors)) {
        flash('error', implode(' ', $errors));
        flash_old_input();
        redirect('/subjects/create');
    }

    subjects_create([
        'code'        => $code,
        'name'        => $name,
        'description' => $description,
        'credits'     => $credits,
    ]);

    flash('success', 'Subject created successfully.');
    redirect('/subjects');
}

function subjects_edit_form(string $id): void
{
    $subject = subjects_find((int) $id);
    if (!$subject) abort(404, 'Subject not found.');

    view('subjects::edit', ['subject' => $subject], 'dashboard');
}

function subjects_update_action(string $id): void
{
    csrf_check();

    $subject = subjects_find((int) $id);
    if (!$subject) abort(404, 'Subject not found.');

    $code        = strtoupper(trim(request_input('code', '')));
    $name        = trim(request_input('name', ''));
    $description = trim(request_input('description', ''));
    $credits     = (int) request_input('credits', 3);

    // Validation
    $errors = [];
    if (empty($code))   $errors[] = 'Subject code is required.';
    if (empty($name))   $errors[] = 'Subject name is required.';
    if ($credits < 1 || $credits > 10) $errors[] = 'Credits must be between 1 and 10.';

    // Check for duplicate code (excluding current)
    if (!empty($code)) {
        $existing = db_fetch('SELECT id FROM subjects WHERE code = ? AND id != ?', [$code, (int) $id]);
        if ($existing) $errors[] = 'A subject with this code already exists.';
    }

    if (!empty($errors)) {
        flash('error', implode(' ', $errors));
        flash_old_input();
        redirect('/subjects/' . $id . '/edit');
    }

    subjects_update_data((int) $id, [
        'code'        => $code,
        'name'        => $name,
        'description' => $description,
        'credits'     => $credits,
    ]);

    flash('success', 'Subject updated successfully.');
    redirect('/subjects');
}

function subjects_delete_action(string $id): void
{
    csrf_check();

    $subject = subjects_find((int) $id);
    if (!$subject) abort(404, 'Subject not found.');

    subjects_delete_by_id((int) $id);

    flash('success', 'Subject "' . $subject['name'] . '" deleted.');
    redirect('/subjects');
}

// ──────────────────────────────────────
// Student View
// ──────────────────────────────────────

function subjects_student_list(): void
{
    $subjects = subjects_all();
    view('subjects::student_list', ['subjects' => $subjects], 'dashboard');
}
