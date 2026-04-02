<div class="page-header">
    <div class="page-header-content">
        <p class="page-breadcrumb">Community / My Posts</p>
        <h1>Edit Post</h1>
        <p class="page-subtitle">Update your post content and media.</p>
    </div>
    <div class="page-header-actions">
        <a href="/my-posts" class="btn btn-outline"><?= ui_lucide_icon('arrow-left') ?> Back to My Posts</a>
    </div>
</div>

<section class="community-composer-card">
    <form method="POST" action="/my-posts/<?= (int) $post['id'] ?>" enctype="multipart/form-data" class="community-composer-form">
        <?= csrf_field() ?>

        <div class="community-composer-grid">
            <div class="form-group">
                <label for="post_type">Post Type</label>
                <?php $selectedType = old('post_type', (string) ($post['post_type'] ?? 'general')); ?>
                <select id="post_type" name="post_type" required>
                    <?php foreach ($post_types as $postType): ?>
                        <option value="<?= e($postType) ?>" <?= $selectedType === $postType ? 'selected' : '' ?>>
                            <?= e(community_post_type_label($postType)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="subject_id">Subject (Optional)</label>
                <?php $selectedSubjectId = (int) old('subject_id', (string) ((int) ($post['subject_id'] ?? 0))); ?>
                <select id="subject_id" name="subject_id">
                    <option value="">General (No Subject)</option>
                    <?php foreach ($subject_options as $subject): ?>
                        <?php $subjectId = (int) ($subject['id'] ?? 0); ?>
                        <option value="<?= $subjectId ?>" <?= $selectedSubjectId === $subjectId ? 'selected' : '' ?>>
                            <?= e((string) ($subject['code'] ?? 'SUB')) ?> — <?= e((string) ($subject['name'] ?? '')) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label for="body">Post</label>
            <textarea id="body" name="body" rows="6" placeholder="Write your post..."><?= e(old('body', (string) ($post['body'] ?? ''))) ?></textarea>
        </div>

        <div class="form-group">
            <label for="image">Replace Image (Optional, max 10MB)</label>
            <input type="file" id="image" name="image" accept=".jpg,.jpeg,.png,.webp,.gif,image/*">
        </div>

        <?php if (!empty($post['image_path'])): ?>
            <div class="community-edit-image-preview">
                <img src="/community/<?= (int) $post['id'] ?>/image" alt="Current post image">
                <label class="checkbox-inline">
                    <input type="checkbox" name="remove_image" value="1" <?= old('remove_image') === '1' ? 'checked' : '' ?>>
                    Remove current image
                </label>
            </div>
        <?php endif; ?>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Save Changes</button>
            <a href="/my-posts" class="btn btn-outline">Cancel</a>
        </div>
    </form>
</section>
