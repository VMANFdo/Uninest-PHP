<?php

/**
 * Kuppi Module — Conductor Application Controllers
 */

function kuppi_conductor_apply_form(string $id): void
{
    $requestId = (int) $id;
    $request = kuppi_resolve_readable_request($requestId);
    if (!$request) {
        abort(404, 'Kuppi request not found.');
    }

    if (!kuppi_user_can_apply_as_conductor($request)) {
        abort(403, 'Only students can apply as conductors for open requests in their batch.');
    }

    $existingApplication = kuppi_find_user_conductor_application($requestId, (int) auth_id());
    if ($existingApplication) {
        flash('warning', 'You have already applied. You can edit your application.');
        redirect('/dashboard/kuppi/' . $requestId . '/conductors/' . (int) ($existingApplication['id'] ?? 0) . '/edit');
    }

    view('kuppi::conductor_apply', [
        'request' => $request,
        'availability_options' => kuppi_conductor_availability_options(),
        'back_request_url' => kuppi_request_url($request),
        'is_edit' => false,
        'form_action' => '/dashboard/kuppi/' . $requestId . '/conductors/apply',
        'submit_label' => 'Submit Application',
    ], 'dashboard');
}

function kuppi_conductor_apply_action(string $id): void
{
    csrf_check();

    $requestId = (int) $id;
    $request = kuppi_resolve_readable_request($requestId);
    if (!$request) {
        abort(404, 'Kuppi request not found.');
    }

    if (!kuppi_user_can_apply_as_conductor($request)) {
        abort(403, 'Only students can apply as conductors for open requests in their batch.');
    }

    $existingApplication = kuppi_find_user_conductor_application($requestId, (int) auth_id());
    if ($existingApplication) {
        flash('warning', 'You have already applied as a conductor for this request.');
        redirect(kuppi_request_url($request));
    }

    $validated = kuppi_validate_conductor_application_input();
    if (!empty($validated['errors'])) {
        flash('error', implode(' ', $validated['errors']));
        flash_old_input();
        redirect('/dashboard/kuppi/' . $requestId . '/conductors/apply');
    }

    try {
        kuppi_create_conductor_application([
            'request_id' => $requestId,
            'applicant_user_id' => (int) auth_id(),
            'motivation' => $validated['data']['motivation'],
            'availability_csv' => $validated['data']['availability_csv'],
        ]);
    } catch (Throwable) {
        flash('error', 'Unable to submit conductor application right now.');
        flash_old_input();
        redirect('/dashboard/kuppi/' . $requestId . '/conductors/apply');
    }

    clear_old_input();
    flash('success', 'Conductor application submitted.');
    redirect(kuppi_request_url($request));
}

function kuppi_conductor_edit_form(string $id, string $applicationId): void
{
    $requestId = (int) $id;
    $request = kuppi_resolve_readable_request($requestId);
    if (!$request) {
        abort(404, 'Kuppi request not found.');
    }

    $application = kuppi_find_conductor_application_for_request((int) $applicationId, $requestId);
    if (!$application) {
        abort(404, 'Conductor application not found.');
    }

    if (!kuppi_can_manage_own_conductor_application($request, $application)) {
        abort(403, 'Only the applicant can edit this conductor application while the request is open.');
    }

    view('kuppi::conductor_apply', [
        'request' => $request,
        'application' => $application,
        'availability_options' => kuppi_conductor_availability_options(),
        'back_request_url' => kuppi_request_url($request),
        'is_edit' => true,
        'form_action' => '/dashboard/kuppi/' . $requestId . '/conductors/' . (int) $application['id'],
        'delete_action' => '/dashboard/kuppi/' . $requestId . '/conductors/' . (int) $application['id'] . '/delete',
        'submit_label' => 'Update Application',
    ], 'dashboard');
}

