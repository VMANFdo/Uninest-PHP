<?php
$sessions = (array) ($sessions ?? []);
$searchQuery = (string) ($selected_search_query ?? '');
$selectedSubjectId = (int) ($selected_subject_id ?? 0);
$selectedStatus = (string) ($selected_status ?? '');
$subjectOptions = (array) ($subject_options ?? []);
$statusOptions = (array) ($status_options ?? []);
$isAdmin = !empty($is_admin);
$adminBatchId = (int) ($admin_batch_id ?? 0);
$batchOptions = (array) ($batch_options ?? []);

$statusClassMap = [
    'scheduled' => 'badge-info',
    'completed' => 'badge-success',
    'cancelled' => 'badge-danger',
];
?>

<div class="page-header">
    <div class="page-header-content">
        <p class="page-breadcrumb">Dashboard / Kuppi Sessions / Scheduled Sessions</p>
        <h1>Scheduled Kuppi Sessions</h1>
        <p class="page-subtitle">Browse upcoming, completed, and cancelled sessions.</p>
    </div>
    <div class="page-header-actions">
        <?php if (kuppi_user_is_scheduler()): ?>
            <a href="/dashboard/kuppi/schedule" class="btn btn-primary"><?= ui_lucide_icon('calendar-plus') ?> Schedule Session</a>
        <?php endif; ?>
        <a href="/dashboard/kuppi" class="btn btn-outline">Requested Sessions</a>
    </div>
</div>

<section class="kuppi-filter-card">
    <form method="GET" action="/dashboard/kuppi/scheduled" class="kuppi-filter-grid">
        <?php if ($isAdmin): ?>
            <div class="form-group">
                <label for="batch_id">Batch</label>
                <select id="batch_id" name="batch_id">
                    <option value="">All Batches</option>
                    <?php foreach ($batchOptions as $batch): ?>
                        <?php $batchId = (int) ($batch['id'] ?? 0); ?>
                        <option value="<?= $batchId ?>" <?= $adminBatchId === $batchId ? 'selected' : '' ?>>
                            <?= e((string) ($batch['batch_code'] ?? 'BATCH')) ?> — <?= e((string) ($batch['name'] ?? '')) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>

        <div class="form-group kuppi-search-group">
            <label for="q">Search</label>
            <input type="search" id="q" name="q" value="<?= e($searchQuery) ?>" placeholder="Search by title, subject, or batch">
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
            <label for="status">Status</label>
            <select id="status" name="status">
                <option value="">All Statuses</option>
                <?php foreach ($statusOptions as $status): ?>
                    <option value="<?= e((string) $status) ?>" <?= $selectedStatus === $status ? 'selected' : '' ?>>
                        <?= e(ucfirst((string) $status)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="kuppi-filter-actions">
            <button type="submit" class="btn btn-primary">Apply</button>
            <a href="/dashboard/kuppi/scheduled" class="btn btn-outline">Reset</a>
        </div>
    </form>
</section>

<p class="kuppi-result-count"><?= count($sessions) ?> <?= count($sessions) === 1 ? 'session' : 'sessions' ?> found</p>

<?php if (empty($sessions)): ?>
    <article class="community-post-card community-empty-state">
        <h3>No scheduled sessions found</h3>
        <p class="text-muted">Use the scheduler to create your first session.</p>
    </article>
<?php else: ?>
    <section class="kuppi-scheduled-list">
        <?php foreach ($sessions as $session): ?>
            <?php
            $sessionId = (int) ($session['id'] ?? 0);
            $status = (string) ($session['status'] ?? 'scheduled');
            $statusClass = $statusClassMap[$status] ?? 'badge-info';
            $canManage = kuppi_user_can_manage_scheduled_session($session);
            ?>
            <article class="kuppi-scheduled-card">
                <div class="kuppi-scheduled-head">
                    <div>
                        <p class="kuppi-request-meta">
                            <?php if (!empty($session['subject_code'])): ?>
                                <span class="badge"><?= e((string) $session['subject_code']) ?></span>
                            <?php endif; ?>
                            <?php if ($isAdmin && !empty($session['batch_code'])): ?>
                                <span class="badge"><?= e((string) $session['batch_code']) ?></span>
                            <?php endif; ?>
                            <span class="badge <?= e($statusClass) ?>"><?= e(ucfirst($status)) ?></span>
                        </p>
                        <h3><a href="/dashboard/kuppi/scheduled/<?= $sessionId ?>"><?= e((string) ($session['title'] ?? 'Scheduled Session')) ?></a></h3>
                        <p class="text-muted">
                            <?= e((string) ($session['session_date'] ?? '')) ?>
                            • <?= e(substr((string) ($session['start_time'] ?? ''), 0, 5)) ?> - <?= e(substr((string) ($session['end_time'] ?? ''), 0, 5)) ?>
                            • <?= (int) ($session['duration_minutes'] ?? 0) ?> mins
                        </p>
                    </div>
                    <div class="kuppi-scheduled-actions">
                        <a href="/dashboard/kuppi/scheduled/<?= $sessionId ?>" class="btn btn-outline">View</a>
                        <?php if ($canManage && $status !== 'cancelled'): ?>
                            <a href="/dashboard/kuppi/scheduled/<?= $sessionId ?>/edit" class="btn btn-outline">Edit</a>
                        <?php endif; ?>
                    </div>
                </div>

                <p class="kuppi-request-description"><?= nl2br(e((string) ($session['description'] ?? ''))) ?></p>

                <div class="kuppi-request-metrics">
                    <span class="kuppi-request-metric">
                        <?= ui_lucide_icon('users', 'kuppi-request-metric-icon') ?>
                        <?= (int) ($session['host_count'] ?? 0) ?> hosts
                    </span>
                    <span class="kuppi-request-metric">
                        <?= ui_lucide_icon('user-plus', 'kuppi-request-metric-icon') ?>
                        Max <?= (int) ($session['max_attendees'] ?? 0) ?> attendees
                    </span>
                    <span class="kuppi-request-metric">
                        <?= ui_lucide_icon(($session['location_type'] ?? 'physical') === 'online' ? 'video' : 'map-pin', 'kuppi-request-metric-icon') ?>
                        <?= ($session['location_type'] ?? 'physical') === 'online'
                            ? e((string) ($session['meeting_link'] ?? 'Online'))
                            : e((string) ($session['location_text'] ?? 'Physical')) ?>
                    </span>
                    <?php if ((int) ($session['request_id'] ?? 0) > 0): ?>
                        <span class="kuppi-request-metric">
                            <?= ui_lucide_icon('link-2', 'kuppi-request-metric-icon') ?>
                            Linked request #<?= (int) ($session['request_id'] ?? 0) ?>
                        </span>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
    </section>
<?php endif; ?>
