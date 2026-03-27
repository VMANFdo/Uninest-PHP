<?php
$currentUri = (string) ($_SERVER['REQUEST_URI'] ?? '/dashboard/community');
$selectedBatchId = (int) ($selected_batch_id ?? 0);
$selectedSubjectId = (int) ($selected_subject_id ?? 0);
?>

<div class="page-header">
    <div class="page-header-content">
        <p class="page-breadcrumb">Dashboard / Community Feed</p>
        <h1>Batch Community Feed</h1>
        <p class="page-subtitle">Discuss, ask questions, and share updates with your batch community.</p>
    </div>
    <div class="page-header-actions">
        <a href="/dashboard" class="btn btn-outline">← Back to Dashboard</a>
    </div>
</div>

<section class="community-filters-card">
    <form method="GET" action="/dashboard/community" class="community-filters-form">
        <?php if (!empty($is_admin)): ?>
            <div class="form-group">
                <label for="batch_id">Batch</label>
                <select id="batch_id" name="batch_id" required>
                    <option value="">Select a batch</option>
                    <?php foreach ($batch_options as $batch): ?>
                        <?php $batchId = (int) ($batch['id'] ?? 0); ?>
                        <option value="<?= $batchId ?>" <?= $selectedBatchId === $batchId ? 'selected' : '' ?>>
                            <?= e((string) ($batch['batch_code'] ?? 'BATCH')) ?> — <?= e((string) ($batch['name'] ?? '')) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>

        <div class="form-group">
            <label for="subject_id">Subject</label>
            <select id="subject_id" name="subject_id">
                <option value="">All subjects</option>
                <?php foreach ($subject_options as $subject): ?>
                    <?php $subjectId = (int) ($subject['id'] ?? 0); ?>
                    <option value="<?= $subjectId ?>" <?= $selectedSubjectId === $subjectId ? 'selected' : '' ?>>
                        <?= e((string) ($subject['code'] ?? 'SUB')) ?> — <?= e((string) ($subject['name'] ?? '')) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="community-filters-actions">
            <button type="submit" class="btn btn-primary">Apply</button>
            <?php if (!empty($is_admin)): ?>
                <a href="/dashboard/community" class="btn btn-outline">Reset</a>
            <?php else: ?>
                <a href="/dashboard/community" class="btn btn-outline">Clear Subject</a>
            <?php endif; ?>
        </div>
    </form>
</section>

<?php if (!empty($is_admin) && $selectedBatchId <= 0): ?>
    <div class="card">
        <div class="card-body">
            <p class="text-muted">Select a batch to view and moderate its community feed.</p>
        </div>
    </div>
