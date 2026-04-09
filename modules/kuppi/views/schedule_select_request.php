<?php
$requests = (array) ($requests ?? []);
$selectedSort = (string) ($selected_sort ?? 'most_votes');
$selectedSearchQuery = (string) ($selected_search_query ?? '');
$isAdmin = !empty($is_admin);
$adminBatchId = (int) ($admin_batch_id ?? 0);
$manualStartUrl = '/dashboard/kuppi/schedule/manual';
if ($isAdmin && $adminBatchId > 0) {
    $manualStartUrl .= '?batch_id=' . $adminBatchId;
}

$initialRequestId = 0;
foreach ($requests as $request) {
    if ((int) ($request['active_session_count'] ?? 0) === 0) {
        $initialRequestId = (int) ($request['id'] ?? 0);
        break;
    }
}
?>

<div class="page-header kuppi-wizard-header">
    <div class="page-header-content">
        <h1>Schedule Kuppi Session</h1>
        <p class="page-subtitle">Review requests and schedule kuppi sessions with conductors.</p>
    </div>
    <div class="page-header-actions">
        <a href="/dashboard/kuppi" class="btn btn-outline"><?= ui_lucide_icon('arrow-left') ?> Back to Sessions</a>
    </div>
</div>

<section class="kuppi-wizard-stepper" aria-label="Scheduling steps">
    <div class="kuppi-wizard-step is-active">
        <span class="kuppi-wizard-step-icon"><?= ui_lucide_icon('file-text') ?></span>
        <strong>Select Request</strong>
    </div>
    <div class="kuppi-wizard-step">
        <span class="kuppi-wizard-step-icon"><?= ui_lucide_icon('calendar') ?></span>
        <strong>Set Schedule</strong>
    </div>
    <div class="kuppi-wizard-step">
        <span class="kuppi-wizard-step-icon"><?= ui_lucide_icon('user-check') ?></span>
        <strong>Assign Conductor</strong>
    </div>
    <div class="kuppi-wizard-step">
        <span class="kuppi-wizard-step-icon"><?= ui_lucide_icon('check-circle') ?></span>
        <strong>Review & Confirm</strong>
    </div>
</section>

