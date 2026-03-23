<div class="page-header">
    <div class="page-header-content">
        <p class="page-breadcrumb">Admin / Batches</p>
        <h1>Edit Batch</h1>
        <p class="page-subtitle">Update batch settings, ownership, and status while keeping moderator assignments consistent.</p>
    </div>
    <div class="page-header-actions">
        <a href="/admin/batches" class="btn btn-outline">← Back to Batches</a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="/admin/batches/<?= (int) $batch['id'] ?>">
            <?= csrf_field() ?>

            <div class="form-group">
                <label for="batch_code">Batch ID</label>
                <input type="text" id="batch_code" name="batch_code" value="<?= old('batch_code', (string) ($batch['batch_code'] ?? '')) ?>" maxlength="20" required>
            </div>

            <div class="form-group">
                <label for="name">Batch Name</label>
                <input type="text" id="name" name="name" value="<?= old('name', (string) $batch['name']) ?>" maxlength="150" required>
            </div>

            <div class="form-group">
                <label for="program">Program</label>
                <input type="text" id="program" name="program" value="<?= old('program', (string) $batch['program']) ?>" maxlength="150" required>
            </div>

            <div class="form-group">
                <label for="intake_year">Intake Year</label>
                <input type="number" id="intake_year" name="intake_year" value="<?= old('intake_year', (string) $batch['intake_year']) ?>" min="2000" max="2100" required>
            </div>

            <div class="form-group">
                <label for="university_id">University</label>
                <?php $selectedUniversity = old('university_id', (string) $batch['university_id']); ?>
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
                <label for="moderator_user_id">Primary Moderator</label>
                <?php $selectedModerator = old('moderator_user_id', (string) $batch['moderator_user_id']); ?>
                <select id="moderator_user_id" name="moderator_user_id" required>
                    <option value="">Select moderator</option>
                    <?php foreach ($moderators as $moderator): ?>
                        <option value="<?= (int) $moderator['id'] ?>" <?= $selectedModerator === (string) $moderator['id'] ? 'selected' : '' ?>>
                            <?= e($moderator['name']) ?> (<?= e($moderator['email']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <small class="text-muted">Primary ownership can be changed. Multiple moderators can still be assigned to this same batch via moderator management.</small>
            </div>

            <div class="form-group">
                <label for="status">Status</label>
                <?php $selectedStatus = old('status', (string) $batch['status']); ?>
                <select id="status" name="status" required>
                    <?php foreach (['pending', 'approved', 'rejected', 'inactive'] as $status): ?>
                        <option value="<?= e($status) ?>" <?= $selectedStatus === $status ? 'selected' : '' ?>>
                            <?= e(ucfirst($status)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="rejection_reason">Rejection Reason (required if status is rejected)</label>
                <textarea id="rejection_reason" name="rejection_reason" rows="3" placeholder="Optional unless status is rejected"><?= old('rejection_reason', (string) ($batch['rejection_reason'] ?? '')) ?></textarea>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Update Batch</button>
                <a href="/admin/batches" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>
</div>
