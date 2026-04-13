<?php
$isAdmin = !empty($is_admin);
$batchOptions = (array) ($batch_options ?? []);
$selectedBatchId = (int) ($selected_batch_id ?? 0);
$activeBatch = (array) ($active_batch ?? []);
$canManage = !empty($can_manage);
$slots = (array) ($slots ?? []);
$editSlot = (array) ($edit_slot ?? []);
$dayLabels = (array) ($day_labels ?? kuppi_timetable_day_labels());
$gridRows = (array) ($grid_rows ?? []);
$metrics = (array) ($metrics ?? []);

$isEditing = !empty($editSlot);
$blockedSlotCount = (int) ($metrics['blocked_slot_count'] ?? count($slots));
$totalBlockedHours = (float) ($metrics['total_blocked_hours'] ?? 0);
$availableSlotCount = (int) ($metrics['available_slot_count'] ?? 0);
$blockedHoursLabel = abs($totalBlockedHours - round($totalBlockedHours)) < 0.01
    ? (string) ((int) round($totalBlockedHours))
    : number_format($totalBlockedHours, 1);

$formDayOfWeek = (int) old('day_of_week', (string) ($editSlot['day_of_week'] ?? ''));
$formStartTime = (string) old('start_time', kuppi_timetable_time_label((string) ($editSlot['start_time'] ?? '')));
$formEndTime = (string) old('end_time', kuppi_timetable_time_label((string) ($editSlot['end_time'] ?? '')));
$formReason = (string) old('reason', (string) ($editSlot['reason'] ?? ''));

$baseUrl = '/dashboard/kuppi/timetable' . ($isAdmin && $selectedBatchId > 0 ? ('?batch_id=' . $selectedBatchId) : '');
$editActionUrl = '/dashboard/kuppi/timetable/' . (int) ($editSlot['id'] ?? 0);
?>

<div class="page-header kuppi-scheduled-page-header">
    <div class="page-header-content">
        <p class="page-breadcrumb">Dashboard / Kuppi Sessions / University Timetable</p>
        <h1>University Lecture Timetable</h1>
        <p class="page-subtitle">
            Define official lecture slots to prevent Kuppi scheduling conflicts.
            <?php if (!empty($activeBatch['batch_code'])): ?>
                Active batch: <strong><?= e((string) $activeBatch['batch_code']) ?></strong>.
            <?php endif; ?>
        </p>
    </div>
    <div class="page-header-actions">
        <a href="/dashboard/kuppi/schedule<?= $isAdmin && $selectedBatchId > 0 ? '?batch_id=' . $selectedBatchId : '' ?>" class="btn btn-primary"><?= ui_lucide_icon('calendar-plus') ?> Schedule Session</a>
        <a href="/dashboard/kuppi/scheduled<?= $isAdmin && $selectedBatchId > 0 ? '?batch_id=' . $selectedBatchId : '' ?>" class="btn btn-outline">Scheduled Sessions</a>
        <a href="/dashboard/kuppi" class="btn btn-outline">Requested Sessions</a>
    </div>
</div>

<?php if ($isAdmin && $selectedBatchId <= 0): ?>
    <section class="community-admin-gate">
        <h3>Select Batch to Open Timetable</h3>
        <p class="text-muted">Pick an approved batch before managing or viewing official lecture slots.</p>
        <form method="GET" action="/dashboard/kuppi/timetable" class="community-topbar-form">
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
            <button type="submit" class="btn btn-primary">Open Timetable</button>
        </form>
    </section>
