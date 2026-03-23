<div class="page-header">
    <div class="page-header-content">
        <p class="page-breadcrumb"><?= $is_admin ? 'Admin / Students' : 'Moderator / Students' ?></p>
        <h1>Students</h1>
        <p class="page-subtitle">
            <?= $is_admin
                ? 'Manage student accounts and approved batch assignments.'
                : 'View your batch members and remove access when required.' ?>
        </p>
    </div>
    <div class="page-header-actions">
        <?php if ($is_admin): ?>
            <a href="/students/create" class="btn btn-primary">+ New Student</a>
        <?php elseif (!empty($moderator_batch['batch_code'])): ?>
            <span class="badge badge-info">Batch: <?= e($moderator_batch['batch_code']) ?></span>
        <?php endif; ?>
    </div>
</div>

<?php if (empty($students)): ?>
    <div class="card">
        <div class="card-body">
            <p class="text-muted">
                <?= $is_admin
                    ? 'No students found. Create the first student account.'
                    : 'No students are currently assigned to your batch.' ?>
            </p>
        </div>
    </div>
<?php else: ?>
    <div class="card">
        <div class="card-body no-padding">
            <table class="table">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Academic Year</th>
                        <th>University</th>
                        <th>Current Batch</th>
                        <th>Locked Batch</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $student): ?>
                        <?php
                        $avatarText = ui_initials((string) $student['name']);
                        $avatarTone = ui_avatar_tone_class((string) ($student['email'] ?? $student['name']));
                        ?>
                        <tr>
                            <td>
                                <div class="table-identity">
                                    <span class="table-avatar <?= e($avatarTone) ?>"><?= e($avatarText) ?></span>
                                    <div class="table-identity-text">
                                        <strong><?= e($student['name']) ?></strong>
                                        <?php if (($student['role'] ?? 'student') === 'coordinator'): ?>
                                            <span class="badge badge-info">Coordinator</span>
                                        <?php endif; ?>
                                        <br>
                                        <small class="text-muted"><?= e($student['email']) ?></small>
                                    </div>
                                </div>
                            </td>
                            <td><?= (int) ($student['academic_year'] ?? 0) ?></td>
                            <td><?= e($student['university_name'] ?? 'N/A') ?></td>
                            <td>
                                <?php if (!empty($student['batch_code'])): ?>
                                    <strong><?= e($student['batch_name'] ?? '-') ?></strong><br>
                                    <small class="text-muted"><?= e($student['batch_code']) ?></small>
                                <?php else: ?>
                                    <small class="text-muted">Not assigned</small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($student['locked_batch_code'])): ?>
                                    <strong><?= e($student['locked_batch_name'] ?? '-') ?></strong><br>
                                    <small class="text-muted"><?= e($student['locked_batch_code']) ?></small>
                                <?php else: ?>
                                    <small class="text-muted">Not locked</small>
                                <?php endif; ?>
                            </td>
                            <td class="actions">
                                <?php if ($is_admin): ?>
                                    <a href="/students/<?= (int) $student['id'] ?>/edit" class="table-icon-btn" title="Edit student" aria-label="Edit student">
                                        <?= ui_lucide_icon('pencil') ?>
                                    </a>
                                    <form method="POST" action="/students/<?= (int) $student['id'] ?>/delete" class="table-action-form" onsubmit="return confirm('Delete this student account?');">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="table-icon-btn is-danger" title="Delete student" aria-label="Delete student">
                                            <?= ui_lucide_icon('trash-2') ?>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <form method="POST" action="/students/<?= (int) $student['id'] ?>/remove" class="table-action-form" onsubmit="return confirm('Remove this student from your batch?');">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="table-icon-btn is-danger" title="Remove student from batch" aria-label="Remove student from batch">
                                            <?= ui_lucide_icon('trash-2') ?>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>
