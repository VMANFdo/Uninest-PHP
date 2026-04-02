<div class="page-header">
    <div class="page-header-content">
        <p class="page-breadcrumb">Resources / My Resources</p>
        <h1>My Resources</h1>
        <p class="page-subtitle">Manage all resources you have uploaded in one place.</p>
    </div>
    <div class="page-header-actions">
        <a href="/dashboard" class="btn btn-outline"><?= ui_lucide_icon('arrow-left') ?> Back to Dashboard</a>
    </div>
</div>

<?php if (empty($resources)): ?>
    <div class="card">
        <div class="card-body">
            <p class="text-muted">You have not uploaded any resources yet.</p>
        </div>
    </div>
<?php else: ?>
    <div class="card">
        <div class="card-body no-padding">
            <table class="table">
                <thead>
                    <tr>
                        <th>Resource</th>
                        <th>Status</th>
                        <th>Source</th>
                        <th>Topic</th>
                        <th>Updated</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($resources as $resource): ?>
                        <?php
                        $avatarText = ui_initials((string) $resource['title']);
                        $avatarTone = ui_avatar_tone_class((string) (($resource['title'] ?? '') . '-' . ($resource['id'] ?? '')));
                        $resourceStatus = (string) ($resource['status'] ?? 'pending');
                        $updateStatus = trim((string) ($resource['update_request_status'] ?? ''));
                        ?>
                        <tr>
                            <td>
                                <div class="table-identity">
                                    <span class="table-avatar <?= e($avatarTone) ?>"><?= e($avatarText) ?></span>
                                    <div class="table-identity-text">
                                        <strong><?= e($resource['title']) ?></strong>
                                        <?php if (!empty($resource['description'])): ?>
                                            <br><small class="text-muted"><?= e(substr((string) $resource['description'], 0, 80)) ?><?= strlen((string) $resource['description']) > 80 ? '...' : '' ?></small>
                                        <?php endif; ?>
                                        <?php if (!empty($resource['rejection_reason']) && $resourceStatus === 'rejected'): ?>
                                            <br><small class="text-muted">Rejected: <?= e((string) $resource['rejection_reason']) ?></small>
                                        <?php endif; ?>
                                        <?php if ($updateStatus === 'rejected' && !empty($resource['update_request_rejection_reason'])): ?>
                                            <br><small class="text-muted">Update rejected: <?= e((string) $resource['update_request_rejection_reason']) ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge <?= e(resources_status_badge_class($resourceStatus)) ?>"><?= e(resources_status_label($resourceStatus)) ?></span>
                                <?php if ($updateStatus !== ''): ?>
                                    <br>
                                    <span class="badge <?= e(resources_update_status_badge_class($updateStatus)) ?>"><?= e(resources_update_status_label($updateStatus)) ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge"><?= e(resources_source_label((string) $resource['source_type'])) ?></span>
                                <br>
                                <small class="text-muted"><?= e(resources_category_display((string) $resource['category'], (string) ($resource['category_other'] ?? ''))) ?></small>
                                <?php if (($resource['source_type'] ?? '') === 'link' && !empty($resource['external_url'])): ?>
                                    <br><a href="<?= e((string) $resource['external_url']) ?>" target="_blank" rel="noopener">Open Link</a>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?= e($resource['subject_code']) ?></strong><br>
                                <small class="text-muted"><?= e($resource['topic_title']) ?></small><br>
                                <a href="/dashboard/subjects/<?= (int) $resource['subject_id'] ?>/topics/<?= (int) $resource['topic_id'] ?>/resources" class="text-muted">Open topic resources</a>
                            </td>
                            <td><?= e(date('Y-m-d H:i', strtotime((string) $resource['updated_at']))) ?></td>
                            <td class="actions">
                                <a href="/my-resources/<?= (int) $resource['id'] ?>/edit" class="table-icon-btn" title="Edit resource" aria-label="Edit resource">
                                    <?= ui_lucide_icon('pencil') ?>
                                </a>
                                <?php if (($resource['source_type'] ?? '') === 'file' && !empty($resource['file_path'])): ?>
                                    <a href="/resources/<?= (int) $resource['id'] ?>/download" class="table-icon-btn" title="Download file" aria-label="Download file">
                                        <?= ui_lucide_icon('download') ?>
                                    </a>
                                <?php endif; ?>
                                <form method="POST" action="/my-resources/<?= (int) $resource['id'] ?>/delete" class="table-action-form" onsubmit="return confirm('Delete this resource?');">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="table-icon-btn is-danger" title="Delete resource" aria-label="Delete resource">
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
