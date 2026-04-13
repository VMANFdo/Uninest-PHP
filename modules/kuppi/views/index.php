<?php
$currentUri = (string) ($_SERVER['REQUEST_URI'] ?? '/dashboard/kuppi');
$selectedBatchId = (int) ($selected_batch_id ?? 0);
$selectedSubjectId = (int) ($selected_subject_id ?? 0);
$selectedSort = (string) ($selected_sort ?? 'most_votes');
$selectedSearchQuery = (string) ($selected_search_query ?? '');
$selectedPage = max(1, (int) ($selected_page ?? 1));
$requestCount = (int) ($request_count ?? 0);
$subjectOptions = (array) ($subject_options ?? []);
$requests = (array) ($requests ?? []);
$hasMoreRequests = !empty($has_more_requests);
$activeBatch = (array) ($active_batch ?? []);

$buildListUrl = static function (array $params = []) use ($is_admin, $selectedBatchId, $selectedSubjectId, $selectedSort, $selectedSearchQuery): string {
    $query = [];

    if (!empty($is_admin) && $selectedBatchId > 0) {
        $query['batch_id'] = $selectedBatchId;
    }
    if ($selectedSubjectId > 0) {
        $query['subject_id'] = $selectedSubjectId;
    }
    if ($selectedSort !== 'most_votes') {
        $query['sort'] = $selectedSort;
    }
    if ($selectedSearchQuery !== '') {
        $query['q'] = $selectedSearchQuery;
    }

    foreach ($params as $key => $value) {
        if ($value === null || $value === '') {
            unset($query[$key]);
            continue;
        }
        $query[$key] = $value;
    }

    return '/dashboard/kuppi' . (!empty($query) ? '?' . http_build_query($query) : '');
};
?>

<?php if (!empty($is_admin) && $selectedBatchId <= 0): ?>
    <section class="community-admin-gate">
        <h3>Select Batch to Open Requested Kuppi</h3>
        <p class="text-muted">Choose an approved batch first to view and moderate requests.</p>
        <form method="GET" action="/dashboard/kuppi" class="community-topbar-form">
            <div class="form-group">
                <label for="batch_id">Batch</label>
                <select id="batch_id" name="batch_id" required>
                    <option value="">Select a batch</option>
                    <?php foreach ((array) ($batch_options ?? []) as $batch): ?>
                        <?php $batchId = (int) ($batch['id'] ?? 0); ?>
                        <option value="<?= $batchId ?>">
                            <?= e((string) ($batch['batch_code'] ?? 'BATCH')) ?> — <?= e((string) ($batch['name'] ?? '')) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Open Requests</button>
        </form>
    </section>
