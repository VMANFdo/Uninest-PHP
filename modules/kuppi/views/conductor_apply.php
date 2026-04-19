<?php
$requestId = (int) ($request['id'] ?? 0);
$availabilityOptions = (array) ($availability_options ?? []);
$application = (array) ($application ?? []);
$isEdit = !empty($is_edit);
$formAction = (string) ($form_action ?? ('/dashboard/kuppi/' . $requestId . '/conductors/apply'));
$deleteAction = (string) ($delete_action ?? '');
$submitLabel = (string) ($submit_label ?? ($isEdit ? 'Update Application' : 'Submit Application'));
$existingAvailability = kuppi_conductor_availability_from_csv((string) ($application['availability_csv'] ?? ''));
$selectedAvailability = $_SESSION['_old_input']['availability'] ?? null;
if (!is_array($selectedAvailability)) {
    $selectedAvailability = $existingAvailability;
}
$motivationValue = old('motivation', (string) ($application['motivation'] ?? ''));
?>

<div class="page-header">
    <div class="page-header-content">
        <p class="page-breadcrumb">Dashboard / Kuppi Sessions / Conductor Application</p>
        <h1><?= $isEdit ? 'Edit Conductor Application' : 'Apply to Be a Conductor' ?></h1>
        <p class="page-subtitle">
            <?= $isEdit ? 'Update your motivation and availability for this session.' : 'Lead this Kuppi session and help peers learn with confidence.' ?>
        </p>
    </div>
    <div class="page-header-actions">
        <a href="<?= e((string) $back_request_url) ?>" class="btn btn-outline"><?= ui_lucide_icon('arrow-left') ?> Back to Session</a>
    </div>
</div>

<article class="kuppi-apply-request-card">
    <p class="page-breadcrumb">Applying for Session</p>
    <h2><?= e((string) ($request['title'] ?? 'Requested Session')) ?></h2>
    <p class="kuppi-request-meta">
        <?php if (!empty($request['subject_code'])): ?>
            <span class="badge"><?= e((string) $request['subject_code']) ?></span>
        <?php endif; ?>
        Requested by <?= e((string) ($request['requester_name'] ?? 'Unknown User')) ?>
        • <?= (int) ($request['vote_score'] ?? 0) ?> votes
    </p>
</article>

<article class="kuppi-apply-info-card">
    <strong>About Being a Conductor</strong>
    <p>
        As a conductor, you will host the session and guide fellow students through this topic.
        Students vote on applied conductors, and the most-voted candidate can lead the session.
    </p>
</article>

<div class="card kuppi-apply-form-card">
    <div class="card-body">
        <form method="POST" action="<?= e($formAction) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="return_to" value="<?= e((string) $back_request_url) ?>">

            <div class="form-group">
                <label for="motivation">Why do you want to conduct this session?</label>
                <textarea id="motivation" name="motivation" rows="4" maxlength="300" required placeholder="Share your interest in this topic and how you can help others learn..."><?= e($motivationValue) ?></textarea>
                <small class="text-muted">Maximum 300 characters.</small>
            </div>

            <div class="form-group">
                <label>When are you available?</label>
                <p class="text-muted">Select all time slots when you can conduct this session.</p>
                <div class="kuppi-availability-grid">
                    <?php foreach ($availabilityOptions as $slotKey => $slotLabel): ?>
                        <label class="kuppi-availability-item">
                            <input type="checkbox" name="availability[]" value="<?= e((string) $slotKey) ?>" <?= in_array($slotKey, $selectedAvailability, true) ? 'checked' : '' ?>>
                            <span><?= e((string) $slotLabel) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="form-actions">
                <a href="<?= e((string) $back_request_url) ?>" class="btn btn-outline">Cancel</a>
                <?php if ($isEdit && $deleteAction !== ''): ?>
                    <button
                        type="submit"
                        formaction="<?= e($deleteAction) ?>"
                        formnovalidate
                        class="btn btn-outline"
                        onclick="return confirm('Delete your conductor application?');">
                        Delete Application
                    </button>
                <?php endif; ?>
                <button type="submit" class="btn btn-primary"><?= e($submitLabel) ?></button>
            </div>
        </form>
    </div>
</div>
