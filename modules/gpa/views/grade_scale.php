<?php
$isAdmin = !empty($is_admin);
$batchOptions = (array) ($batch_options ?? []);
$selectedBatchId = (int) ($selected_batch_id ?? 0);
$activeBatch = (array) ($active_batch ?? []);
$canManage = !empty($can_manage);
$isReadOnly = !empty($is_read_only);
$scaleRows = (array) ($scale_rows ?? []);
$metrics = (array) ($metrics ?? []);

$totalGrades = (int) ($metrics['total_grades'] ?? count($scaleRows));
$highestPoint = $metrics['highest_grade_point'] !== null ? (float) $metrics['highest_grade_point'] : null;
$lowestPoint = $metrics['lowest_grade_point'] !== null ? (float) $metrics['lowest_grade_point'] : null;
?>

<div class="page-header gpa-page-header">
    <div class="page-header-content">
        <p class="page-breadcrumb"><?= e((string) ($role_label ?? 'User')) ?> / GPA / Grade Scale</p>
        <h1>Grade Point Configuration</h1>
        <p class="page-subtitle">
            Configure letter grades and corresponding grade points used by the GPA calculator.
            <?php if (!empty($activeBatch['batch_code'])): ?>
                Active batch: <strong><?= e((string) $activeBatch['batch_code']) ?></strong>.
            <?php endif; ?>
        </p>
    </div>
    <div class="page-header-actions">
        <a href="/dashboard/gpa" class="btn btn-outline"><?= ui_lucide_icon('calculator') ?> Open GPA Calculator</a>
        <a href="/dashboard/gpa/analytics" class="btn btn-outline"><?= ui_lucide_icon('line-chart') ?> GPA Analytics</a>
    </div>
</div>

<?php if ($isAdmin && $selectedBatchId <= 0): ?>
    <section class="community-admin-gate">
        <h3>Select Batch to Manage Grade Scale</h3>
        <p class="text-muted">Choose an approved batch before viewing or editing its grade-point configuration.</p>
        <form method="GET" action="/dashboard/gpa/grade-scale" class="community-topbar-form">
            <div class="form-group">
                <label for="batch_id">Batch</label>
                <select id="batch_id" name="batch_id" required>
                    <option value="">Select a batch</option>
                    <?php foreach ($batchOptions as $batch): ?>
                        <?php $batchId = (int) ($batch['id'] ?? 0); ?>
                        <option value="<?= $batchId ?>">
                            <?= e((string) ($batch['batch_code'] ?? 'BATCH')) ?> — <?= e((string) ($batch['name'] ?? '')) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Open Configuration</button>
        </form>
    </section>
