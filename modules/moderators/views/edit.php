<?php
$ownedBatchId = (int) ($moderator['owned_batch_id'] ?? 0);
$batchOptions = $batches;

if ($ownedBatchId > 0) {
    $hasOwnedInOptions = false;
    foreach ($batchOptions as $option) {
        if ((int) ($option['id'] ?? 0) === $ownedBatchId) {
            $hasOwnedInOptions = true;
            break;
        }
    }

    if (!$hasOwnedInOptions) {
        $batchOptions[] = [
            'id' => $ownedBatchId,
            'name' => $moderator['owned_batch_name'] ?? 'Owned Batch',
            'batch_code' => $moderator['owned_batch_code'] ?? '',
        ];
    }
}
?>

<div class="page-header">
    <div class="page-header-content">
        <p class="page-breadcrumb">Admin / Moderators</p>
        <h1>Edit Moderator</h1>
        <p class="page-subtitle">Update moderator profile details and batch assignment rules.</p>
    </div>
    <div class="page-header-actions">
        <a href="/admin/moderators" class="btn btn-outline"><?= ui_lucide_icon('arrow-left') ?> Back to Moderators</a>
    </div>
</div>

<?php if ($ownedBatchId > 0): ?>
    <div class="alert alert-warning">
        This moderator is the primary owner of <strong><?= e($moderator['owned_batch_name'] ?? 'a batch') ?></strong>
        (<?= e($moderator['owned_batch_code'] ?? 'N/A') ?>).
        Their batch assignment cannot be removed or switched until ownership is reassigned.
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form method="POST" action="/admin/moderators/<?= (int) $moderator['id'] ?>">
            <?= csrf_field() ?>

            <div class="form-group">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" value="<?= old('name', (string) $moderator['name']) ?>" required maxlength="100">
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?= old('email', (string) $moderator['email']) ?>" required maxlength="150">
            </div>

            <div class="form-group">
                <label for="password">New Password (Optional)</label>
                <input type="password" id="password" name="password" minlength="6" placeholder="Leave blank to keep current password">
            </div>

            <div class="form-group">
                <label for="password_confirmation">Confirm New Password</label>
                <input type="password" id="password_confirmation" name="password_confirmation" minlength="6" placeholder="Only required when changing password">
            </div>

            <div class="form-group">
                <label for="academic_year">Academic Year</label>
                <?php $selectedYear = old('academic_year', (string) $moderator['academic_year']); ?>
                <select id="academic_year" name="academic_year" required>
                    <?php for ($year = 1; $year <= 8; $year++): ?>
                        <option value="<?= $year ?>" <?= $selectedYear === (string) $year ? 'selected' : '' ?>>Year <?= $year ?></option>
                    <?php endfor; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="university_id">University</label>
                <?php $selectedUniversity = old('university_id', (string) $moderator['university_id']); ?>
                <select id="university_id" name="university_id" required>
                    <option value="">Select university</option>
                    <?php foreach ($universities as $university): ?>
                        <option value="<?= (int) $university['id'] ?>" <?= $selectedUniversity === (string) $university['id'] ? 'selected' : '' ?>>
                            <?= e($university['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="batch_id">Assigned Batch</label>
                <?php $selectedBatch = old('batch_id', (string) ($moderator['batch_id'] ?? '')); ?>
                <select id="batch_id" name="batch_id">
                    <option value="" <?= $ownedBatchId > 0 ? 'disabled' : '' ?>>No batch assignment</option>
                    <?php foreach ($batchOptions as $batch): ?>
                        <option value="<?= (int) $batch['id'] ?>" <?= $selectedBatch === (string) $batch['id'] ? 'selected' : '' ?>>
                            <?= e($batch['name']) ?> (<?= e($batch['batch_code']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <small class="text-muted">One moderator can manage only one batch. Multiple moderators can be assigned to the same batch.</small>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Update Moderator</button>
                <a href="/admin/moderators" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>
</div>
