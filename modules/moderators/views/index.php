<div class="page-header">
    <div class="page-header-content">
        <p class="page-breadcrumb">Admin / Moderators</p>
        <h1>Moderators</h1>
        <p class="page-subtitle">Manage moderator accounts, batch assignments, and ownership responsibilities.</p>
    </div>
    <div class="page-header-actions">
        <a href="/admin/moderators/create" class="btn btn-primary">+ New Moderator</a>
    </div>
</div>

<?php if (empty($moderators)): ?>
    <div class="card">
        <div class="card-body">
            <p class="text-muted">No moderators found. Create the first moderator account.</p>
        </div>
    </div>
<?php else: ?>
    <div class="card">
        <div class="card-body no-padding">
            <table class="table">
                <thead>
                    <tr>
                        <th>Moderator</th>
                        <th>Year</th>
                        <th>University</th>
                        <th>Assigned Batch</th>
                        <th>Primary Owner Of</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($moderators as $moderator): ?>
                        <?php
                        $avatarText = ui_initials((string) $moderator['name']);
                        $avatarTone = ui_avatar_tone_class((string) ($moderator['email'] ?? $moderator['name']));
                        $ownsBatch = (int) ($moderator['owned_batch_id'] ?? 0) > 0;
                        ?>
                        <tr>
                            <td>
                                <div class="table-identity">
                                    <span class="table-avatar <?= e($avatarTone) ?>"><?= e($avatarText) ?></span>
                                    <div class="table-identity-text">
                                        <strong><?= e($moderator['name']) ?></strong><br>
                                        <small class="text-muted"><?= e($moderator['email']) ?></small>
                                    </div>
                                </div>
                            </td>
                            <td><?= (int) ($moderator['academic_year'] ?? 0) ?></td>
                            <td><?= e($moderator['university_name'] ?? '-') ?></td>
                            <td>
                                <?php if (!empty($moderator['assigned_batch_code'])): ?>
                                    <strong><?= e($moderator['assigned_batch_name'] ?? '-') ?></strong><br>
                                    <small class="text-muted"><?= e($moderator['assigned_batch_code']) ?></small>
                                <?php else: ?>
                                    <small class="text-muted">Not assigned</small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($ownsBatch): ?>
                                    <strong><?= e($moderator['owned_batch_name'] ?? '-') ?></strong><br>
                                    <small class="text-muted"><?= e($moderator['owned_batch_code'] ?? '') ?></small>
                                <?php else: ?>
                                    <small class="text-muted">None</small>
                                <?php endif; ?>
                            </td>
                            <td class="actions">
                                <a href="/admin/moderators/<?= (int) $moderator['id'] ?>/edit" class="table-icon-btn" title="Edit moderator" aria-label="Edit moderator">
                                    <?= ui_lucide_icon('pencil') ?>
                                </a>

                                <?php if (!$ownsBatch): ?>
                                    <form method="POST" action="/admin/moderators/<?= (int) $moderator['id'] ?>/delete" class="table-action-form" onsubmit="return confirm('Delete this moderator account?');">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="table-icon-btn is-danger" title="Delete moderator" aria-label="Delete moderator">
                                            <?= ui_lucide_icon('trash-2') ?>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span class="badge badge-info">Owner Locked</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>
