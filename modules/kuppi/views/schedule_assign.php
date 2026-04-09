<?php
$draft = (array) ($draft ?? []);
$candidates = (array) ($candidates ?? []);
$selectedHostIds = array_values(array_filter(array_map('intval', (array) ($selected_host_ids ?? [])), static fn(int $id): bool => $id > 0));
$selectedMap = array_fill_keys($selectedHostIds, true);
$availabilityOptions = (array) ($availability_options ?? []);
$mode = (string) ($draft['mode'] ?? 'request');

$maxVotes = 0;
foreach ($candidates as $candidate) {
    $votes = (int) ($candidate['vote_count'] ?? 0);
    if ($votes > $maxVotes) {
        $maxVotes = $votes;
    }
}

$locationType = (string) ($draft['location_type'] ?? 'physical');
$locationLabel = $locationType === 'online'
    ? (string) ($draft['meeting_link'] ?? 'Online session')
    : (string) ($draft['location_text'] ?? 'Physical location');
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
    <div class="kuppi-wizard-step is-complete">
        <span class="kuppi-wizard-step-icon"><?= ui_lucide_icon('file-text') ?></span>
        <strong>Select Request</strong>
    </div>
    <div class="kuppi-wizard-step is-complete">
        <span class="kuppi-wizard-step-icon"><?= ui_lucide_icon('calendar') ?></span>
        <strong>Set Schedule</strong>
    </div>
    <div class="kuppi-wizard-step is-active">
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
        <h2>Assign Session Conductor</h2>
        <p class="kuppi-wizard-muted">Select one or more qualified conductors to lead this kuppi session.</p>

        <article class="kuppi-wizard-context">
            <p class="kuppi-wizard-request-subject"><?= ui_lucide_icon('book-open') ?> <?= e((string) ($draft['title'] ?? 'Session')) ?></p>
            <div class="kuppi-wizard-request-meta">
                <span><?= ui_lucide_icon('calendar') ?> <?= e((string) ($draft['session_date'] ?? '')) ?> at <?= e(substr((string) ($draft['start_time'] ?? ''), 0, 5)) ?> - <?= e(substr((string) ($draft['end_time'] ?? ''), 0, 5)) ?></span>
                <span><?= ui_lucide_icon($locationType === 'online' ? 'video' : 'map-pin') ?> <?= e($locationLabel) ?></span>
            </div>
        </article>

        <?php if (empty($candidates)): ?>
            <article class="community-post-card community-empty-state">
                <h3>No conductor candidates available</h3>
                <p class="text-muted">
                    <?php if ($mode === 'request'): ?>
                        No students have applied as conductors for this request yet.
                    <?php else: ?>
                        No eligible users were found for this batch.
                    <?php endif; ?>
                </p>
            </article>

            <div class="kuppi-wizard-actions">
                <a href="/dashboard/kuppi/schedule/set" class="btn btn-outline"><?= ui_lucide_icon('arrow-left') ?> Back</a>
                <a href="/dashboard/kuppi" class="btn btn-outline">Cancel</a>
            </div>
        <?php else: ?>
            <form method="POST" action="/dashboard/kuppi/schedule/assign" id="kuppi-host-assign-form">
                <?= csrf_field() ?>

                <div class="form-group kuppi-wizard-filter-grow">
                    <label for="kuppi-host-search">Search Conductors</label>
                    <input type="search" id="kuppi-host-search" placeholder="Search by name, role, or availability...">
                </div>

                <div class="kuppi-wizard-host-list" id="kuppi-host-list">
                    <?php foreach ($candidates as $candidate): ?>
                        <?php
                        $hostId = (int) ($candidate['host_user_id'] ?? 0);
                        if ($hostId <= 0) {
                            continue;
                        }

                        $hostName = trim((string) ($candidate['host_name'] ?? 'Unknown User'));
                        if ($hostName === '') {
                            $hostName = 'Unknown User';
                        }

                        $availability = (array) ($candidate['availability'] ?? []);
                        $voteCount = (int) ($candidate['vote_count'] ?? 0);
                        $isTopVote = $mode === 'request' && $voteCount > 0 && $voteCount === $maxVotes;
                        $isSelected = !empty($selectedMap[$hostId]);
                        $toneClass = ui_avatar_tone_class((string) ($hostId . '-' . $hostName));

                        $searchPayload = strtolower($hostName . ' ' . (string) ($candidate['host_role'] ?? '') . ' ' . implode(' ', $availability));
                        ?>
                        <label class="kuppi-wizard-host-option <?= $isSelected ? 'is-selected' : '' ?>" data-search="<?= e($searchPayload) ?>">
                            <div class="kuppi-wizard-host-radio-wrap">
                                <input type="checkbox" class="kuppi-wizard-host-checkbox" name="host_user_ids[]" value="<?= $hostId ?>" <?= $isSelected ? 'checked' : '' ?>>
                            </div>

                            <div class="kuppi-wizard-host-content">
                                <div class="kuppi-wizard-host-head">
                                    <div class="kuppi-wizard-host-identity">
                                        <span class="kuppi-conductor-avatar <?= e($toneClass) ?>"><?= e(ui_initials($hostName)) ?></span>
                                        <div>
                                            <h3><?= e($hostName) ?></h3>
                                            <p class="kuppi-wizard-muted-inline">
                                                <?= e(ucfirst((string) ($candidate['host_role'] ?? 'student'))) ?>
                                                <?php if ((int) ($candidate['host_academic_year'] ?? 0) > 0): ?>
                                                    • Year <?= (int) ($candidate['host_academic_year'] ?? 0) ?>
                                                <?php endif; ?>
                                            </p>
                                        </div>
                                    </div>

                                    <div class="kuppi-wizard-host-badges">
                                        <?php if ($mode === 'request'): ?>
                                            <span class="badge badge-info"><?= $voteCount ?> votes</span>
                                        <?php endif; ?>
                                        <?php if ($isTopVote): ?>
                                            <span class="badge badge-success">Top Rated</span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <?php if (!empty($availability)): ?>
                                    <div class="kuppi-tags">
                                        <?php foreach ($availability as $slot): ?>
                                            <span class="badge"><?= e((string) ($availabilityOptions[$slot] ?? $slot)) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </label>
                    <?php endforeach; ?>
                </div>

                <div class="form-group">
                    <label for="conductor_notes">Notes for Conductor (Optional)</label>
                    <textarea id="conductor_notes" name="conductor_notes" rows="3" maxlength="500" placeholder="Any special instructions or expectations for the conductor..."></textarea>
                    <small class="text-muted">This note is for scheduling context only.</small>
                </div>

                <div class="kuppi-wizard-info-box">
                    <strong>Conductor Notification</strong>
                    <p>The selected conductors will receive an email notification with session details after confirmation.</p>
                </div>

                <div class="kuppi-wizard-actions">
                    <a href="/dashboard/kuppi/schedule/set" class="btn btn-outline"><?= ui_lucide_icon('arrow-left') ?> Back</a>
                    <button type="submit" id="kuppi-host-continue" class="btn btn-primary kuppi-wizard-cta">Continue <?= ui_lucide_icon('arrow-right') ?></button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<script>
