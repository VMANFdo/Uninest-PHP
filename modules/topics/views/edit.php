<div class="page-header">
    <div class="page-header-content">
        <p class="page-breadcrumb"><?= e($role_label) ?> / Subjects / Topics</p>
        <h1>Edit Topic</h1>
        <p class="page-subtitle">
            Update topic details for <strong><?= e($subject['code']) ?> - <?= e($subject['name']) ?></strong>.
        </p>
    </div>
    <div class="page-header-actions">
        <a href="/subjects/<?= (int) $subject['id'] ?>/topics" class="btn btn-outline"><?= ui_lucide_icon('arrow-left') ?> Back to Topics</a>
        <a href="<?= e($back_subjects_url) ?>" class="btn btn-outline">Subjects</a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="/subjects/<?= (int) $subject['id'] ?>/topics/<?= (int) $topic['id'] ?>">
            <?= csrf_field() ?>

            <div class="form-group">
                <label for="title">Topic Title</label>
                <input type="text" id="title" name="title" value="<?= old('title', (string) $topic['title']) ?>" required maxlength="200">
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="5" placeholder="Brief topic description..."><?= old('description', (string) ($topic['description'] ?? '')) ?></textarea>
            </div>

            <div class="form-group">
                <label for="sort_order">Sort Order</label>
                <input type="number" id="sort_order" name="sort_order" value="<?= old('sort_order', (string) $topic['sort_order']) ?>" min="1" required>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Update Topic</button>
                <a href="/subjects/<?= (int) $subject['id'] ?>/topics" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>
</div>