<?php else: ?>
    <div class="page-header">
        <div class="page-header-content">
            <p class="page-breadcrumb">Dashboard / Requested Kuppi Sessions</p>
            <h1>Requested Kuppi Sessions</h1>
            <p class="page-subtitle">
                Vote on session demand and prioritize peer-led learning for your batch.
                <?php if (!empty($activeBatch['batch_code'])): ?>
                    Active batch: <strong><?= e((string) $activeBatch['batch_code']) ?></strong>.
                <?php endif; ?>
            </p>
        </div>
        <div class="page-header-actions">
            <?php if (!empty($can_create)): ?>
                <a href="/dashboard/kuppi/create" class="btn btn-primary"><?= ui_lucide_icon('plus') ?> Request Session</a>
                <a href="/my-kuppi-requests" class="btn btn-outline">My Requests</a>
            <?php endif; ?>
            <?php if (kuppi_user_is_scheduler()): ?>
                <a href="/dashboard/kuppi/schedule" class="btn btn-primary"><?= ui_lucide_icon('calendar-plus') ?> Schedule Session</a>
                <a href="/dashboard/kuppi/scheduled" class="btn btn-outline"><?= ui_lucide_icon('calendar') ?> Scheduled Sessions</a>
            <?php endif; ?>
            <a href="/dashboard/kuppi/timetable<?= !empty($is_admin) && $selectedBatchId > 0 ? '?batch_id=' . $selectedBatchId : '' ?>" class="btn btn-outline"><?= ui_lucide_icon('calendar-clock') ?> University Timetable</a>
            <a href="/dashboard" class="btn btn-outline">Dashboard</a>
        </div>
    </div>

    <section class="kuppi-filter-card">
        <form method="GET" action="/dashboard/kuppi" class="kuppi-filter-grid">
            <?php if (!empty($is_admin)): ?>
                <div class="form-group">
                    <label for="batch_id">Batch</label>
                    <select id="batch_id" name="batch_id" required>
                        <?php foreach ((array) ($batch_options ?? []) as $batch): ?>
                            <?php $batchId = (int) ($batch['id'] ?? 0); ?>
                            <option value="<?= $batchId ?>" <?= $selectedBatchId === $batchId ? 'selected' : '' ?>>
                                <?= e((string) ($batch['batch_code'] ?? 'BATCH')) ?> — <?= e((string) ($batch['name'] ?? '')) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

            <div class="form-group kuppi-search-group">
                <label for="q">Search</label>
                <input type="search" id="q" name="q" value="<?= e($selectedSearchQuery) ?>" placeholder="Search title, subject, tags, or description">
            </div>

            <div class="form-group">
                <label for="subject_id">Subject</label>
                <select id="subject_id" name="subject_id">
                    <option value="">All Subjects</option>
                    <?php foreach ($subjectOptions as $subject): ?>
                        <?php $subjectId = (int) ($subject['id'] ?? 0); ?>
                        <option value="<?= $subjectId ?>" <?= $selectedSubjectId === $subjectId ? 'selected' : '' ?>>
                            <?= e((string) ($subject['code'] ?? 'SUB')) ?> — <?= e((string) ($subject['name'] ?? '')) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="sort">Sort by</label>
                <select id="sort" name="sort">
                    <option value="most_votes" <?= $selectedSort === 'most_votes' ? 'selected' : '' ?>>Most Votes</option>
                    <option value="recent" <?= $selectedSort === 'recent' ? 'selected' : '' ?>>Most Recent</option>
                </select>
            </div>

            <div class="kuppi-filter-actions">
                <button type="submit" class="btn btn-primary">Apply</button>
                <a href="<?= e($buildListUrl(['q' => null, 'subject_id' => null, 'sort' => null, 'page' => null])) ?>" class="btn btn-outline">Reset</a>
            </div>
        </form>
    </section>

    <p class="kuppi-result-count"><?= $requestCount ?> <?= $requestCount === 1 ? 'request' : 'requests' ?> found</p>

    <?php if (empty($requests)): ?>
        <article class="community-post-card community-empty-state">
            <h3>No requested sessions found</h3>
            <p class="text-muted">Try changing filters or create the first Kuppi request for your batch.</p>
        </article>
    <?php else: ?>
        <section class="kuppi-request-list">
            <?php foreach ($requests as $request): ?>
                <?php
                $requestId = (int) ($request['id'] ?? 0);
                $requesterName = trim((string) ($request['requester_name'] ?? 'Unknown User'));
                $isOwn = (int) ($request['requested_by_user_id'] ?? 0) === (int) auth_id();
                $viewerVote = (string) ($request['viewer_vote'] ?? '');
                $voteScore = (int) ($request['vote_score'] ?? 0);
                $upvotes = (int) ($request['upvote_count'] ?? 0);
                $commentCount = (int) ($request['comment_count'] ?? 0);
                $interestedCount = max(0, $upvotes);
                $canEdit = kuppi_can_edit_request($request);
                $canDelete = kuppi_can_delete_request($request);
                $canVote = kuppi_user_can_vote_request($request) && !$isOwn;
                $canApplyAsConductor = kuppi_user_can_apply_as_conductor($request);
                $canSchedule = kuppi_user_can_schedule_request($request);
                $tags = kuppi_tags_to_array((string) ($request['tags_csv'] ?? ''));
                $showUrl = '/dashboard/kuppi/' . $requestId;
                if (!empty($is_admin) && $selectedBatchId > 0) {
                    $showUrl .= '?batch_id=' . $selectedBatchId;
                }
                ?>
                <article class="kuppi-request-card kuppi-request-card--list">
                    <aside class="kuppi-vote-rail">
                        <form method="POST" action="/dashboard/kuppi/<?= $requestId ?>/vote">
                            <?= csrf_field() ?>
                            <input type="hidden" name="vote" value="up">
                            <input type="hidden" name="return_to" value="<?= e($currentUri) ?>">
                            <?php if (!empty($is_admin)): ?>
                                <input type="hidden" name="batch_id" value="<?= (int) ($selectedBatchId > 0 ? $selectedBatchId : ($request['batch_id'] ?? 0)) ?>">
                            <?php endif; ?>
                            <button type="submit" class="kuppi-vote-btn <?= $viewerVote === 'up' ? 'is-active' : '' ?>" <?= $canVote ? '' : 'disabled' ?> aria-label="Upvote request"><?= ui_lucide_icon('arrow-up') ?></button>
                        </form>

                        <strong class="kuppi-vote-score"><?= $voteScore ?></strong>

                        <form method="POST" action="/dashboard/kuppi/<?= $requestId ?>/vote">
                            <?= csrf_field() ?>
                            <input type="hidden" name="vote" value="down">
                            <input type="hidden" name="return_to" value="<?= e($currentUri) ?>">
                            <?php if (!empty($is_admin)): ?>
                                <input type="hidden" name="batch_id" value="<?= (int) ($selectedBatchId > 0 ? $selectedBatchId : ($request['batch_id'] ?? 0)) ?>">
                            <?php endif; ?>
                            <button type="submit" class="kuppi-vote-btn <?= $viewerVote === 'down' ? 'is-active is-down' : 'is-down' ?>" <?= $canVote ? '' : 'disabled' ?> aria-label="Downvote request"><?= ui_lucide_icon('arrow-down') ?></button>
                        </form>
                    </aside>

                    <div class="kuppi-request-main kuppi-request-main--list">
                        <header class="kuppi-request-header">
                            <div class="kuppi-request-badges">
                                <?php if (!empty($request['subject_code'])): ?>
                                    <span class="badge"><?= e((string) $request['subject_code']) ?></span>
                                <?php endif; ?>
                            </div>
                            <a href="<?= e($showUrl) ?>" class="kuppi-request-title kuppi-request-title--list"><?= e((string) ($request['title'] ?? 'Untitled')) ?></a>
                            <div class="kuppi-request-author-row">
                                <span class="kuppi-request-avatar"><?= e(ui_initials($requesterName)) ?></span>
                                <p class="kuppi-request-meta kuppi-request-meta--list">
                                    <span>Requested by <strong><?= e($requesterName) ?></strong></span>
                                    <span class="kuppi-meta-dot">•</span>
                                    <span><?= e(kuppi_relative_time_label((string) ($request['created_at'] ?? 'now'))) ?></span>
                                </p>
                            </div>
                        </header>

                        <p class="kuppi-request-description kuppi-request-description--list"><?= nl2br(e((string) ($request['description'] ?? ''))) ?></p>

                        <?php if (!empty($tags)): ?>
                            <div class="kuppi-tags">
                                <?php foreach ($tags as $tag): ?>
                                    <span class="badge"><?= e($tag) ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <footer class="kuppi-request-footer kuppi-request-footer--list">
                            <div class="kuppi-request-metrics">
                                <span class="kuppi-request-metric">
                                    <?= ui_lucide_icon('message-circle', 'kuppi-request-metric-icon') ?>
                                    <?= $commentCount ?> comments
                                </span>
                                <span class="kuppi-request-metric">
                                    <?= ui_lucide_icon('users', 'kuppi-request-metric-icon') ?>
                                    <?= $interestedCount ?> interested
                                </span>
                                <span class="kuppi-request-metric">
                                    <?= ui_lucide_icon('user-check', 'kuppi-request-metric-icon') ?>
                                    <?= (int) ($request['conductor_count'] ?? 0) ?> conductors
                                </span>
                            </div>
                            <div class="kuppi-request-actions kuppi-request-actions--list">
                                <?php if ($canSchedule): ?>
                                    <a href="/dashboard/kuppi/schedule?request_id=<?= $requestId ?>" class="btn btn-outline kuppi-request-action-btn">
                                        <?= ui_lucide_icon('calendar-plus', 'kuppi-btn-icon') ?>
                                        Schedule
                                    </a>
                                <?php endif; ?>
                                <a href="<?= e($showUrl) ?>#kuppi-comments" class="btn btn-outline kuppi-request-action-btn">
                                    <?= ui_lucide_icon('message-circle', 'kuppi-btn-icon') ?>
                                    Comment
                                </a>
                                <?php if ($canApplyAsConductor): ?>
                                    <a href="/dashboard/kuppi/<?= $requestId ?>/conductors/apply" class="btn btn-primary kuppi-request-action-btn">
                                        <?= ui_lucide_icon('user-check', 'kuppi-btn-icon') ?>
                                        Be a Conductor
                                    </a>
                                <?php else: ?>
                                    <a href="<?= e($showUrl) ?>" class="btn btn-outline kuppi-request-action-btn">Open Session</a>
                                <?php endif; ?>
                            </div>
                        </footer>

                        <?php if ($canEdit || $canDelete): ?>
                            <div class="kuppi-request-manage-links">
                                <?php if ($canEdit): ?>
                                    <a href="/dashboard/kuppi/<?= $requestId ?>/edit">Edit</a>
                                <?php endif; ?>
                                <?php if ($canDelete): ?>
                                    <form method="POST" action="/dashboard/kuppi/<?= $requestId ?>/delete" onsubmit="return confirm('Delete this request?');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="return_to" value="<?= e($currentUri) ?>">
                                        <button type="submit">Delete</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </section>

        <?php if ($hasMoreRequests): ?>
            <div class="community-load-more">
                <a href="<?= e($buildListUrl(['page' => $selectedPage + 1])) ?>" class="btn btn-outline">Load more requests</a>
            </div>
        <?php endif; ?>
    <?php endif; ?>
<?php endif; ?>
