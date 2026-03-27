<?php
$currentUri = (string) ($_SERVER['REQUEST_URI'] ?? '/dashboard/community');
$selectedBatchId = (int) ($selected_batch_id ?? 0);
$selectedSubjectId = (int) ($selected_subject_id ?? 0);
$selectedPostType = (string) ($selected_post_type ?? '');
$selectedSort = (string) ($selected_sort ?? 'recent');
$postTypeCounts = (array) ($post_type_counts ?? []);
$popularPosts = (array) ($popular_posts ?? []);

$buildFeedUrl = static function (array $params = []) use ($is_admin, $selectedBatchId, $selectedSubjectId, $selectedSort): string {
    $query = [];
    if (!empty($is_admin) && $selectedBatchId > 0) {
        $query['batch_id'] = $selectedBatchId;
    }
    if ($selectedSubjectId > 0) {
        $query['subject_id'] = $selectedSubjectId;
    }
    if ($selectedSort !== 'recent') {
        $query['sort'] = $selectedSort;
    }

    foreach ($params as $key => $value) {
        if ($value === null || $value === '') {
            unset($query[$key]);
            continue;
        }
        $query[$key] = $value;
    }

    return '/dashboard/community' . (!empty($query) ? '?' . http_build_query($query) : '');
};
?>

<?php if (!empty($is_admin) && $selectedBatchId <= 0): ?>
    <section class="community-admin-gate">
        <h3>Select Batch to Open Feed</h3>
        <p class="text-muted">Choose a batch first to view and moderate its feed.</p>
        <form method="GET" action="/dashboard/community" class="community-topbar-form">
            <div class="form-group">
                <label for="batch_id">Batch</label>
                <select id="batch_id" name="batch_id" required>
                    <option value="">Select a batch</option>
                    <?php foreach ($batch_options as $batch): ?>
                        <?php $batchId = (int) ($batch['id'] ?? 0); ?>
                        <option value="<?= $batchId ?>">
                            <?= e((string) ($batch['batch_code'] ?? 'BATCH')) ?> — <?= e((string) ($batch['name'] ?? '')) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Open Feed</button>
        </form>
    </section>