<?php else: ?>
    <?php if (!empty($can_post)): ?>
        <section class="community-composer-card">
            <form method="POST" action="/dashboard/community" enctype="multipart/form-data" class="community-composer-form">
                <?= csrf_field() ?>
                <div class="community-composer-grid">
                    <div class="form-group">
                        <label for="post_type">Post Type</label>
                        <?php $selectedType = old('post_type', 'general'); ?>
                        <select id="post_type" name="post_type" required>
                            <?php foreach ($post_types as $postType): ?>
                                <option value="<?= e($postType) ?>" <?= $selectedType === $postType ? 'selected' : '' ?>>
                                    <?= e(community_post_type_label($postType)) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="composer_subject_id">Subject (Optional)</label>
                        <?php $composerSubjectId = (int) old('subject_id', '0'); ?>
                        <select id="composer_subject_id" name="subject_id">
                            <option value="">General (No Subject)</option>
                            <?php foreach ($subject_options as $subject): ?>
                                <?php $subjectId = (int) ($subject['id'] ?? 0); ?>
                                <option value="<?= $subjectId ?>" <?= $composerSubjectId === $subjectId ? 'selected' : '' ?>>
                                    <?= e((string) ($subject['code'] ?? 'SUB')) ?> — <?= e((string) ($subject['name'] ?? '')) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="body">Post</label>
                    <textarea id="body" name="body" rows="4" placeholder="Share a thought, question, or update..."><?= e(old('body', '')) ?></textarea>
                </div>

                <div class="form-group">
                    <label for="image">Image (Optional, max 10MB)</label>
                    <input type="file" id="image" name="image" accept=".jpg,.jpeg,.png,.webp,.gif,image/*">
                </div>

                <div class="community-composer-actions">
                    <small class="text-muted">Post text or image, or both. Allowed images: JPG, PNG, WEBP, GIF.</small>
                    <button type="submit" class="btn btn-primary">Publish Post</button>
                </div>
            </form>
        </section>
    <?php endif; ?>

    <?php if (empty($posts)): ?>
        <div class="card">
            <div class="card-body">
                <p class="text-muted">No posts yet. Start the first discussion for this batch.</p>
            </div>
        </div>
    <?php else: ?>
        <section class="community-post-list">
            <?php foreach ($posts as $post): ?>
                <?php
                $postId = (int) ($post['id'] ?? 0);
                $authorName = trim((string) ($post['author_name'] ?? ''));
                if ($authorName === '') {
                    $authorName = 'Unknown User';
                }
                $likedByViewer = (int) ($post['is_liked_by_viewer'] ?? 0) === 1;
                $postBody = trim((string) ($post['body'] ?? ''));
                $hasImage = trim((string) ($post['image_path'] ?? '')) !== '';
                $badgeClass = community_post_type_badge_class((string) ($post['post_type'] ?? 'general'));
                ?>
                <article class="community-post-card">
                    <header class="community-post-header">
                        <div class="community-post-author">
                            <span class="community-post-avatar"><?= e(ui_initials($authorName)) ?></span>
                            <div>
                                <strong><?= e($authorName) ?></strong>
                                <div class="community-post-meta-line">
                                    <span><?= e(date('Y-m-d H:i', strtotime((string) ($post['created_at'] ?? 'now')))) ?></span>
                                    <?php if (!empty($post['edited_at'])): ?>
                                        <span>• Edited</span>
                                    <?php endif; ?>
                                    <?php if (!empty($post['subject_code'])): ?>
                                        <span>• <?= e((string) $post['subject_code']) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="community-post-badges">
                            <span class="badge <?= e($badgeClass) ?>"><?= e(community_post_type_label((string) ($post['post_type'] ?? 'general'))) ?></span>
                            <?php if (!empty($post['subject_name'])): ?>
                                <span class="badge"><?= e((string) $post['subject_name']) ?></span>
                            <?php endif; ?>
                        </div>
                    </header>

                    <?php if ($postBody !== ''): ?>
                        <p class="community-post-body">
                            <?= nl2br(e(strlen($postBody) > 420 ? substr($postBody, 0, 420) . '...' : $postBody)) ?>
                        </p>
                    <?php endif; ?>

                    <?php if ($hasImage): ?>
                        <a href="<?= e(community_post_url($post)) ?>" class="community-post-image-link" aria-label="Open post image">
                            <img src="/community/<?= $postId ?>/image" alt="Post image for <?= e($authorName) ?>">
                        </a>
                    <?php endif; ?>

                    <footer class="community-post-footer">
                        <div class="community-post-stats">
                            <span><?= (int) ($post['like_count'] ?? 0) ?> likes</span>
                            <span><?= (int) ($post['comment_count'] ?? 0) ?> comments</span>
                        </div>
                        <div class="community-post-actions">
                            <form method="POST" action="/dashboard/community/<?= $postId ?>/like">
                                <?= csrf_field() ?>
                                <input type="hidden" name="return_to" value="<?= e($currentUri) ?>">
                                <button type="submit" class="btn btn-sm <?= $likedByViewer ? 'btn-primary' : 'btn-outline' ?>">
                                    <?= $likedByViewer ? 'Liked' : 'Like' ?>
                                </button>
                            </form>
                            <a href="<?= e(community_post_url($post)) ?>" class="btn btn-sm btn-outline">Open Discussion</a>
                        </div>
                    </footer>
                </article>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>
<?php endif; ?>
