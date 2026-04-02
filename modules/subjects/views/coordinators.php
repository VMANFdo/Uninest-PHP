<?php if ($success = get_flash('success')): ?>
    <div class="alert alert-success"><?= e($success) ?></div>
<?php endif; ?>
<?php if ($error = get_flash('error')): ?>
    <div class="alert alert-error"><?= e($error) ?></div>
<?php endif; ?>

<?php
$availableCandidates = array_values(array_filter(
    $candidates,
    static fn(array $candidate): bool => (int) ($candidate['is_assigned'] ?? 0) === 0
));
?>

<div class="page-header">
    <div class="page-header-content">
        <p class="page-breadcrumb"><?= $is_admin ? 'Admin / Subjects / Coordinators' : 'Moderator / Subjects / Coordinators' ?></p>
        <h1>Subject Coordinators</h1>
        <p class="page-subtitle">
            Assign students as coordinators for <strong><?= e($subject['name']) ?></strong> (<?= e($subject['code']) ?>).
        </p>
    </div>
    <div class="page-header-actions">
        <a href="/subjects/<?= (int) $subject['id'] ?>/edit" class="btn btn-outline">Edit Subject</a>
        <a href="/subjects" class="btn btn-outline"><?= ui_lucide_icon('arrow-left') ?> Back to Subjects</a>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2>Assign Coordinator</h2>
    </div>
    <div class="card-body">
        <?php if (empty($availableCandidates)): ?>
            <p class="text-muted">All eligible students in this batch are already assigned as coordinators.</p>
        <?php else: ?>
            <form method="POST" action="/subjects/<?= (int) $subject['id'] ?>/coordinators">
                <?= csrf_field() ?>
                <div class="form-group">
                    <label for="student_user_id">Student</label>
                    <select id="student_user_id" name="student_user_id" required>
                        <option value="">Select student</option>
                        <?php foreach ($availableCandidates as $candidate): ?>
                            <option value="<?= (int) $candidate['id'] ?>">
                                <?= e($candidate['name']) ?> (<?= e($candidate['email']) ?>) — Year <?= (int) ($candidate['academic_year'] ?? 1) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Assign Coordinator</button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2>Assigned Coordinators</h2>
    </div>
    <div class="card-body no-padding">
        <?php if (empty($coordinators)): ?>
            <div class="card-body">
                <p class="text-muted">No coordinators assigned to this subject yet.</p>
            </div>
        <?php else: ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Email</th>
                        <th>Year</th>
                        <th>Assigned At</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($coordinators as $coordinator): ?>
                        <?php
                        $avatarText = ui_initials((string) $coordinator['name']);
                        $avatarTone = ui_avatar_tone_class((string) ($coordinator['email'] ?? $coordinator['name']));
                        ?>
                        <tr>
                            <td>
                                <div class="table-identity">
                                    <span class="table-avatar <?= e($avatarTone) ?>"><?= e($avatarText) ?></span>
                                    <div class="table-identity-text">
                                        <strong><?= e($coordinator['name']) ?></strong>
                                    </div>
                                </div>
                            </td>
                            <td><?= e($coordinator['email']) ?></td>
                            <td><?= (int) ($coordinator['academic_year'] ?? 1) ?></td>
                            <td><?= e(date('Y-m-d', strtotime((string) $coordinator['created_at']))) ?></td>
                            <td class="actions">
                                <form method="POST" action="/subjects/<?= (int) $subject['id'] ?>/coordinators/<?= (int) $coordinator['id'] ?>/delete" class="table-action-form" onsubmit="return confirm('Remove this coordinator from the subject?');">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="table-icon-btn is-danger" title="Remove coordinator" aria-label="Remove coordinator">
                                        <?= ui_lucide_icon('x') ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
