<div class="page-header">
    <div class="page-header-content">
        <p class="page-breadcrumb">Admin / Moderators</p>
        <h1>Create Moderator</h1>
        <p class="page-subtitle">Create a moderator account and optionally assign it to an approved batch.</p>
    </div>
    <div class="page-header-actions">
        <a href="/admin/moderators" class="btn btn-outline"><?= ui_lucide_icon('arrow-left') ?> Back to Moderators</a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="/admin/moderators">
            <?= csrf_field() ?>

            <div class="form-group">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" value="<?= old('name') ?>" required maxlength="100">
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?= old('email') ?>" required maxlength="150" placeholder="e.g. m2@uninest.com">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required minlength="6">
            </div>

            <div class="form-group">
                <label for="password_confirmation">Confirm Password</label>
                <input type="password" id="password_confirmation" name="password_confirmation" required minlength="6">
            </div>

            <div class="form-group">
                <label for="academic_year">Academic Year</label>
                <?php $selectedYear = old('academic_year', '1'); ?>
                <select id="academic_year" name="academic_year" required>
                    <?php for ($year = 1; $year <= 4; $year++): ?>
                        <option value="<?= $year ?>" <?= $selectedYear === (string) $year ? 'selected' : '' ?>>Year <?= $year ?></option>
                    <?php endfor; ?>
                </select>
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
                <label for="batch_id">Assign Batch (Optional)</label>
                <?php $selectedBatch = old('batch_id'); ?>
                <select id="batch_id" name="batch_id">
                    <option value="">No batch assignment yet</option>
                    <?php foreach ($batches as $batch): ?>
                        <option value="<?= (int) $batch['id'] ?>" <?= $selectedBatch === (string) $batch['id'] ? 'selected' : '' ?>>
                            <?= e($batch['name']) ?> (<?= e($batch['batch_code']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <small class="text-muted">A moderator can manage only one batch. You can still assign multiple moderators to the same batch.</small>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Create Moderator</button>
                <a href="/admin/moderators" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>
</div>