<?php else: ?>
    <section class="community-shell">
        <main class="community-main-column">
            <article class="community-topbar-card">
                <form method="GET" action="/dashboard/community" class="community-topbar-form">
                    <?php if (!empty($is_admin)): ?>
                        <div class="form-group">
                            <label for="batch_id">Batch</label>
                            <select id="batch_id" name="batch_id" required>
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
                                    <?= e((string) ($subject['code'] ?? 'SUB')) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="sort">Sort</label>
                        <select id="sort" name="sort">
                            <option value="recent" <?= $selectedSort === 'recent' ? 'selected' : '' ?>>Most Recent</option>
                            <option value="top" <?= $selectedSort === 'top' ? 'selected' : '' ?>>Top Engaged</option>
                        </select>
                    </div>

                    <?php if ($selectedPostType !== ''): ?>
                        <input type="hidden" name="post_type" value="<?= e($selectedPostType) ?>">
                    <?php endif; ?>

                    <div class="community-topbar-actions">
                        <a href="/dashboard" class="community-topbar-link">← Dashboard</a>
                        <button type="submit" class="btn btn-primary">Apply</button>
                        <a href="<?= e($buildFeedUrl(['subject_id' => null])) ?>" class="btn btn-outline">Reset</a>
                    </div>
                </form>
            </article>

            <nav class="community-category-strip" aria-label="Feed categories">
                <?php
                $allCount = (int) ($postTypeCounts['_all'] ?? 0);
                $allActive = $selectedPostType === '';
                ?>
                <a href="<?= e($buildFeedUrl(['post_type' => null])) ?>" class="community-category-pill <?= $allActive ? 'is-active' : '' ?>">
                    <span>All Posts</span>
                    <span class="community-category-count"><?= $allCount ?></span>
                </a>
                <?php foreach ($post_types as $postType): ?>
                    <?php
                    $isActive = $selectedPostType === $postType;
                    $typeCount = (int) ($postTypeCounts[$postType] ?? 0);
                    ?>
                    <a href="<?= e($buildFeedUrl(['post_type' => $postType])) ?>" class="community-category-pill <?= $isActive ? 'is-active' : '' ?>">
                        <span><?= e(community_post_type_label($postType)) ?></span>
                        <span class="community-category-count"><?= $typeCount ?></span>
                    </a>
                <?php endforeach; ?>
            </nav>

            <?php if (!empty($can_post)): ?>
                <article class="community-composer-card social-composer">
                    <form method="POST" action="/dashboard/community" enctype="multipart/form-data" class="community-composer-form">
                        <?= csrf_field() ?>
                        <div class="social-composer-row">
                            <span class="community-post-avatar"><?= e(ui_initials((string) (auth_user()['name'] ?? 'User'))) ?></span>
                            <textarea id="body" name="body" rows="3" placeholder="Share with your batch..."><?= e(old('body', '')) ?></textarea>
                        </div>

                        <div class="social-composer-controls">
                            <div class="social-composer-inline-fields">
                                <select id="post_type" name="post_type" required>
                                    <?php $selectedType = old('post_type', $selectedPostType !== '' ? $selectedPostType : 'general'); ?>
                                    <?php foreach ($post_types as $postType): ?>
                                        <option value="<?= e($postType) ?>" <?= $selectedType === $postType ? 'selected' : '' ?>>
                                            <?= e(community_post_type_label($postType)) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <select id="composer_subject_id" name="subject_id">
                                    <option value="">General (No Subject)</option>
                                    <?php $composerSubjectId = (int) old('subject_id', (string) $selectedSubjectId); ?>
                                    <?php foreach ($subject_options as $subject): ?>
                                        <?php $subjectId = (int) ($subject['id'] ?? 0); ?>
                                        <option value="<?= $subjectId ?>" <?= $composerSubjectId === $subjectId ? 'selected' : '' ?>>
                                            <?= e((string) ($subject['code'] ?? 'SUB')) ?> — <?= e((string) ($subject['name'] ?? '')) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <label class="social-upload-btn">
                                    <input type="file" name="image" accept=".jpg,.jpeg,.png,.webp,.gif,image/*">
                                    <span>Add Photo</span>
                                </label>
                            </div>
                            <button type="submit" class="btn btn-primary">Publish</button>
                        </div>
                    </form>
                </article>
            <?php endif; ?>

            <?php if (empty($posts)): ?>
                <article class="community-post-card">
                    <p class="text-muted">No posts found for the current filters.</p>
                </article>
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
                        <article class="community-post-card social-post-card">
                            <header class="community-post-header">
                                <div class="community-post-author">
                                    <span class="community-post-avatar"><?= e(ui_initials($authorName)) ?></span>
                                    <div>
                                        <strong><?= e($authorName) ?></strong>
                                        <div class="community-post-meta-line">
                                            <span><?= e(date('M d, Y • H:i', strtotime((string) ($post['created_at'] ?? 'now')))) ?></span>
                                            <?php if (!empty($post['edited_at'])): ?>
                                                <span>• Edited</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="community-post-badges">
                                    <span class="badge <?= e($badgeClass) ?>"><?= e(community_post_type_label((string) ($post['post_type'] ?? 'general'))) ?></span>
                                    <?php if (!empty($post['subject_code'])): ?>
                                        <span class="badge"><?= e((string) $post['subject_code']) ?></span>
                                    <?php endif; ?>
                                </div>
                            </header>

                            <?php if ($postBody !== ''): ?>
                                <p class="community-post-body"><?= nl2br(e($postBody)) ?></p>
                            <?php endif; ?>

                            <?php if ($hasImage): ?>
                                <a href="<?= e(community_post_url($post)) ?>" class="community-post-image-link" aria-label="Open post image">
                                    <img src="/community/<?= $postId ?>/image" alt="Post image by <?= e($authorName) ?>">
                                </a>
                            <?php endif; ?>

                            <div class="community-post-stats-row">
                                <span><?= (int) ($post['like_count'] ?? 0) ?> likes</span>
                                <span><?= (int) ($post['comment_count'] ?? 0) ?> comments</span>
                            </div>

                            <footer class="community-post-footer social-post-actions">
                                <form method="POST" action="/dashboard/community/<?= $postId ?>/like">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="return_to" value="<?= e($currentUri) ?>">
                                    <button type="submit" class="community-action-btn <?= $likedByViewer ? 'is-active' : '' ?>">
                                        <?= $likedByViewer ? 'Liked' : 'Like' ?>
                                    </button>
                                </form>
                                <a href="<?= e(community_post_url($post)) ?>" class="community-action-btn">Comment</a>
                                <a href="<?= e(community_post_url($post)) ?>" class="community-action-btn">View Full Post</a>
                            </footer>
                        </article>
                    <?php endforeach; ?>
                </section>
            <?php endif; ?>
        </main>

        <aside class="community-right-rail">
            <article class="community-rail-card">
                <header class="community-rail-header">
                    <h3>Feed State</h3>
                </header>
                <ul class="community-mini-list">
                    <li>
                        <span>Sort</span>
                        <strong><?= $selectedSort === 'top' ? 'Top Engaged' : 'Most Recent' ?></strong>
                    </li>
                    <li>
                        <span>Subject</span>
                        <strong><?= $selectedSubjectId > 0 ? 'Filtered' : 'All' ?></strong>
                    </li>
                    <li>
                        <span>Category</span>
                        <strong><?= $selectedPostType !== '' ? e(community_post_type_label($selectedPostType)) : 'All Posts' ?></strong>
                    </li>
                </ul>
            </article>

            <article class="community-rail-card">
                <header class="community-rail-header">
                    <h3>Top Discussions</h3>
                </header>
                <?php if (empty($popularPosts)): ?>
                    <p class="text-muted">No popular posts yet.</p>
                <?php else: ?>
                    <div class="community-popular-list">
                        <?php foreach ($popularPosts as $popular): ?>
                            <?php
                            $popularAuthor = trim((string) ($popular['author_name'] ?? ''));
                            if ($popularAuthor === '') {
                                $popularAuthor = 'Unknown User';
                            }
                            $popularBody = trim((string) ($popular['body'] ?? ''));
                            if ($popularBody === '') {
                                $popularBody = 'Image post';
                            }
                            ?>
                            <a href="<?= e(community_post_url($popular)) ?>" class="community-popular-item">
                                <strong><?= e($popularAuthor) ?></strong>
                                <p><?= e(strlen($popularBody) > 92 ? substr($popularBody, 0, 92) . '...' : $popularBody) ?></p>
                                <small><?= (int) ($popular['like_count'] ?? 0) ?> likes · <?= (int) ($popular['comment_count'] ?? 0) ?> comments</small>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </article>
        </aside>
    </section>
<?php endif; ?>
