<div class="page-header">
    <div class="page-header-content">
        <p class="page-breadcrumb"><?= e($role_label) ?> / Subjects / Topics</p>
        <h1><?= e($subject['code']) ?> Topics</h1>
        <p class="page-subtitle">Explore topic cards for <strong><?= e($subject['name']) ?></strong>.</p>
    </div>
    <div class="page-header-actions">
        <?php if ($can_manage): ?>
            <a href="/subjects/<?= (int) $subject['id'] ?>/topics" class="btn btn-primary">Manage Topics</a>
            <a href="/subjects/<?= (int) $subject['id'] ?>/topics/create" class="btn btn-outline">+ New Topic</a>
        <?php endif; ?>
        <a href="/dashboard/subjects" class="btn btn-outline">← Back to Subjects</a>
    </div>
</div>

<?php if (empty($topics)): ?>
    <div class="card">
        <div class="card-body">
            <p class="text-muted">
                No topics are available for this subject yet.
                <?php if ($can_manage): ?>
                    <a href="/subjects/<?= (int) $subject['id'] ?>/topics/create">Create the first topic</a>.
                <?php endif; ?>
            </p>
        </div>
    </div>
<?php else: ?>
    <div class="topic-grid">
        <?php foreach ($topics as $topic): ?>
            <?php
            $thumbTone = ui_avatar_tone_class((string) (($topic['title'] ?? '') . '-' . ($topic['id'] ?? '')));
            ?>
            <article class="topic-card">
                <div class="topic-card-thumb <?= e($thumbTone) ?>">
                    <span class="topic-card-thumb-order">#<?= (int) $topic['sort_order'] ?></span>
                </div>
                <div class="topic-card-content">
                    <h3><?= e($topic['title']) ?></h3>
                    <?php if (!empty($topic['description'])): ?>
                        <p><?= e((string) $topic['description']) ?></p>
                    <?php else: ?>
                        <p class="text-muted">No description added yet.</p>
                    <?php endif; ?>
                    <div class="topic-card-meta">
                        <span class="badge">Created by <?= e($topic['creator_name'] ?? 'Unknown') ?></span>
                        <span class="badge"><?= e(date('Y-m-d', strtotime((string) $topic['updated_at']))) ?></span>
                    </div>
                    <?php if ($can_manage): ?>
                        <div class="topic-card-actions">
                            <a href="/subjects/<?= (int) $subject['id'] ?>/topics/<?= (int) $topic['id'] ?>/edit" class="table-icon-btn" title="Edit topic" aria-label="Edit topic">
                                <?= ui_lucide_icon('pencil') ?>
                            </a>
                            <form method="POST" action="/subjects/<?= (int) $subject['id'] ?>/topics/<?= (int) $topic['id'] ?>/delete" class="table-action-form" onsubmit="return confirm('Delete this topic?');">
                                <?= csrf_field() ?>
                                <button type="submit" class="table-icon-btn is-danger" title="Delete topic" aria-label="Delete topic">
                                    <?= ui_lucide_icon('trash-2') ?>
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