(function () {
    const searchInput = document.getElementById('kuppi-host-search');
    const hostList = document.getElementById('kuppi-host-list');
    if (searchInput && hostList) {
        searchInput.addEventListener('input', function () {
            const needle = searchInput.value.trim().toLowerCase();
            hostList.querySelectorAll('.kuppi-wizard-host-option').forEach((item) => {
                const hay = (item.getAttribute('data-search') || '').toLowerCase();
                item.style.display = hay.includes(needle) ? '' : 'none';
            });
        });
    }

    const form = document.getElementById('kuppi-host-assign-form');
    if (!form) {
        return;
    }

    const continueBtn = document.getElementById('kuppi-host-continue');
    const checkboxes = Array.from(form.querySelectorAll('.kuppi-wizard-host-checkbox'));

    function syncSelection() {
        let count = 0;
        checkboxes.forEach((input) => {
            const card = input.closest('.kuppi-wizard-host-option');
            if (!card) {
                return;
            }
            if (input.checked) {
                card.classList.add('is-selected');
                count += 1;
            } else {
                card.classList.remove('is-selected');
            }
        });

        if (continueBtn) {
            continueBtn.disabled = count === 0;
        }
    }

    checkboxes.forEach((input) => input.addEventListener('change', syncSelection));
    syncSelection();
})();
</script>
