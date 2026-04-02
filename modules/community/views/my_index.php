<div class="page-header">
    <div class="page-header-content">
        <p class="page-breadcrumb">Community / My Posts</p>
        <h1>My Posts</h1>
        <p class="page-subtitle">Manage all community posts you have published.</p>
    </div>
    <div class="page-header-actions">
        <a href="/dashboard/community" class="btn btn-outline"><?= ui_lucide_icon('arrow-left') ?> Back to Feed</a>
    </div>
</div>

<?php if (empty($posts)): ?>
    <div class="card">
        <div class="card-body">
            <p class="text-muted">You have not published any posts yet.</p>
        </div>
    </div>
<?php else: ?>
    <section class="community-post-list">
        <?php foreach ($posts as $post): ?>
            <?php
            $postId = (int) ($post['id'] ?? 0);
            $postBody = trim((string) ($post['body'] ?? ''));
            $hasImage = trim((string) ($post['image_path'] ?? '')) !== '';
            ?>
            <article class="community-post-card">
                <header class="community-post-header">
                    <div>
                        <strong><?= e(community_post_type_label((string) ($post['post_type'] ?? 'general'))) ?></strong>
                        <div class="community-post-meta-line">
                            <span><?= e(date('Y-m-d H:i', strtotime((string) ($post['updated_at'] ?? 'now')))) ?></span>
                            <?php if (!empty($post['subject_code'])): ?>
                                <span>• <?= e((string) $post['subject_code']) ?></span>
                            <?php endif; ?>
                            <?php if (!empty($post['edited_at'])): ?>
                                <span>• Edited</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="community-post-badges">
                        <span class="badge <?= e(community_post_type_badge_class((string) ($post['post_type'] ?? 'general'))) ?>">
                            <?= e(community_post_type_label((string) ($post['post_type'] ?? 'general'))) ?>
                        </span>
                    </div>
                </header>

                <?php if ($postBody !== ''): ?>
                    <p class="community-post-body">
                        <?= nl2br(e(strlen($postBody) > 320 ? substr($postBody, 0, 320) . '...' : $postBody)) ?>
                    </p>
                <?php endif; ?>

                <?php if ($hasImage): ?>
                    <a href="/dashboard/community/<?= $postId ?>" class="community-post-image-link" aria-label="Open post">
                        <img src="/community/<?= $postId ?>/image" alt="Post image">
                    </a>
                <?php endif; ?>

                <footer class="community-post-footer">
                    <div class="community-post-stats">
                        <span><?= (int) ($post['like_count'] ?? 0) ?> likes</span>
                        <span><?= (int) ($post['comment_count'] ?? 0) ?> comments</span>
                    </div>
                    <div class="community-post-actions">
                        <a href="/dashboard/community/<?= $postId ?>" class="btn btn-sm btn-outline">Open</a>
                        <a href="/my-posts/<?= $postId ?>/edit" class="btn btn-sm btn-outline">Edit</a>
                        <form method="POST" action="/my-posts/<?= $postId ?>/delete" onsubmit="return confirm('Delete this post? This will also delete all comments.');">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-sm btn-outline">Delete</button>
                        </form>
                    </div>
                </footer>
            </article>
        <?php endforeach; ?>
    </section>
<?php endif; ?>
