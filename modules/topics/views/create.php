<div class="page-header">
    <div class="page-header-content">
        <p class="page-breadcrumb"><?= e($role_label) ?> / Subjects / Topics</p>
        <h1>Create Topic</h1>
        <p class="page-subtitle">
            Add a new topic for <strong><?= e($subject['code']) ?> - <?= e($subject['name']) ?></strong>.
        </p>
    </div>
    <div class="page-header-actions">
        <a href="/subjects/<?= (int) $subject['id'] ?>/topics" class="btn btn-outline">← Back to Topics</a>
        <a href="<?= e($back_subjects_url) ?>" class="btn btn-outline">Subjects</a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="/subjects/<?= (int) $subject['id'] ?>/topics">
            <?= csrf_field() ?>

            <div class="form-group">
                <label for="title">Topic Title</label>
                <input type="text" id="title" name="title" value="<?= old('title') ?>" required maxlength="200">
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="5" placeholder="Brief topic description..."><?= old('description') ?></textarea>
            </div>

            <div class="form-group">
                <label for="sort_order">Sort Order</label>
                <input type="number" id="sort_order" name="sort_order" value="<?= old('sort_order', (string) $next_sort_order) ?>" min="1" required>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Create Topic</button>
                <a href="/subjects/<?= (int) $subject['id'] ?>/topics" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>
</div>