<div class="card kuppi-wizard-card">
    <div class="card-body">
        <h2>Select Kuppi Request</h2>
        <p class="kuppi-wizard-muted">Choose a pending kuppi session request to schedule.</p>

        <form method="GET" action="/dashboard/kuppi/schedule" class="kuppi-wizard-filter-row">
            <?php if ($isAdmin): ?>
                <div class="form-group">
                    <label for="batch_id">Batch</label>
                    <select id="batch_id" name="batch_id">
                        <option value="">All Batches</option>
                        <?php foreach ((array) ($batch_options ?? []) as $batch): ?>
                            <?php $batchId = (int) ($batch['id'] ?? 0); ?>
                            <option value="<?= $batchId ?>" <?= $adminBatchId === $batchId ? 'selected' : '' ?>>
                                <?= e((string) ($batch['batch_code'] ?? 'BATCH')) ?> - <?= e((string) ($batch['name'] ?? '')) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

            <div class="form-group kuppi-wizard-filter-grow">
                <label for="q">Search Requests</label>
                <input type="search" id="q" name="q" value="<?= e($selectedSearchQuery) ?>" placeholder="Search by topic, subject, or requester...">
            </div>

            <div class="form-group kuppi-wizard-filter-sort">
                <label for="sort">Sort</label>
                <select id="sort" name="sort">
                    <option value="most_votes" <?= $selectedSort === 'most_votes' ? 'selected' : '' ?>>Most Votes</option>
                    <option value="recent" <?= $selectedSort === 'recent' ? 'selected' : '' ?>>Most Recent</option>
                </select>
            </div>

            <div class="kuppi-wizard-filter-submit">
                <button type="submit" class="btn btn-outline">Apply</button>
            </div>
        </form>

        <?php if (empty($requests)): ?>
            <article class="community-post-card community-empty-state">
                <h3>No open requests found</h3>
                <p class="text-muted">Try another filter or create a manual session instead.</p>
            </article>

            <div class="kuppi-wizard-actions">
                <a href="<?= e($manualStartUrl) ?>" class="btn btn-outline">Start Manual Session</a>
                <a href="/dashboard/kuppi" class="btn btn-outline">Cancel</a>
            </div>
        <?php else: ?>
            <form method="POST" action="/dashboard/kuppi/schedule/select-request" id="kuppi-request-select-form">
                <?= csrf_field() ?>

                <div class="kuppi-wizard-request-list">
                    <?php foreach ($requests as $request): ?>
                        <?php
                        $requestId = (int) ($request['id'] ?? 0);
                        $isBlocked = (int) ($request['active_session_count'] ?? 0) > 0;
                        $isChecked = !$isBlocked && $initialRequestId === $requestId;
                        $requesterName = trim((string) ($request['requester_name'] ?? 'Unknown User'));
                        if ($requesterName === '') {
                            $requesterName = 'Unknown User';
                        }
                        ?>

                        <?php if ($isBlocked): ?>
                            <article class="kuppi-wizard-request-option is-disabled">
                                <div class="kuppi-wizard-request-radio-wrap">
                                    <span class="kuppi-wizard-request-dot is-disabled"></span>
                                </div>
                                <div class="kuppi-wizard-request-content">
                                    <p class="kuppi-wizard-request-subject"><?= ui_lucide_icon('book-open') ?> <?= e((string) ($request['subject_code'] ?? 'SUB')) ?> - <?= e((string) ($request['subject_name'] ?? 'Subject')) ?></p>
                                    <h3><?= e((string) ($request['title'] ?? 'Untitled request')) ?></h3>
                                    <div class="kuppi-wizard-request-meta">
                                        <span><?= ui_lucide_icon('user') ?> Requested by <strong><?= e($requesterName) ?></strong></span>
                                        <span><?= ui_lucide_icon('clock-3') ?> <?= e(kuppi_relative_time_label((string) ($request['created_at'] ?? 'now'))) ?></span>
                                        <span><?= ui_lucide_icon('arrow-up') ?> <?= (int) ($request['vote_score'] ?? 0) ?> votes</span>
                                    </div>
                                </div>
                                <span class="badge badge-warning">Already Scheduled</span>
                            </article>
                        <?php else: ?>
                            <label class="kuppi-wizard-request-option <?= $isChecked ? 'is-selected' : '' ?>">
                                <div class="kuppi-wizard-request-radio-wrap">
                                    <input
                                        type="radio"
                                        class="kuppi-wizard-request-radio"
                                        name="request_id"
                                        value="<?= $requestId ?>"
                                        <?= $isChecked ? 'checked' : '' ?>
                                        required>
                                </div>
                                <div class="kuppi-wizard-request-content">
                                    <p class="kuppi-wizard-request-subject"><?= ui_lucide_icon('book-open') ?> <?= e((string) ($request['subject_code'] ?? 'SUB')) ?> - <?= e((string) ($request['subject_name'] ?? 'Subject')) ?></p>
                                    <h3><?= e((string) ($request['title'] ?? 'Untitled request')) ?></h3>
                                    <div class="kuppi-wizard-request-meta">
                                        <span><?= ui_lucide_icon('user') ?> Requested by <strong><?= e($requesterName) ?></strong></span>
                                        <span><?= ui_lucide_icon('clock-3') ?> <?= e(kuppi_relative_time_label((string) ($request['created_at'] ?? 'now'))) ?></span>
                                        <span><?= ui_lucide_icon('arrow-up') ?> <?= (int) ($request['vote_score'] ?? 0) ?> votes</span>
                                    </div>
                                </div>
                            </label>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>

                <div class="kuppi-wizard-actions">
                    <a href="<?= e($manualStartUrl) ?>" class="btn btn-outline">Start Manual Session</a>
                    <div class="kuppi-wizard-actions-right">
                        <a href="/dashboard/kuppi" class="btn btn-outline">Cancel</a>
                        <button type="submit" id="kuppi-select-continue" class="btn btn-primary kuppi-wizard-cta" <?= $initialRequestId > 0 ? '' : 'disabled' ?>>
                            Continue <?= ui_lucide_icon('arrow-right') ?>
                        </button>
                    </div>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<script>
(function () {
    const form = document.getElementById('kuppi-request-select-form');
    if (!form) {
        return;
    }

    const continueBtn = document.getElementById('kuppi-select-continue');
    const radios = Array.from(form.querySelectorAll('.kuppi-wizard-request-radio'));

    function syncSelection() {
        let hasChecked = false;
        radios.forEach((radio) => {
            const card = radio.closest('.kuppi-wizard-request-option');
            if (!card) {
                return;
            }
            if (radio.checked) {
                card.classList.add('is-selected');
                hasChecked = true;
            } else {
                card.classList.remove('is-selected');
            }
        });
        if (continueBtn) {
            continueBtn.disabled = !hasChecked;
        }
    }

    radios.forEach((radio) => radio.addEventListener('change', syncSelection));
    syncSelection();
})();
</script>
