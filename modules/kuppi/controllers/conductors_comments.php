<?php

/**
 * Kuppi Module — Controllers (Conductors and comments)
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
        flash('warning', 'You have already applied as a conductor for this request.');
        redirect(kuppi_request_url($request));
    }

    view('kuppi::conductor_apply', [
        'request' => $request,
        'availability_options' => kuppi_conductor_availability_options(),
        'back_request_url' => kuppi_request_url($request),
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

function kuppi_comment_store(string $id): void
{
    csrf_check();

    $requestId = (int) $id;
    $request = kuppi_resolve_readable_request($requestId);
    if (!$request) {
        abort(404, 'Kuppi request not found.');
    }

    $requestPath = kuppi_request_url($request);
    $validation = comments_validate_body((string) request_input('body', ''));
    if (!empty($validation['errors'])) {
        flash('error', implode(' ', $validation['errors']));
        redirect($requestPath . '#kuppi-comments');
    }

    $targetType = kuppi_comment_target_type();
    $parentCommentId = (int) request_input('parent_comment_id', 0);
    $parentId = null;
    $depth = 0;
    $maxDepth = comments_max_depth_for_target($targetType);

    if ($parentCommentId > 0) {
        $parent = comments_find_target_comment($parentCommentId, $targetType, $requestId);
        if (!$parent) {
            flash('error', 'Reply target not found.');
            redirect($requestPath . '#kuppi-comments');
        }

        $depth = (int) ($parent['depth'] ?? 0) + 1;
        if ($depth > $maxDepth) {
            flash('error', 'Reply depth limit reached.');
            redirect($requestPath . '#kuppi-comments');
        }

        $parentId = $parentCommentId;
    }

    try {
        comments_insert($targetType, $requestId, (int) auth_id(), $validation['body'], $parentId, $depth);
    } catch (Throwable) {
        flash('error', 'Unable to post comment right now. Please try again.');
        redirect($requestPath . '#kuppi-comments');
    }

    flash('success', 'Comment posted.');
    redirect($requestPath . '#kuppi-comments');
}

function kuppi_comment_update(string $id, string $commentId): void
{
    csrf_check();

    $requestId = (int) $id;
    $request = kuppi_resolve_readable_request($requestId);
    if (!$request) {
        abort(404, 'Kuppi request not found.');
    }

    $requestPath = kuppi_request_url($request);
    $commentIdInt = (int) $commentId;
    $comment = comments_find_target_comment($commentIdInt, kuppi_comment_target_type(), $requestId);
    if (!$comment) {
        abort(404, 'Comment not found.');
    }

    if ((int) ($comment['user_id'] ?? 0) !== (int) auth_id()) {
        abort(403, 'You can only edit your own comments.');
    }

    $validation = comments_validate_body((string) request_input('body', ''));
    if (!empty($validation['errors'])) {
        flash('error', implode(' ', $validation['errors']));
        redirect($requestPath . '#kuppi-comments');
    }

    if (!comments_update_body_by_author($commentIdInt, (int) auth_id(), $validation['body'])) {
        flash('error', 'Unable to update this comment.');
        redirect($requestPath . '#kuppi-comments');
    }

    flash('success', 'Comment updated.');
    redirect($requestPath . '#kuppi-comments');
}

function kuppi_comment_delete(string $id, string $commentId): void
{
    csrf_check();

    $requestId = (int) $id;
    $request = kuppi_resolve_readable_request($requestId);
    if (!$request) {
        abort(404, 'Kuppi request not found.');
    }

    $requestPath = kuppi_request_url($request);
    $commentIdInt = (int) $commentId;
    $comment = comments_find_target_comment($commentIdInt, kuppi_comment_target_type(), $requestId);
    if (!$comment) {
        abort(404, 'Comment not found.');
    }

    if (!kuppi_comment_can_delete($request, $comment)) {
        abort(403, 'You do not have permission to delete this comment.');
    }

    if (!comments_delete_by_id($commentIdInt)) {
        flash('error', 'Unable to delete this comment.');
        redirect($requestPath . '#kuppi-comments');
    }

    flash('success', 'Comment deleted.');
    redirect($requestPath . '#kuppi-comments');
}
