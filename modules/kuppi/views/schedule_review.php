<?php
$draft = (array) ($draft ?? []);
$selectedHosts = (array) ($selected_hosts ?? []);
$linkedRequest = (array) ($linked_request ?? []);
$isRequestMode = ((string) ($draft['mode'] ?? 'request')) === 'request';

$locationType = (string) ($draft['location_type'] ?? 'physical');
$locationLabel = $locationType === 'online'
    ? (string) ($draft['meeting_link'] ?? '')
    : (string) ($draft['location_text'] ?? '');

$subjectLabel = $isRequestMode
    ? trim((string) (($linkedRequest['subject_code'] ?? '') . ' - ' . ($linkedRequest['subject_name'] ?? '')))
    : 'Subject #' . (int) ($draft['subject_id'] ?? 0);
if ($subjectLabel === '' || $subjectLabel === ' - ') {
    $subjectLabel = 'Subject #' . (int) ($draft['subject_id'] ?? 0);
}

$requesterLabel = $isRequestMode
    ? trim((string) ($linkedRequest['requester_name'] ?? 'Unknown'))
    : 'Manual session';
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
    <div class="kuppi-wizard-step is-complete">
        <span class="kuppi-wizard-step-icon"><?= ui_lucide_icon('user-check') ?></span>
        <strong>Assign Conductor</strong>
    </div>
    <div class="kuppi-wizard-step is-active">
        <span class="kuppi-wizard-step-icon"><?= ui_lucide_icon('check-circle') ?></span>
        <strong>Review & Confirm</strong>
    </div>
</section>

<div class="card kuppi-wizard-card">
    <div class="card-body kuppi-wizard-review-card">
        <h2>Review & Confirm</h2>
        <p class="kuppi-wizard-muted">Please review all details before scheduling the session.</p>

        <section class="kuppi-wizard-review-section">
            <h3>Session Information</h3>
            <div class="kuppi-wizard-review-block">
                <div class="kuppi-wizard-review-row">
                    <span>Subject</span>
                    <strong><?= e($subjectLabel) ?></strong>
                </div>
                <div class="kuppi-wizard-review-row">
                    <span>Topic</span>
                    <strong><?= e((string) ($draft['title'] ?? '')) ?></strong>
                </div>
                <div class="kuppi-wizard-review-row">
                    <span>Requested By</span>
                    <strong><?= e($requesterLabel) ?></strong>
                </div>
            </div>
        </section>

        <section class="kuppi-wizard-review-section">
            <h3>Schedule Details</h3>
            <div class="kuppi-wizard-review-grid-two">
                <div class="kuppi-wizard-review-row">
                    <span>Date</span>
                    <strong><?= e((string) ($draft['session_date'] ?? '')) ?></strong>
                </div>
                <div class="kuppi-wizard-review-row">
                    <span>Time</span>
                    <strong><?= e(substr((string) ($draft['start_time'] ?? ''), 0, 5)) ?> - <?= e(substr((string) ($draft['end_time'] ?? ''), 0, 5)) ?></strong>
                </div>
                <div class="kuppi-wizard-review-row">
                    <span>Duration</span>
                    <strong><?= (int) ($draft['duration_minutes'] ?? 0) ?> minutes</strong>
                </div>
                <div class="kuppi-wizard-review-row">
                    <span>Max Attendees</span>
                    <strong><?= (int) ($draft['max_attendees'] ?? 0) ?> students</strong>
                </div>
            </div>
            <div class="kuppi-wizard-review-row">
                <span>Location</span>
                <strong>
                    <?= e(ucfirst($locationType)) ?>
                    <?php if ($locationLabel !== ''): ?>
                        - <?= e($locationLabel) ?>
                    <?php endif; ?>
                </strong>
            </div>
            <?php if (trim((string) ($draft['notes'] ?? '')) !== ''): ?>
                <div class="kuppi-wizard-review-row">
                    <span>Additional Notes</span>
                    <strong><?= nl2br(e((string) ($draft['notes'] ?? ''))) ?></strong>
                </div>
            <?php endif; ?>
        </section>

        <section class="kuppi-wizard-review-section">
            <h3>Assigned Conductors</h3>
            <div class="kuppi-wizard-review-hosts">
                <?php foreach ($selectedHosts as $host): ?>
                    <?php
                    $hostName = trim((string) ($host['host_name'] ?? 'Unknown User'));
                    if ($hostName === '') {
                        $hostName = 'Unknown User';
                    }
                    $toneClass = ui_avatar_tone_class((string) (((int) ($host['host_user_id'] ?? 0)) . '-' . $hostName));
                    ?>
                    <article class="kuppi-wizard-review-host-card">
                        <span class="kuppi-conductor-avatar <?= e($toneClass) ?>"><?= e(ui_initials($hostName)) ?></span>
                        <div>
                            <strong><?= e($hostName) ?></strong>
                            <p class="kuppi-wizard-muted-inline">
                                <?= e(ucfirst((string) ($host['host_role'] ?? 'student'))) ?>
                                <?php if ((int) ($host['host_academic_year'] ?? 0) > 0): ?>
                                    • Year <?= (int) ($host['host_academic_year'] ?? 0) ?>
                                <?php endif; ?>
                                <?php if ((int) ($host['vote_count'] ?? 0) > 0): ?>
                                    • <?= (int) ($host['vote_count'] ?? 0) ?> votes
                                <?php endif; ?>
                            </p>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <div class="kuppi-wizard-ready-box">
                <strong><?= ui_lucide_icon('check-circle') ?> Ready to Schedule</strong>
                <p>All participants and selected conductors will receive email notifications with session details.</p>
            </div>
        </section>

        <div class="kuppi-wizard-actions">
            <a href="/dashboard/kuppi/schedule/assign" class="btn btn-outline"><?= ui_lucide_icon('arrow-left') ?> Back</a>
            <form method="POST" action="/dashboard/kuppi/schedule/confirm">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-primary kuppi-wizard-cta"><?= ui_lucide_icon('check') ?> Schedule Session</button>
            </form>
        </div>
    </div>
</div>
