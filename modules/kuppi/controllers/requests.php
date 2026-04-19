<?php

/**
 * Kuppi Module — Request Feed and Request CRUD Controllers
 */

function kuppi_index(): void
{
    $role = (string) user_role();
    $isAdmin = $role === 'admin';
    $viewerId = (int) auth_id();
    $batchOptions = $isAdmin ? kuppi_batch_options_for_admin() : [];
    $selectedBatchId = 0;
    $activeBatch = null;

    if ($isAdmin) {
        $selectedBatchId = (int) request_input('batch_id', 0);
        if ($selectedBatchId > 0) {
            $activeBatch = kuppi_find_batch_option_by_id($selectedBatchId);
            if (!$activeBatch) {
                $selectedBatchId = 0;
            }
        }
    } else {
        $selectedBatchId = (int) (auth_user()['batch_id'] ?? 0);
        if ($selectedBatchId <= 0) {
            abort(403, 'You are not assigned to a batch.');
        }
        $activeBatch = kuppi_find_batch_option_by_id($selectedBatchId);
    }

    $subjectOptions = $selectedBatchId > 0
        ? kuppi_subject_options_for_batch($selectedBatchId)
        : [];

    $selectedSubjectId = (int) request_input('subject_id', 0);
    if ($selectedSubjectId > 0 && !kuppi_subject_exists_in_batch($selectedSubjectId, $selectedBatchId)) {
        $selectedSubjectId = 0;
    }

    $selectedSort = trim((string) request_input('sort', 'most_votes'));
    if (!in_array($selectedSort, kuppi_sort_options(), true)) {
        $selectedSort = 'most_votes';
    }

    $selectedSearchQuery = trim((string) request_input('q', ''));
    if (strlen($selectedSearchQuery) > 120) {
        $selectedSearchQuery = substr($selectedSearchQuery, 0, 120);
    }

    $selectedPage = max(1, min(50, (int) request_input('page', 1)));
    $selectedSubjectFilter = $selectedSubjectId > 0 ? $selectedSubjectId : null;

    $feedPage = $selectedBatchId > 0
        ? kuppi_requests_for_batch(
            $selectedBatchId,
            $selectedSubjectFilter,
            $selectedSort,
            $viewerId,
            $selectedSearchQuery,
            $selectedPage,
            kuppi_feed_per_page()
        )
        : ['requests' => [], 'has_more' => false];

    $requestCount = $selectedBatchId > 0
        ? kuppi_requests_count_for_batch($selectedBatchId, $selectedSubjectFilter, $selectedSearchQuery)
        : 0;

    view('kuppi::index', [
        'is_admin' => $isAdmin,
        'batch_options' => $batchOptions,
        'active_batch' => $activeBatch,
        'selected_batch_id' => $selectedBatchId,
        'subject_options' => $subjectOptions,
        'selected_subject_id' => $selectedSubjectId,
        'selected_sort' => $selectedSort,
        'selected_search_query' => $selectedSearchQuery,
        'selected_page' => $selectedPage,
        'requests' => (array) ($feedPage['requests'] ?? []),
        'has_more_requests' => !empty($feedPage['has_more']),
        'request_count' => $requestCount,
        'can_create' => kuppi_user_can_create(),
    ], 'dashboard');
}


function kuppi_create_form(): void
{
    if (!kuppi_user_can_create()) {
        abort(403, 'Only students and coordinators can request Kuppi sessions.');
    }

    $batchId = (int) (auth_user()['batch_id'] ?? 0);
    if ($batchId <= 0) {
        abort(403, 'You are not assigned to a batch.');
    }

    view('kuppi::create', [
        'active_batch' => kuppi_find_batch_option_by_id($batchId),
        'subject_options' => kuppi_subject_options_for_batch($batchId),
    ], 'dashboard');
}

