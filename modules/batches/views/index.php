<div class="page-header">
    <div class="page-header-content">
        <p class="page-breadcrumb">Admin / Batches</p>
        <h1>Batches</h1>
        <p class="page-subtitle">View and manage all batches, moderator ownership, and lifecycle status from one place.</p>
    </div>
    <div class="page-header-actions">
        <a href="/admin/batches/create" class="btn btn-primary">+ New Batch</a>
    </div>
</div>

<?php if (empty($batches)): ?>
    <div class="card">
        <div class="card-body">
            <p class="text-muted">No batches found. Create the first batch to start managing moderators and students.</p>
        </div>
    </div>
<?php else: ?>
    <div class="card">
        <div class="card-body no-padding">
            <table class="table">
                <thead>
                    <tr>
                        <th>Batch</th>
                        <th>University</th>
                        <th>Primary Moderator</th>
                        <th>Status</th>
                        <th>Members</th>
                        <th>Updated</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($batches as $batch): ?>
                        <?php
                        $avatarText = ui_initials((string) $batch['name']);
                        $avatarTone = ui_avatar_tone_class((string) ($batch['batch_code'] ?? $batch['name']));
                        $status = (string) ($batch['status'] ?? 'pending');
                        $statusClass = 'badge-info';
                        if ($status === 'pending') {
                            $statusClass = 'badge-warning';
                        } elseif ($status === 'rejected') {
                            $statusClass = 'badge-danger';
                        } elseif ($status === 'inactive') {
                            $statusClass = 'badge-warning';
                        }
                        ?>
                        <tr>
                            <td>
                                <div class="table-identity">
                                    <span class="table-avatar <?= e($avatarTone) ?>"><?= e($avatarText) ?></span>
                                    <div class="table-identity-text">
                                        <strong><?= e($batch['name']) ?></strong><br>
                                        <small class="text-muted"><?= e($batch['batch_code'] ?? 'No Batch ID') ?></small>
                                    </div>
                                </div>
                            </td>
                            <td><?= e($batch['university_name'] ?? '-') ?></td>
                            <td>
                                <strong><?= e($batch['primary_moderator_name'] ?? '-') ?></strong><br>
                                <small class="text-muted"><?= e($batch['primary_moderator_email'] ?? 'N/A') ?></small>
                            </td>
                            <td><span class="badge <?= e($statusClass) ?>"><?= e(ucfirst($status)) ?></span></td>
                            <td>
                                <small class="text-muted">
                                    Mods: <?= (int) ($batch['moderators_count'] ?? 0) ?>,
                                    Students: <?= (int) ($batch['students_count'] ?? 0) ?>,
                                    Subjects: <?= (int) ($batch['subjects_count'] ?? 0) ?>
                                </small>
                            </td>
                            <td><?= e(date('Y-m-d', strtotime((string) ($batch['updated_at'] ?? 'now')))) ?></td>
                            <td class="actions">
                                <a href="/admin/batches/<?= (int) $batch['id'] ?>/edit" class="table-icon-btn" title="Edit batch" aria-label="Edit batch">
                                    <?= ui_lucide_icon('pencil') ?>
                                </a>
                                <form method="POST" action="/admin/batches/<?= (int) $batch['id'] ?>/delete" class="table-action-form" onsubmit="return confirm('Delete this batch? This will unassign users and remove related subjects and requests.');">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="table-icon-btn is-danger" title="Delete batch" aria-label="Delete batch">
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
