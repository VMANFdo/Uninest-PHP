<?php if ($error = get_flash('error')): ?>
    <div class="alert alert-error"><?= e($error) ?></div>
<?php endif; ?>

<div class="page-header">
    <h1>Create Subject</h1>
    <a href="/subjects" class="btn btn-outline">← Back to Subjects</a>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="/subjects">
            <?= csrf_field() ?>

            <div class="form-group">
                <label for="code">Subject Code</label>
                <input type="text" id="code" name="code" value="<?= old('code') ?>" placeholder="e.g. CS101" required maxlength="20">
            </div>

            <div class="form-group">
                <label for="name">Subject Name</label>
                <input type="text" id="name" name="name" value="<?= old('name') ?>" placeholder="e.g. Introduction to Computer Science" required maxlength="200">
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="4" placeholder="Brief description of the subject..."><?= old('description') ?></textarea>
            </div>

            <div class="form-group">
                <label for="credits">Credits</label>
                <input type="number" id="credits" name="credits" value="<?= old('credits', '3') ?>" min="1" max="10" required>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Create Subject</button>
                <a href="/subjects" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>
</div>