function kuppi_store(): void
{
    csrf_check();

    if (!kuppi_user_can_create()) {
        abort(403, 'Only students and coordinators can request Kuppi sessions.');
    }

    $batchId = (int) (auth_user()['batch_id'] ?? 0);
    if ($batchId <= 0) {
        abort(403, 'You are not assigned to a batch.');
    }

    $validated = kuppi_validate_request_input($batchId);
    if (!empty($validated['errors'])) {
        flash('error', implode(' ', $validated['errors']));
        flash_old_input();
        redirect('/dashboard/kuppi/create');
    }

    try {
        $requestId = kuppi_create_request([
            'batch_id' => $batchId,
            'subject_id' => (int) $validated['data']['subject_id'],
            'requested_by_user_id' => (int) auth_id(),
            'title' => $validated['data']['title'],
            'description' => $validated['data']['description'],
            'tags_csv' => $validated['data']['tags_csv'],
            'status' => 'open',
        ]);
    } catch (Throwable) {
        flash('error', 'Unable to create request right now. Please try again.');
        flash_old_input();
        redirect('/dashboard/kuppi/create');
    }

    clear_old_input();
    flash('success', 'Kuppi request created.');
    redirect('/dashboard/kuppi/' . $requestId);
}

function kuppi_show(string $id): void
{
    $requestId = (int) $id;
    $request = kuppi_resolve_readable_request($requestId);
    if (!$request) {
        abort(404, 'Kuppi request not found.');
    }

    $viewerId = (int) auth_id();
    $conductorApplications = kuppi_conductor_applications_for_request($requestId, $viewerId);
    $viewerApplication = kuppi_find_user_conductor_application($requestId, $viewerId);

    $topVoteApplicationId = 0;
    $topVoteCount = -1;
    foreach ($conductorApplications as &$application) {
        $application['availability'] = kuppi_conductor_availability_from_csv((string) ($application['availability_csv'] ?? ''));
        $voteCount = (int) ($application['vote_count'] ?? 0);
        if ($voteCount > $topVoteCount) {
            $topVoteCount = $voteCount;
            $topVoteApplicationId = (int) ($application['id'] ?? 0);
        }
    }
    unset($application);

    $commentsTree = comments_tree_for_target(kuppi_comment_target_type(), $requestId);
    $commentsTree = kuppi_enrich_comment_tree($commentsTree, $request);
    $commentCount = comments_count_for_target(kuppi_comment_target_type(), $requestId);

    view('kuppi::show', [
        'request' => $request,
        'tags' => kuppi_tags_to_array((string) ($request['tags_csv'] ?? '')),
        'can_edit_request' => kuppi_can_edit_request($request),
        'can_delete_request' => kuppi_can_delete_request($request),
        'can_vote_request' => kuppi_user_can_vote_request($request),
        'can_apply_as_conductor' => kuppi_user_can_apply_as_conductor($request),
        'can_vote_conductor' => kuppi_user_can_vote_conductor($request),
        'conductor_applications' => $conductorApplications,
        'conductor_count' => count($conductorApplications),
        'viewer_conductor_application' => $viewerApplication,
        'top_vote_application_id' => $topVoteApplicationId,
        'availability_options' => kuppi_conductor_availability_options(),
        'comments' => $commentsTree,
        'comment_count' => $commentCount,
        'comment_max_level' => comments_max_depth_for_target(kuppi_comment_target_type()) + 1,
        'back_list_url' => kuppi_index_url_for_request($request),
    ], 'dashboard');
}


function kuppi_edit_form(string $id): void
{
    $requestId = (int) $id;
    $request = kuppi_resolve_readable_request($requestId);
    if (!$request) {
        abort(404, 'Kuppi request not found.');
    }

    if (!kuppi_can_edit_request($request)) {
        abort(403, 'Only the request owner can edit open requests.');
    }

    view('kuppi::edit', [
        'request' => $request,
        'subject_options' => kuppi_subject_options_for_batch((int) ($request['batch_id'] ?? 0)),
        'back_list_url' => kuppi_index_url_for_request($request),
    ], 'dashboard');
}

