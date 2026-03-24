<div class="page-header">
    <div class="page-header-content">
        <p class="page-breadcrumb">Coordinator / Resource Requests</p>
        <h1>Resource Approval Queue</h1>
        <p class="page-subtitle">Review pending student resource submissions and update requests.</p>
    </div>
    <div class="page-header-actions">
        <span class="badge badge-warning"><?= (int) $pending_count ?> Pending</span>
    </div>
</div>

<?php if (empty($create_requests) && empty($update_requests)): ?>
    <div class="card">
        <div class="card-body">
            <p class="text-muted">No pending resource requests for your assigned subjects.</p>
        </div>
    </div>
<?php endif; ?>

<?php if (!empty($create_requests)): ?>
    <div class="card" style="margin-bottom: 1rem;">
        <div class="card-body no-padding">
            <table class="table">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Subject / Topic</th>
                        <th>Resource</th>
                        <th>Source</th>
                        <th>Submitted</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($create_requests as $request): ?>
                        <?php
                        $avatarText = ui_initials((string) $request['uploader_name']);
                        $avatarTone = ui_avatar_tone_class((string) (($request['uploader_email'] ?? '') . '-' . ($request['resource_id'] ?? '')));
                        ?>
                        <tr>
                            <td>
                                <div class="table-identity">
                                    <span class="table-avatar <?= e($avatarTone) ?>"><?= e($avatarText) ?></span>
                                    <div class="table-identity-text">
                                        <strong><?= e($request['uploader_name']) ?></strong><br>
                                        <small class="text-muted"><?= e($request['uploader_email']) ?></small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <strong><?= e($request['subject_code']) ?> - <?= e($request['subject_name']) ?></strong><br>
                                <small class="text-muted"><?= e($request['topic_title']) ?></small>
                            </td>
                            <td>
                                <strong><?= e($request['title']) ?></strong><br>
                                <small class="text-muted"><?= e(resources_category_display((string) $request['category'], (string) ($request['category_other'] ?? ''))) ?></small>
                            </td>
                            <td>
                                <span class="badge"><?= e(resources_source_label((string) $request['source_type'])) ?></span>
                                <?php if (($request['source_type'] ?? '') === 'file'): ?>
                                    <?php if (!empty($request['file_name'])): ?>
                                        <br><small class="text-muted"><?= e((string) $request['file_name']) ?></small>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <br><a href="<?= e((string) $request['external_url']) ?>" target="_blank" rel="noopener">Open Link</a>
                                <?php endif; ?>
                            </td>
                            <td><?= e(date('Y-m-d H:i', strtotime((string) $request['created_at']))) ?></td>
                            <td class="actions">
                                <form method="POST" action="/coordinator/resource-requests/create/<?= (int) $request['resource_id'] ?>/approve" class="table-action-form">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="table-icon-btn is-success" title="Approve request" aria-label="Approve request">
                                        <?= ui_lucide_icon('check') ?>
                                    </button>
                                </form>
                                <form method="POST" action="/coordinator/resource-requests/create/<?= (int) $request['resource_id'] ?>/reject" class="table-action-form">
                                    <?= csrf_field() ?>
                                    <input type="text" name="rejection_reason" placeholder="Rejection reason" class="table-action-form-input" required>
                                    <button type="submit" class="table-icon-btn is-danger" title="Reject request" aria-label="Reject request">
                                        <?= ui_lucide_icon('x') ?>
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

<?php if (!empty($update_requests)): ?>
    <div class="card">
        <div class="card-body no-padding">
            <table class="table">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Subject / Topic</th>
                        <th>Current</th>
                        <th>Requested Update</th>
                        <th>Submitted</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($update_requests as $request): ?>
                        <?php
                        $avatarText = ui_initials((string) $request['requester_name']);
                        $avatarTone = ui_avatar_tone_class((string) (($request['requester_email'] ?? '') . '-' . ($request['update_request_id'] ?? '')));
                        ?>
                        <tr>
                            <td>
                                <div class="table-identity">
                                    <span class="table-avatar <?= e($avatarTone) ?>"><?= e($avatarText) ?></span>
                                    <div class="table-identity-text">
                                        <strong><?= e($request['requester_name']) ?></strong><br>
                                        <small class="text-muted"><?= e($request['requester_email']) ?></small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <strong><?= e($request['subject_code']) ?> - <?= e($request['subject_name']) ?></strong><br>
                                <small class="text-muted"><?= e($request['topic_title']) ?></small>
                            </td>
                            <td>
                                <strong><?= e($request['current_title']) ?></strong><br>
                                <small class="text-muted"><?= e(resources_category_display((string) $request['current_category'], (string) ($request['current_category_other'] ?? ''))) ?></small><br>
                                <small class="text-muted"><?= e(resources_source_label((string) $request['current_source_type'])) ?></small>
                            </td>
                            <td>
                                <strong><?= e($request['title']) ?></strong><br>
                                <small class="text-muted"><?= e(resources_category_display((string) $request['category'], (string) ($request['category_other'] ?? ''))) ?></small><br>
                                <small class="text-muted"><?= e(resources_source_label((string) $request['source_type'])) ?></small>
                            </td>
                            <td><?= e(date('Y-m-d H:i', strtotime((string) $request['updated_at']))) ?></td>
                            <td class="actions">
                                <form method="POST" action="/coordinator/resource-requests/update/<?= (int) $request['update_request_id'] ?>/approve" class="table-action-form">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="table-icon-btn is-success" title="Approve update" aria-label="Approve update">
                                        <?= ui_lucide_icon('check') ?>
                                    </button>
                                </form>
                                <form method="POST" action="/coordinator/resource-requests/update/<?= (int) $request['update_request_id'] ?>/reject" class="table-action-form">
                                    <?= csrf_field() ?>
                                    <input type="text" name="rejection_reason" placeholder="Rejection reason" class="table-action-form-input" required>
                                    <button type="submit" class="table-icon-btn is-danger" title="Reject update" aria-label="Reject update">
                                        <?= ui_lucide_icon('x') ?>
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