<?php else: ?>
    <section class="kuppi-timetable-kpis">
        <article class="kuppi-timetable-kpi">
            <span class="kuppi-timetable-kpi-icon"><?= ui_lucide_icon('calendar-x-2') ?></span>
            <div>
                <p>Blocked Slots</p>
                <strong><?= $blockedSlotCount ?></strong>
            </div>
        </article>
        <article class="kuppi-timetable-kpi">
            <span class="kuppi-timetable-kpi-icon"><?= ui_lucide_icon('clock-3') ?></span>
            <div>
                <p>Total Hours</p>
                <strong><?= e($blockedHoursLabel) ?></strong>
            </div>
        </article>
        <article class="kuppi-timetable-kpi">
            <span class="kuppi-timetable-kpi-icon"><?= ui_lucide_icon('calendar-check-2') ?></span>
            <div>
                <p>Available Slots</p>
                <strong><?= $availableSlotCount ?></strong>
            </div>
        </article>
    </section>

    <article class="kuppi-timetable-info">
        <h3><?= ui_lucide_icon('info') ?> How It Works</h3>
        <p>
            Official lecture slots are recurring weekly blocks. Kuppi session scheduling is hard-blocked when selected
            date/time overlaps these slots.
        </p>
    </article>

    <?php if ($canManage): ?>
        <section class="card kuppi-timetable-form-card">
            <div class="card-body">
                <h2><?= $isEditing ? 'Edit Blocked Time Slot' : 'Add Blocked Time Slot' ?></h2>
                <p class="kuppi-wizard-muted">
                    <?= $isEditing ? 'Update this official lecture slot.' : 'Define a weekly lecture period when Kuppi sessions are unavailable.' ?>
                </p>

                <form method="POST" action="<?= $isEditing ? e($editActionUrl) : '/dashboard/kuppi/timetable' ?>" class="kuppi-timetable-form-grid">
                    <?= csrf_field() ?>
                    <?php if ($isAdmin): ?>
                        <input type="hidden" name="batch_id" value="<?= $selectedBatchId ?>">
                    <?php endif; ?>

                    <div class="form-group">
                        <label for="day_of_week">Day of Week *</label>
                        <select id="day_of_week" name="day_of_week" required>
                            <option value="">Select day</option>
                            <?php foreach ($dayLabels as $dayValue => $dayLabel): ?>
                                <option value="<?= (int) $dayValue ?>" <?= $formDayOfWeek === (int) $dayValue ? 'selected' : '' ?>>
                                    <?= e((string) $dayLabel) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="start_time">Start Time *</label>
                        <input type="time" id="start_time" name="start_time" required value="<?= e($formStartTime) ?>">
                    </div>

                    <div class="form-group">
                        <label for="end_time">End Time *</label>
                        <input type="time" id="end_time" name="end_time" required value="<?= e($formEndTime) ?>">
                    </div>

                    <div class="form-group form-group-span-2">
                        <label for="reason">Reason (Optional)</label>
                        <input type="text" id="reason" name="reason" maxlength="255" value="<?= e($formReason) ?>" placeholder="e.g., CS101 Lecture">
                    </div>

                    <div class="kuppi-wizard-actions form-group-span-2">
                        <?php if ($isEditing): ?>
                            <a href="<?= e($baseUrl) ?>" class="btn btn-outline">Cancel Edit</a>
                        <?php endif; ?>
                        <button type="submit" class="btn btn-primary kuppi-wizard-cta">
                            <?= ui_lucide_icon($isEditing ? 'pencil' : 'plus') ?>
                            <?= $isEditing ? 'Update Slot' : 'Add Blocked Slot' ?>
                        </button>
                    </div>
                </form>
            </div>
        </section>
    <?php else: ?>
        <article class="kuppi-timetable-readonly-note">
            <span><?= ui_lucide_icon('shield-alert') ?></span>
            <p>Read-only mode: moderators and admins can update official timetable slots.</p>
        </article>
    <?php endif; ?>

    <section class="card kuppi-timetable-grid-card">
        <div class="card-body">
            <h2>Weekly Timetable Overview</h2>
            <p class="kuppi-wizard-muted">08:00–21:00 view in 1-hour cells (Mon–Sun).</p>

            <div class="kuppi-timetable-grid-wrap">
                <table class="kuppi-timetable-grid" aria-label="Weekly blocked timetable grid">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <?php foreach ($dayLabels as $dayLabel): ?>
                                <th><?= e((string) $dayLabel) ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($gridRows as $row): ?>
                            <tr>
                                <th><?= e((string) ($row['time_label'] ?? '')) ?></th>
                                <?php foreach ($dayLabels as $dayValue => $dayLabel): ?>
                                    <?php $cellSlot = $row['cells'][$dayValue] ?? null; ?>
                                    <?php if (is_array($cellSlot)): ?>
                                        <td class="is-blocked">
                                            <strong>Blocked</strong>
                                            <small><?= e(kuppi_timetable_reason_label($cellSlot)) ?></small>
                                        </td>
                                    <?php else: ?>
                                        <td class="is-free"><span>—</span></td>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <section class="card kuppi-timetable-slots-card">
        <div class="card-body">
            <h2>Blocked Time Slots</h2>
            <p class="kuppi-wizard-muted">Manage all recurring lecture blocks for this batch.</p>

            <?php if (empty($slots)): ?>
                <article class="community-post-card community-empty-state">
                    <h3>No official slots added yet</h3>
                    <p class="text-muted">Add your first lecture block to prevent scheduling conflicts.</p>
                </article>
            <?php else: ?>
                <div class="kuppi-timetable-slot-list">
                    <?php foreach ($slots as $slot): ?>
                        <?php
                        $slotId = (int) ($slot['id'] ?? 0);
                        $dayLabel = kuppi_timetable_day_label((int) ($slot['day_of_week'] ?? 0));
                        $startLabel = kuppi_timetable_time_label((string) ($slot['start_time'] ?? ''));
                        $endLabel = kuppi_timetable_time_label((string) ($slot['end_time'] ?? ''));
                        $reasonLabel = kuppi_timetable_reason_label($slot);
                        $slotEditUrl = '/dashboard/kuppi/timetable?edit=' . $slotId . ($isAdmin && $selectedBatchId > 0 ? '&batch_id=' . $selectedBatchId : '');
                        ?>
                        <article class="kuppi-timetable-slot-item">
                            <span class="kuppi-timetable-slot-icon"><?= ui_lucide_icon('calendar-x-2') ?></span>
                            <div class="kuppi-timetable-slot-main">
                                <h3><?= e($reasonLabel) ?></h3>
                                <p><?= e($dayLabel) ?> • <?= e($startLabel) ?> - <?= e($endLabel) ?></p>
                            </div>

                            <?php if ($canManage): ?>
                                <div class="kuppi-timetable-slot-actions">
                                    <a href="<?= e($slotEditUrl) ?>" class="btn btn-outline"><?= ui_lucide_icon('pencil') ?> Edit</a>
                                    <form method="POST" action="/dashboard/kuppi/timetable/<?= $slotId ?>/delete" onsubmit="return confirm('Delete this blocked slot?');">
                                        <?= csrf_field() ?>
                                        <?php if ($isAdmin): ?>
                                            <input type="hidden" name="batch_id" value="<?= $selectedBatchId ?>">
                                        <?php endif; ?>
                                        <button type="submit" class="btn btn-outline text-danger"><?= ui_lucide_icon('trash-2') ?> Delete</button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>
<?php endif; ?>