function kuppi_update_action(string $id): void
{
    csrf_check();

    $requestId = (int) $id;
    $request = kuppi_resolve_readable_request($requestId);
    if (!$request) {
        abort(404, 'Kuppi request not found.');
    }

    if (!kuppi_can_edit_request($request)) {
        abort(403, 'Only the request owner can edit open requests.');
    }

    $batchId = (int) ($request['batch_id'] ?? 0);
    $validated = kuppi_validate_request_input($batchId);
    if (!empty($validated['errors'])) {
        flash('error', implode(' ', $validated['errors']));
        flash_old_input();
        redirect('/dashboard/kuppi/' . $requestId . '/edit');
    }

    try {
        kuppi_update_request_by_owner($requestId, (int) auth_id(), [
            'subject_id' => (int) $validated['data']['subject_id'],
            'title' => $validated['data']['title'],
            'description' => $validated['data']['description'],
            'tags_csv' => $validated['data']['tags_csv'],
        ]);
    } catch (Throwable) {
        flash('error', 'Unable to update request right now. Please try again.');
        flash_old_input();
        redirect('/dashboard/kuppi/' . $requestId . '/edit');
    }

    clear_old_input();
    flash('success', 'Kuppi request updated.');
    redirect('/dashboard/kuppi/' . $requestId);
}

function kuppi_delete_action(string $id): void
{
    csrf_check();

    $requestId = (int) $id;
    $request = kuppi_resolve_readable_request($requestId);
    if (!$request) {
        abort(404, 'Kuppi request not found.');
    }

    if (!kuppi_can_delete_request($request)) {
        abort(403, 'You do not have permission to delete this request.');
    }

    $redirectTo = kuppi_resolve_valid_return_to((string) request_input('return_to', ''), $request);

    if (!kuppi_delete_request_by_id($requestId)) {
        flash('error', 'Unable to delete this request.');
        redirect($redirectTo);
    }

    flash('success', 'Kuppi request deleted.');
    redirect($redirectTo);
}

function kuppi_vote_action(string $id): void
{
    csrf_check();

    $requestId = (int) $id;
    $request = kuppi_resolve_readable_request($requestId);
    if (!$request) {
        abort(404, 'Kuppi request not found.');
    }

    $returnTo = kuppi_resolve_valid_return_to((string) request_input('return_to', ''), $request);
    if (!kuppi_user_can_vote_request($request)) {
        abort(403, 'You do not have permission to vote on this request.');
    }

    if ((int) ($request['requested_by_user_id'] ?? 0) === (int) auth_id()) {
        flash('error', 'You cannot vote on your own request.');
        redirect($returnTo);
    }

    $direction = trim((string) request_input('vote', ''));
    if (!in_array($direction, ['up', 'down'], true)) {
        flash('error', 'Invalid vote action.');
        redirect($returnTo);
    }

    try {
        $appliedVote = kuppi_apply_vote($requestId, (int) auth_id(), $direction);
    } catch (Throwable) {
        flash('error', 'Unable to save your vote right now.');
        redirect($returnTo);
    }

    if ($appliedVote === null) {
        flash('success', 'Vote removed.');
    } elseif ($appliedVote === 'up') {
        flash('success', 'Upvoted.');
    } else {
        flash('success', 'Downvoted.');
    }

    redirect($returnTo);
}

function kuppi_vote_delete_action(string $id): void
{
    csrf_check();

    $requestId = (int) $id;
    $request = kuppi_resolve_readable_request($requestId);
    if (!$request) {
        abort(404, 'Kuppi request not found.');
    }

    $returnTo = kuppi_resolve_valid_return_to((string) request_input('return_to', ''), $request);
    if (!kuppi_user_can_vote_request($request)) {
        abort(403, 'You do not have permission to vote on this request.');
    }

    if ((int) ($request['requested_by_user_id'] ?? 0) === (int) auth_id()) {
        flash('error', 'You cannot vote on your own request.');
        redirect($returnTo);
    }

    try {
        $removed = kuppi_remove_vote($requestId, (int) auth_id());
    } catch (Throwable) {
        flash('error', 'Unable to remove your vote right now.');
        redirect($returnTo);
    }

    flash('success', $removed ? 'Vote removed.' : 'No active vote found.');
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

function kuppi_my_index(): void
{
    if (!kuppi_user_can_create()) {
        abort(403, 'Only students and coordinators can access this page.');
    }

    view('kuppi::my_index', [
        'requests' => kuppi_my_requests((int) auth_id()),
    ], 'dashboard');
}

