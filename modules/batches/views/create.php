<div class="page-header">
    <div class="page-header-content">
        <p class="page-breadcrumb">Admin / Batches</p>
        <h1>Create Batch</h1>
        <p class="page-subtitle">Create and approve a new batch, then assign a primary moderator in one step.</p>
    </div>
    <div class="page-header-actions">
        <a href="/admin/batches" class="btn btn-outline">← Back to Batches</a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <?php if (empty($moderators)): ?>
            <div class="alert alert-warning">
                No available moderators found.
                <a href="/admin/moderators/create">Create a moderator</a> first, then return to create the batch.
            </div>
        <?php endif; ?>

        <form method="POST" action="/admin/batches">
            <?= csrf_field() ?>

            <div class="form-group">
                <label for="name">Batch Name</label>
                <input type="text" id="name" name="name" value="<?= old('name') ?>" required maxlength="150" placeholder="e.g. CS 24/25">
            </div>

            <div class="form-group">
                <label for="program">Program</label>
                <input type="text" id="program" name="program" value="<?= old('program') ?>" required maxlength="150" placeholder="e.g. BSc Computer Science">
            </div>

            <div class="form-group">
                <label for="intake_year">Intake Year</label>
                <input type="number" id="intake_year" name="intake_year" value="<?= old('intake_year', date('Y')) ?>" min="2000" max="2100" required>
            </div>

            <div class="form-group">
                <label for="university_id">University</label>
                <?php $selectedUniversity = old('university_id'); ?>
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
                <?php $selectedModerator = old('moderator_user_id'); ?>
                <select id="moderator_user_id" name="moderator_user_id" required>
                    <option value=""><?= empty($moderators) ? 'No available moderators' : 'Select moderator' ?></option>
                    <?php foreach ($moderators as $moderator): ?>
                        <option value="<?= (int) $moderator['id'] ?>" <?= $selectedModerator === (string) $moderator['id'] ? 'selected' : '' ?>>
                            <?= e($moderator['name']) ?> (<?= e($moderator['email']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <small class="text-muted">This moderator becomes the batch owner. You can assign more moderators to this same batch from the moderator creation page.</small>
            </div>

            <div class="form-group">
                <label for="batch_code">Batch ID (Optional)</label>
                <input type="text" id="batch_code" name="batch_code" value="<?= old('batch_code') ?>" maxlength="20" placeholder="Leave empty to auto-generate (e.g. BATCH-A1B2C3)">
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary" <?= empty($moderators) ? 'disabled' : '' ?>>Create Batch</button>
                <a href="/admin/batches" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>
</div>
