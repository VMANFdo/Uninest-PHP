<div class="page-header">
    <div class="page-header-content">
        <p class="page-breadcrumb"><?= e($role_label) ?> / Subjects / Topics</p>
        <h1>Topics</h1>
        <p class="page-subtitle">
            Manage topics for <strong><?= e($subject['code']) ?> - <?= e($subject['name']) ?></strong>.
        </p>
    </div>
    <div class="page-header-actions">
        <a href="/subjects/<?= (int) $subject['id'] ?>/topics/create" class="btn btn-primary">+ New Topic</a>
        <a href="/dashboard/subjects/<?= (int) $subject['id'] ?>/topics" class="btn btn-outline">View Topic Cards</a>
        <a href="<?= e($back_subjects_url) ?>" class="btn btn-outline">← Back to Subjects</a>
    </div>
</div>

<?php if (empty($topics)): ?>
    <div class="card">
        <div class="card-body">
            <p class="text-muted">No topics found. <a href="/subjects/<?= (int) $subject['id'] ?>/topics/create">Create the first topic</a>.</p>
        </div>
    </div>
<?php else: ?>
    <div class="card">
        <div class="card-body no-padding">
            <table class="table">
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Topic</th>
                        <th>Created By</th>
                        <th>Updated</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($topics as $topic): ?>
                        <?php
                        $avatarText = ui_initials((string) $topic['title']);
                        $avatarTone = ui_avatar_tone_class((string) (($topic['title'] ?? '') . '-' . ($topic['id'] ?? '')));
                        ?>
                        <tr>
                            <td><span class="badge">#<?= (int) $topic['sort_order'] ?></span></td>
                            <td>
                                <div class="table-identity">
                                    <span class="table-avatar <?= e($avatarTone) ?>"><?= e($avatarText) ?></span>
                                    <div class="table-identity-text">
                                        <strong><?= e($topic['title']) ?></strong>
                                        <?php if (!empty($topic['description'])): ?>
                                            <br><small class="text-muted"><?= e(substr((string) $topic['description'], 0, 90)) ?><?= strlen((string) $topic['description']) > 90 ? '...' : '' ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td><?= e($topic['creator_name'] ?? 'Unknown') ?></td>
                            <td><?= e(date('Y-m-d H:i', strtotime((string) $topic['updated_at']))) ?></td>
                            <td class="actions">
                                <a href="/subjects/<?= (int) $subject['id'] ?>/topics/<?= (int) $topic['id'] ?>/edit" class="table-icon-btn" title="Edit topic" aria-label="Edit topic">
                                    <?= ui_lucide_icon('pencil') ?>
                                </a>
                                <form method="POST" action="/subjects/<?= (int) $subject['id'] ?>/topics/<?= (int) $topic['id'] ?>/delete" class="table-action-form" onsubmit="return confirm('Delete this topic?');">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="table-icon-btn is-danger" title="Delete topic" aria-label="Delete topic">
                                        <?= ui_lucide_icon('trash-2') ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>
