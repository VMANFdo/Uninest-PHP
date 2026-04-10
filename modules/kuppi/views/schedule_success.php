<?php
$session = (array) ($session ?? []);
$sessionId = (int) ($session['id'] ?? 0);
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
        <span class="kuppi-wizard-step-icon"><?= ui_lucide_icon('user-check') ?></span>
        <strong>Assign Hosts</strong>
    </div>
    <div class="kuppi-wizard-step is-complete">
        <span class="kuppi-wizard-step-icon"><?= ui_lucide_icon('calendar') ?></span>
        <strong>Set Schedule</strong>
    </div>
    <div class="kuppi-wizard-step is-complete">
        <span class="kuppi-wizard-step-icon"><?= ui_lucide_icon('check-circle') ?></span>
        <strong>Review & Confirm</strong>
    </div>
</section>

<div class="card kuppi-wizard-card kuppi-wizard-success-card">
    <div class="card-body">
        <div class="kuppi-wizard-success-mark"><?= ui_lucide_icon('check-circle') ?></div>
        <h2>Session Successfully Scheduled!</h2>
        <p class="kuppi-wizard-muted">
            The kuppi session has been scheduled. All participants and selected conductors have been notified via email.
        </p>

        <div class="kuppi-wizard-actions kuppi-wizard-actions-center">
            <a href="/dashboard/kuppi/scheduled" class="btn btn-primary kuppi-wizard-cta"><?= ui_lucide_icon('calendar') ?> View Scheduled Sessions</a>
            <a href="/dashboard/kuppi/schedule" class="btn btn-outline">Schedule Another</a>
            <?php if ($sessionId > 0): ?>
                <a href="/dashboard/kuppi/scheduled/<?= $sessionId ?>" class="btn btn-outline">Open Session</a>
            <?php endif; ?>
        </div>
    </div>
</div>