function kuppi_conductor_update_action(string $id, string $applicationId): void
{
    csrf_check();

    $requestId = (int) $id;
    $request = kuppi_resolve_readable_request($requestId);
    if (!$request) {
        abort(404, 'Kuppi request not found.');
    }

    $application = kuppi_find_conductor_application_for_request((int) $applicationId, $requestId);
    if (!$application) {
        abort(404, 'Conductor application not found.');
    }

    if (!kuppi_can_manage_own_conductor_application($request, $application)) {
        abort(403, 'Only the applicant can update this conductor application while the request is open.');
    }

    $validated = kuppi_validate_conductor_application_input();
    if (!empty($validated['errors'])) {
        flash('error', implode(' ', $validated['errors']));
        flash_old_input();
        redirect('/dashboard/kuppi/' . $requestId . '/conductors/' . (int) $application['id'] . '/edit');
    }

    try {
        $updated = kuppi_update_conductor_application_by_owner(
            (int) $application['id'],
            $requestId,
            (int) auth_id(),
            [
                'motivation' => $validated['data']['motivation'],
                'availability_csv' => $validated['data']['availability_csv'],
            ]
        );
    } catch (Throwable) {
        flash('error', 'Unable to update conductor application right now.');
        flash_old_input();
        redirect('/dashboard/kuppi/' . $requestId . '/conductors/' . (int) $application['id'] . '/edit');
    }

    if (!$updated) {
        flash('error', 'Unable to update this conductor application.');
        flash_old_input();
        redirect('/dashboard/kuppi/' . $requestId . '/conductors/' . (int) $application['id'] . '/edit');
    }

    clear_old_input();
    flash('success', 'Conductor application updated.');
    redirect(kuppi_request_url($request));
}

function kuppi_conductor_delete_action(string $id, string $applicationId): void
{
    csrf_check();

    $requestId = (int) $id;
    $request = kuppi_resolve_readable_request($requestId);
    if (!$request) {
        abort(404, 'Kuppi request not found.');
    }

    $application = kuppi_find_conductor_application_for_request((int) $applicationId, $requestId);
    if (!$application) {
        abort(404, 'Conductor application not found.');
    }

    if (!kuppi_can_manage_own_conductor_application($request, $application)) {
        abort(403, 'Only the applicant can delete this conductor application while the request is open.');
    }

    $returnTo = kuppi_resolve_valid_return_to((string) request_input('return_to', ''), $request);

    try {
        $deleted = kuppi_delete_conductor_application_by_owner(
            (int) $application['id'],
            $requestId,
            (int) auth_id()
        );
    } catch (Throwable) {
        flash('error', 'Unable to delete conductor application right now.');
        redirect($returnTo);
    }

    if (!$deleted) {
        flash('error', 'Unable to delete this conductor application.');
        redirect($returnTo);
    }

    clear_old_input();
    flash('success', 'Conductor application deleted.');
    redirect($returnTo);
}

function kuppi_conductor_vote_action(string $id, string $applicationId): void
{
    csrf_check();

    $requestId = (int) $id;
    $request = kuppi_resolve_readable_request($requestId);
    if (!$request) {
        abort(404, 'Kuppi request not found.');
    }

    $returnTo = kuppi_resolve_valid_return_to((string) request_input('return_to', ''), $request);
    if (!kuppi_user_can_vote_conductor($request)) {
        abort(403, 'Only students in this batch can vote for conductors.');
    }

    $application = kuppi_find_conductor_application_for_request((int) $applicationId, $requestId);
    if (!$application) {
        abort(404, 'Conductor application not found.');
    }

    if ((int) ($application['applicant_user_id'] ?? 0) === (int) auth_id()) {
        flash('error', 'You cannot vote for your own conductor application.');
        redirect($returnTo);
    }

    try {
        $isVoted = kuppi_toggle_conductor_vote((int) $applicationId, (int) auth_id());
    } catch (Throwable) {
        flash('error', 'Unable to save conductor vote right now.');
        redirect($returnTo);
    }

    flash('success', $isVoted ? 'Conductor vote added.' : 'Conductor vote removed.');
    redirect($returnTo);
}

function kuppi_conductor_vote_delete_action(string $id, string $applicationId): void
{
    csrf_check();

    $requestId = (int) $id;
    $request = kuppi_resolve_readable_request($requestId);
    if (!$request) {
        abort(404, 'Kuppi request not found.');
    }

    $returnTo = kuppi_resolve_valid_return_to((string) request_input('return_to', ''), $request);
    if (!kuppi_user_can_vote_conductor($request)) {
        abort(403, 'Only students in this batch can remove conductor votes.');
    }

    $application = kuppi_find_conductor_application_for_request((int) $applicationId, $requestId);
    if (!$application) {
        abort(404, 'Conductor application not found.');
    }

    if ((int) ($application['applicant_user_id'] ?? 0) === (int) auth_id()) {
        flash('error', 'You cannot vote for your own conductor application.');
        redirect($returnTo);
    }

    try {
        $removed = kuppi_remove_conductor_vote((int) $applicationId, (int) auth_id());
    } catch (Throwable) {
        flash('error', 'Unable to remove conductor vote right now.');
        redirect($returnTo);
    }

    flash('success', $removed ? 'Conductor vote removed.' : 'No active conductor vote found.');
    redirect($returnTo);
}