<?php else: ?>
    <section class="gpa-analytics-kpis">
        <article class="card gpa-kpi-card">
            <div class="card-body">
                <span>Total Grades</span>
                <strong><?= $totalGrades ?></strong>
            </div>
        </article>
        <article class="card gpa-kpi-card">
            <div class="card-body">
                <span>Highest Grade Point</span>
                <strong><?= $highestPoint !== null ? e(number_format($highestPoint, 2)) : '-' ?></strong>
            </div>
        </article>
        <article class="card gpa-kpi-card">
            <div class="card-body">
                <span>Lowest Grade Point</span>
                <strong><?= $lowestPoint !== null ? e(number_format($lowestPoint, 2)) : '-' ?></strong>
            </div>
        </article>
    </section>

    <?php if ($isReadOnly): ?>
        <article class="gpa-warning-banner">
            <span><?= ui_lucide_icon('shield') ?></span>
            <div>
                <strong>Read-only mode.</strong>
                <p class="text-muted">Moderators and admins can update grade scale rows. Students and coordinators can view this configuration.</p>
            </div>
        </article>
    <?php endif; ?>

    <section class="card gpa-grade-scale-card">
        <div class="card-body">
            <h2>Grade Scale Configuration</h2>

            <?php if (empty($scaleRows)): ?>
                <article class="gpa-empty-state-inline">
                    <h3><?= ui_lucide_icon('circle-alert') ?> No grade rows configured</h3>
                    <p class="text-muted">Add letter-grade rows to enable GPA calculations for this batch.</p>
                </article>
            <?php else: ?>
                <div class="gpa-grade-table-wrap">
                    <table class="gpa-grade-config-table">
                        <thead>
                            <tr>
                                <th>Letter Grade</th>
                                <th>Description</th>
                                <th>Grade Point</th>
                                <th>Sort</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($scaleRows as $row): ?>
                                <?php $rowId = (int) ($row['id'] ?? 0); ?>
                                <?php $updateFormId = 'gpa-scale-update-' . $rowId; ?>
                                <tr>
                                    <td>
                                        <?php if ($canManage): ?>
                                                <input type="text" name="letter_grade" maxlength="12" value="<?= e((string) ($row['letter_grade'] ?? '')) ?>" required form="<?= e($updateFormId) ?>">
                                    </td>
                                    <td>
                                                <input type="text" name="description" maxlength="120" value="<?= e((string) ($row['description'] ?? '')) ?>" placeholder="e.g., Excellent" form="<?= e($updateFormId) ?>">
                                    </td>
                                    <td>
                                                <input type="number" name="grade_point" min="0" max="4" step="0.01" value="<?= e(number_format((float) ($row['grade_point'] ?? 0), 2, '.', '')) ?>" required form="<?= e($updateFormId) ?>">
                                    </td>
                                    <td>
                                                <input type="number" name="sort_order" min="1" step="1" value="<?= (int) ($row['sort_order'] ?? 1) ?>" required form="<?= e($updateFormId) ?>">
                                    </td>
                                    <td>
                                                <div class="gpa-inline-actions">
                                                    <form id="<?= e($updateFormId) ?>" method="POST" action="/dashboard/gpa/grade-scale/<?= $rowId ?>">
                                                        <?= csrf_field() ?>
                                                        <?php if ($isAdmin): ?>
                                                            <input type="hidden" name="batch_id" value="<?= $selectedBatchId ?>">
                                                        <?php endif; ?>
                                                        <button type="submit" class="btn btn-outline btn-sm"><?= ui_lucide_icon('save') ?> Update</button>
                                                    </form>
                                                    <form method="POST" action="/dashboard/gpa/grade-scale/<?= $rowId ?>/delete" onsubmit="return confirm('Delete this grade row?');">
                                                        <?= csrf_field() ?>
                                                        <?php if ($isAdmin): ?>
                                                            <input type="hidden" name="batch_id" value="<?= $selectedBatchId ?>">
                                                        <?php endif; ?>
                                                        <button type="submit" class="btn btn-outline btn-sm text-danger"><?= ui_lucide_icon('trash-2') ?> Delete</button>
                                                    </form>
                                                </div>
                                    </td>
                                <?php else: ?>
                                    <td><span class="gpa-grade-pill"><?= e((string) ($row['letter_grade'] ?? '')) ?></span></td>
                                    <td><?= e((string) ($row['description'] ?? '-')) ?></td>
                                    <td><?= e(number_format((float) ($row['grade_point'] ?? 0), 2)) ?></td>
                                    <td><?= (int) ($row['sort_order'] ?? 1) ?></td>
                                    <td><span class="text-muted">View only</span></td>
                                <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <?php if ($canManage): ?>
                <div class="gpa-grade-add-wrap">
                    <h3>Add Grade Row</h3>
                    <form method="POST" action="/dashboard/gpa/grade-scale" class="gpa-grade-add-form">
                        <?= csrf_field() ?>
                        <?php if ($isAdmin): ?>
                            <input type="hidden" name="batch_id" value="<?= $selectedBatchId ?>">
                        <?php endif; ?>

                        <div class="form-group">
                            <label for="letter_grade">Letter</label>
                            <input id="letter_grade" type="text" name="letter_grade" maxlength="12" value="<?= e(old('letter_grade')) ?>" placeholder="A+" required>
                        </div>

                        <div class="form-group">
                            <label for="description">Description</label>
                            <input id="description" type="text" name="description" maxlength="120" value="<?= e(old('description')) ?>" placeholder="Excellent">
                        </div>

                        <div class="form-group">
                            <label for="grade_point">Grade Point</label>
                            <input id="grade_point" type="number" name="grade_point" min="0" max="4" step="0.01" value="<?= e(old('grade_point')) ?>" placeholder="4.00" required>
                        </div>

                        <div class="form-group">
                            <label for="sort_order">Sort Order</label>
                            <input id="sort_order" type="number" name="sort_order" min="1" step="1" value="<?= e(old('sort_order')) ?>" placeholder="Auto">
                        </div>

                        <div class="gpa-term-filter-actions">
                            <button type="submit" class="btn btn-primary"><?= ui_lucide_icon('plus') ?> Add Grade</button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </section>
<?php endif; ?>
