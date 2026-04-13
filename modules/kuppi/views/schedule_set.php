<?php
$draft = (array) ($draft ?? []);
$mode = (string) ($mode ?? ($draft['mode'] ?? 'request'));
$linkedRequest = (array) ($linked_request ?? []);
$selectedHosts = (array) ($selected_hosts ?? []);
$availabilityStats = (array) ($availability_stats ?? []);
$selectedSlotKey = (string) ($selected_slot_key ?? '');
$selectedSlotMatch = (array) ($selected_slot_match ?? []);
$availabilityOptions = (array) ($availability_options ?? []);
$subjectOptions = (array) ($subject_options ?? []);
$isAdmin = !empty($is_admin);
$batchOptions = (array) ($batch_options ?? []);

$selectedBatchId = (int) ($draft['batch_id'] ?? 0);
$selectedSubjectId = (int) ($draft['subject_id'] ?? 0);
$selectedLocationType = (string) ($draft['location_type'] ?? 'physical');
if (!in_array($selectedLocationType, ['physical', 'online'], true)) {
    $selectedLocationType = 'physical';
}

$sessionDate = (string) ($draft['session_date'] ?? '');
$startTime = (string) ($draft['start_time'] ?? '');
$endTime = (string) ($draft['end_time'] ?? '');
$maxAttendees = max(1, (int) ($draft['max_attendees'] ?? 25));
$locationText = (string) ($draft['location_text'] ?? '');
$meetingLink = (string) ($draft['meeting_link'] ?? '');
$notes = (string) ($draft['notes'] ?? '');
$durationMinutes = max(0, (int) ($draft['duration_minutes'] ?? 0));
$recommendedSlots = array_values((array) ($availabilityStats['recommended_slots'] ?? []));
$rankedSlotCounts = (array) ($availabilityStats['ranked_counts'] ?? []);
$hostsWithAvailability = (int) ($availabilityStats['hosts_with_availability'] ?? 0);
$timetableSlots = (array) ($timetable_slots ?? []);
$timetableSelectedDaySlots = (array) ($timetable_selected_day_slots ?? []);
$timetableConflicts = (array) ($timetable_conflicts ?? []);
$timetableDayLabels = (array) ($timetable_day_labels ?? kuppi_timetable_day_labels());
$hasTimetableConflict = !empty($timetableConflicts);

$timetableSlotsForJs = array_map(static function (array $slot): array {
    return [
        'day_of_week' => (int) ($slot['day_of_week'] ?? 0),
        'start_time' => kuppi_timetable_time_label((string) ($slot['start_time'] ?? '')),
        'end_time' => kuppi_timetable_time_label((string) ($slot['end_time'] ?? '')),
        'start_minutes' => (int) (kuppi_timetable_time_to_minutes((string) ($slot['start_time'] ?? '')) ?? 0),
        'end_minutes' => (int) (kuppi_timetable_time_to_minutes((string) ($slot['end_time'] ?? '')) ?? 0),
        'reason' => kuppi_timetable_reason_label($slot),
    ];
}, $timetableSlots);

$timetableSlotsJson = json_encode(
    $timetableSlotsForJs,
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
) ?: '[]';
$timetableDayLabelsJson = json_encode(
    $timetableDayLabels,
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
) ?: '{}';

$durationLabel = 'Automatically calculated from start and end time';
if ($durationMinutes > 0) {
    $hours = intdiv($durationMinutes, 60);
    $minutes = $durationMinutes % 60;
    if ($hours > 0 && $minutes > 0) {
        $durationLabel = $hours . 'h ' . $minutes . 'm';
    } elseif ($hours > 0) {
        $durationLabel = $hours . ' hour' . ($hours > 1 ? 's' : '');
    } else {
        $durationLabel = $minutes . ' minutes';
    }
}
?>

<div class="page-header kuppi-wizard-header">
    <div class="page-header-content">
        <h1>Schedule Kuppi Session</h1>
        <p class="page-subtitle">Review requests and schedule kuppi sessions with conductors.</p>
    </div>
    <div class="page-header-actions">
        <a href="/dashboard/kuppi" class="btn btn-outline"><?= ui_lucide_icon('arrow-left') ?> Back to Sessions</a>
    </div>
</div>

<section class="kuppi-wizard-stepper" aria-label="Scheduling steps">
    <div class="kuppi-wizard-step is-complete">
        <span class="kuppi-wizard-step-icon"><?= ui_lucide_icon('file-text') ?></span>
        <strong>Select Request</strong>
    </div>
    <div class="kuppi-wizard-step is-complete">
        <span class="kuppi-wizard-step-icon"><?= ui_lucide_icon('user-check') ?></span>
        <strong>Assign Hosts</strong>
    </div>
    <div class="kuppi-wizard-step is-active">
        <span class="kuppi-wizard-step-icon"><?= ui_lucide_icon('calendar') ?></span>
        <strong>Set Schedule</strong>
    </div>
    <div class="kuppi-wizard-step">
        <span class="kuppi-wizard-step-icon"><?= ui_lucide_icon('check-circle') ?></span>
        <strong>Review & Confirm</strong>
    </div>
</section>

<div class="card kuppi-wizard-card">
    <div class="card-body">
        <h2>Set Session Schedule</h2>
        <p class="kuppi-wizard-muted">Define the date, time, and location for the kuppi session.</p>

        <?php if ($mode === 'request' && !empty($linkedRequest)): ?>
            <article class="kuppi-wizard-context">
                <p class="kuppi-wizard-request-subject"><?= ui_lucide_icon('book-open') ?> <?= e((string) ($linkedRequest['subject_code'] ?? 'SUB')) ?> - <?= e((string) ($linkedRequest['subject_name'] ?? 'Subject')) ?></p>
                <h3><?= e((string) ($linkedRequest['title'] ?? 'Requested Session')) ?></h3>
                <div class="kuppi-wizard-request-meta">
                    <span><?= ui_lucide_icon('user') ?> Requested by <strong><?= e((string) ($linkedRequest['requester_name'] ?? 'Unknown User')) ?></strong></span>
                    <span><?= ui_lucide_icon('arrow-up') ?> <?= (int) ($linkedRequest['vote_score'] ?? 0) ?> votes</span>
                </div>
            </article>
        <?php endif; ?>

        <?php if (!empty($selectedHosts)): ?>
            <article class="kuppi-wizard-context">
                <p class="kuppi-wizard-request-subject"><?= ui_lucide_icon('user-check') ?> Selected Hosts (<?= count($selectedHosts) ?>)</p>
                <div class="kuppi-tags">
                    <?php foreach ($selectedHosts as $host): ?>
                        <?php $hostName = trim((string) ($host['host_name'] ?? 'Unknown User')); ?>
                        <span class="badge"><?= e($hostName !== '' ? $hostName : 'Unknown User') ?></span>
                    <?php endforeach; ?>
                </div>

                <?php if ($hostsWithAvailability > 0 && !empty($rankedSlotCounts)): ?>
                    <p class="kuppi-wizard-muted-inline">Suggested schedule windows from selected conductors:</p>
                    <div class="kuppi-tags">
                        <?php foreach ($rankedSlotCounts as $slot => $count): ?>
                            <?php
                            $label = (string) ($availabilityOptions[$slot] ?? $slot);
                            $isRecommended = in_array($slot, $recommendedSlots, true);
                            ?>
                            <span class="badge <?= $isRecommended ? 'badge-info' : '' ?>">
                                <?= e($label) ?> · <?= (int) $count ?>/<?= $hostsWithAvailability ?>
                            </span>
                        <?php endforeach; ?>
                    </div>

                    <?php if ($selectedSlotKey !== '' && isset($availabilityOptions[$selectedSlotKey])): ?>
                        <?php
                        $matchedHosts = (int) ($selectedSlotMatch['matched_hosts'] ?? 0);
                        $isFullMatch = !empty($selectedSlotMatch['is_full_match']);
                        ?>
                        <p class="kuppi-wizard-muted-inline <?= $isFullMatch ? 'text-success' : 'text-warning' ?>">
                            Current time window: <?= e((string) ($availabilityOptions[$selectedSlotKey] ?? $selectedSlotKey)) ?>
                            (<?= $matchedHosts ?>/<?= $hostsWithAvailability ?> hosts available)
                        </p>
                    <?php endif; ?>
                <?php endif; ?>
            </article>
        <?php endif; ?>

        <article class="kuppi-wizard-context kuppi-wizard-context--timetable">
            <div class="kuppi-wizard-context-head">
                <p class="kuppi-wizard-request-subject"><?= ui_lucide_icon('calendar-clock') ?> Official University Timetable</p>
                <a href="/dashboard/kuppi/timetable<?= $isAdmin && $selectedBatchId > 0 ? '?batch_id=' . $selectedBatchId : '' ?>" class="btn btn-outline btn-sm">View Timetable</a>
            </div>
            <p class="kuppi-wizard-muted-inline">
                Official lecture slots are strict blockers. You cannot continue while your selected time overlaps any blocked slot.
            </p>

            <div class="kuppi-schedule-blocked-panel" id="kuppi-blocked-panel">
                <h4><?= ui_lucide_icon('calendar-days') ?> Selected Day: Blocked Slots</h4>
                <ul id="kuppi-blocked-slots-list">
                    <?php if (!empty($timetableSelectedDaySlots)): ?>
                        <?php foreach ($timetableSelectedDaySlots as $slot): ?>
                            <li>
                                <strong><?= e(kuppi_timetable_time_label((string) ($slot['start_time'] ?? ''))) ?> - <?= e(kuppi_timetable_time_label((string) ($slot['end_time'] ?? ''))) ?></strong>
                                <span><?= e(kuppi_timetable_reason_label($slot)) ?></span>
                            </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li class="is-empty">No official blocked slots for the selected day.</li>
                    <?php endif; ?>
                </ul>
            </div>

            <div class="kuppi-schedule-conflict-warning <?= $hasTimetableConflict ? '' : 'is-hidden' ?>" id="kuppi-conflict-warning" role="alert" aria-live="polite">
                <h4><?= ui_lucide_icon('triangle-alert') ?> Conflict Detected</h4>
                <p>This session overlaps official lecture time. Update date/time to continue.</p>
                <ul id="kuppi-conflict-list">
                    <?php foreach ($timetableConflicts as $slot): ?>
                        <li><?= e(kuppi_timetable_slot_summary($slot)) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </article>

        <form method="POST" action="/dashboard/kuppi/schedule/set" class="kuppi-wizard-form-grid" id="kuppi-schedule-set-form">
            <?= csrf_field() ?>

            <?php if ($mode === 'manual'): ?>
                <?php if ($isAdmin): ?>
                    <div class="form-group">
                        <label for="batch_id">Batch</label>
                        <select id="batch_id" name="batch_id" required>
                            <option value="">Select batch</option>
                            <?php foreach ($batchOptions as $batch): ?>
                                <?php $batchId = (int) ($batch['id'] ?? 0); ?>
                                <option value="<?= $batchId ?>" <?= $selectedBatchId === $batchId ? 'selected' : '' ?>>
                                    <?= e((string) ($batch['batch_code'] ?? 'BATCH')) ?> — <?= e((string) ($batch['name'] ?? '')) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php else: ?>
                    <input type="hidden" name="batch_id" value="<?= $selectedBatchId ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label for="subject_id">Subject</label>
                    <select id="subject_id" name="subject_id" required>
                        <option value="">Select subject</option>
                        <?php foreach ($subjectOptions as $subject): ?>
                            <?php $subjectId = (int) ($subject['id'] ?? 0); ?>
                            <option value="<?= $subjectId ?>" <?= $selectedSubjectId === $subjectId ? 'selected' : '' ?>>
                                <?= e((string) ($subject['code'] ?? 'SUB')) ?> — <?= e((string) ($subject['name'] ?? '')) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group form-group-span-2">
                    <label for="title">Session Topic</label>
                    <input type="text" id="title" name="title" maxlength="200" required value="<?= e((string) ($draft['title'] ?? '')) ?>" placeholder="e.g., Binary Search Trees Implementation">
                </div>

                <div class="form-group form-group-span-2">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="4" maxlength="2000" required placeholder="Describe what should be covered in this session."><?= e((string) ($draft['description'] ?? '')) ?></textarea>
                </div>
            <?php else: ?>
                <input type="hidden" name="batch_id" value="<?= $selectedBatchId ?>">
                <input type="hidden" name="subject_id" value="<?= $selectedSubjectId ?>">
            <?php endif; ?>

            <div class="form-group">
                <label for="session_date">Session Date *</label>
                <input type="date" id="session_date" name="session_date" required value="<?= e($sessionDate) ?>">
            </div>

            <div class="form-group">
                <label for="start_time">Start Time *</label>
                <input type="time" id="start_time" name="start_time" required value="<?= e($startTime) ?>">
            </div>

            <div class="form-group">
                <label for="end_time">End Time *</label>
                <input type="time" id="end_time" name="end_time" required value="<?= e($endTime) ?>">
            </div>

            <div class="form-group">
                <label>Duration</label>
                <div class="kuppi-wizard-readonly"><?= e($durationLabel) ?></div>
                <small class="text-muted">Automatically calculated from start and end time.</small>
            </div>

            <div class="form-group">
                <label for="max_attendees">Maximum Attendees *</label>
                <div class="kuppi-wizard-counter" data-counter>
                    <button type="button" class="kuppi-wizard-counter-btn" data-counter-action="dec" aria-label="Decrease">−</button>
                    <input type="number" id="max_attendees" name="max_attendees" min="1" max="2000" required value="<?= $maxAttendees ?>">
                    <button type="button" class="kuppi-wizard-counter-btn" data-counter-action="inc" aria-label="Increase">+</button>
                </div>
            </div>

            <div class="form-group form-group-span-2">
                <label>Location Type *</label>
                <div class="kuppi-wizard-choice-grid">
                    <label class="kuppi-wizard-choice">
                        <input type="radio" name="location_type" value="physical" <?= $selectedLocationType === 'physical' ? 'checked' : '' ?>>
                        <span><?= ui_lucide_icon('map-pin') ?> Physical Location</span>
                    </label>
                    <label class="kuppi-wizard-choice">
                        <input type="radio" name="location_type" value="online" <?= $selectedLocationType === 'online' ? 'checked' : '' ?>>
                        <span><?= ui_lucide_icon('video') ?> Online Session</span>
                    </label>
                </div>
            </div>

            <div class="form-group form-group-span-2" id="kuppi-location-physical-wrap">
                <label for="location_text">Location *</label>
                <input type="text" id="location_text" name="location_text" maxlength="255" value="<?= e($locationText) ?>" placeholder="e.g., Main Library, Room 204">
            </div>

            <div class="form-group form-group-span-2" id="kuppi-location-online-wrap">
                <label for="meeting_link">Meeting Link *</label>
                <input type="url" id="meeting_link" name="meeting_link" maxlength="255" value="<?= e($meetingLink) ?>" placeholder="https://zoom.us/j/...">
            </div>

            <div class="form-group form-group-span-2">
                <label for="notes">Additional Notes</label>
                <textarea id="notes" name="notes" rows="3" maxlength="3000" placeholder="Any special instructions or requirements for attendees..."><?= e($notes) ?></textarea>
            </div>

            <div class="kuppi-wizard-actions form-group-span-2">
                <a href="/dashboard/kuppi/schedule/assign" class="btn btn-outline"><?= ui_lucide_icon('arrow-left') ?> Back</a>
                <button type="submit" class="btn btn-primary kuppi-wizard-cta" id="kuppi-schedule-continue" <?= $hasTimetableConflict ? 'disabled' : '' ?>>Continue <?= ui_lucide_icon('arrow-right') ?></button>
            </div>
        </form>
    </div>
</div>

<script id="kuppi-timetable-slots-data" type="application/json"><?= $timetableSlotsJson ?></script>
<script id="kuppi-timetable-day-labels-data" type="application/json"><?= $timetableDayLabelsJson ?></script>
<script>
(function () {
    function readJsonScript(id, fallback) {
        const el = document.getElementById(id);
        if (!el) {
            return fallback;
        }
        try {
            return JSON.parse(el.textContent || '');
        } catch (error) {
            return fallback;
        }
    }

    function toMinutes(value) {
        if (!/^\d{2}:\d{2}$/.test(value || '')) {
            return null;
        }
        const hours = Number(value.slice(0, 2));
        const minutes = Number(value.slice(3, 5));
        if (Number.isNaN(hours) || Number.isNaN(minutes)) {
            return null;
        }
        return (hours * 60) + minutes;
    }

    function dayOfWeekFromDate(dateValue) {
        if (!dateValue) {
            return null;
        }
        const parsed = new Date(dateValue + 'T00:00:00');
        if (Number.isNaN(parsed.getTime())) {
            return null;
        }
        const jsDay = parsed.getDay();
        return jsDay === 0 ? 7 : jsDay;
    }

    const counter = document.querySelector('[data-counter]');
    if (counter) {
        const input = counter.querySelector('input[type="number"]');
        const min = Number(input?.min || 1);
        const max = Number(input?.max || 2000);

        counter.addEventListener('click', function (event) {
            const button = event.target.closest('[data-counter-action]');
            if (!button || !input) {
                return;
            }

            const action = button.getAttribute('data-counter-action');
            const current = Number(input.value || min);
            if (action === 'inc') {
                input.value = String(Math.min(max, current + 1));
            }
            if (action === 'dec') {
                input.value = String(Math.max(min, current - 1));
            }
        });
    }

    const radios = Array.from(document.querySelectorAll('input[name="location_type"]'));
    const physicalWrap = document.getElementById('kuppi-location-physical-wrap');
    const onlineWrap = document.getElementById('kuppi-location-online-wrap');

    function syncLocationVisibility() {
        const selected = radios.find((radio) => radio.checked)?.value || 'physical';
        if (physicalWrap) {
            physicalWrap.style.display = selected === 'physical' ? '' : 'none';
        }
        if (onlineWrap) {
            onlineWrap.style.display = selected === 'online' ? '' : 'none';
        }
    }

    radios.forEach((radio) => radio.addEventListener('change', syncLocationVisibility));
    syncLocationVisibility();

    const slots = readJsonScript('kuppi-timetable-slots-data', []);
    const dayLabels = readJsonScript('kuppi-timetable-day-labels-data', {});
    const dateInput = document.getElementById('session_date');
    const startInput = document.getElementById('start_time');
    const endInput = document.getElementById('end_time');
    const blockedSlotsList = document.getElementById('kuppi-blocked-slots-list');
    const conflictWarning = document.getElementById('kuppi-conflict-warning');
    const conflictList = document.getElementById('kuppi-conflict-list');
    const continueButton = document.getElementById('kuppi-schedule-continue');

    function renderBlockedSlots(dayOfWeek) {
        if (!blockedSlotsList) {
            return;
        }

        blockedSlotsList.innerHTML = '';
        if (!dayOfWeek) {
            const empty = document.createElement('li');
            empty.className = 'is-empty';
            empty.textContent = 'Select a date to see blocked slots for that day.';
            blockedSlotsList.appendChild(empty);
            return;
        }

        const daySlots = slots
            .filter((slot) => Number(slot.day_of_week) === Number(dayOfWeek))
            .sort((a, b) => Number(a.start_minutes) - Number(b.start_minutes));

        if (daySlots.length === 0) {
            const empty = document.createElement('li');
            empty.className = 'is-empty';
            const dayName = dayLabels[String(dayOfWeek)] || dayLabels[dayOfWeek] || 'selected day';
            empty.textContent = 'No official blocked slots for ' + dayName + '.';
            blockedSlotsList.appendChild(empty);
            return;
        }

        daySlots.forEach((slot) => {
            const li = document.createElement('li');
            const strong = document.createElement('strong');
            strong.textContent = String(slot.start_time || '') + ' - ' + String(slot.end_time || '');
            const reason = document.createElement('span');
            reason.textContent = String(slot.reason || 'Official lecture slot');
            li.appendChild(strong);
            li.appendChild(reason);
            blockedSlotsList.appendChild(li);
        });
    }

    function renderConflicts(conflicts) {
        if (!conflictWarning || !conflictList) {
            return;
        }

        conflictList.innerHTML = '';
        if (conflicts.length === 0) {
            conflictWarning.classList.add('is-hidden');
            return;
        }

        conflicts.forEach((slot) => {
            const li = document.createElement('li');
            const dayName = dayLabels[String(slot.day_of_week)] || dayLabels[slot.day_of_week] || 'Selected day';
            li.textContent = dayName + ' ' + String(slot.start_time || '') + ' - ' + String(slot.end_time || '') + ' (' + String(slot.reason || 'Official lecture slot') + ')';
            conflictList.appendChild(li);
        });

        conflictWarning.classList.remove('is-hidden');
    }

    function evaluateTimetableConflict() {
        const dayOfWeek = dayOfWeekFromDate(dateInput?.value || '');
        renderBlockedSlots(dayOfWeek);

        const startMinutes = toMinutes(startInput?.value || '');
        const endMinutes = toMinutes(endInput?.value || '');
        let conflicts = [];

        if (dayOfWeek && startMinutes !== null && endMinutes !== null && endMinutes > startMinutes) {
            conflicts = slots.filter((slot) => (
                Number(slot.day_of_week) === Number(dayOfWeek)
                && Number(slot.start_minutes) < endMinutes
                && Number(slot.end_minutes) > startMinutes
            ));
        }

        renderConflicts(conflicts);
        if (continueButton) {
            continueButton.disabled = conflicts.length > 0;
        }
    }

    if (dateInput) {
        dateInput.addEventListener('change', evaluateTimetableConflict);
        dateInput.addEventListener('input', evaluateTimetableConflict);
    }
    if (startInput) {
        startInput.addEventListener('change', evaluateTimetableConflict);
        startInput.addEventListener('input', evaluateTimetableConflict);
    }
    if (endInput) {
        endInput.addEventListener('change', evaluateTimetableConflict);
        endInput.addEventListener('input', evaluateTimetableConflict);
    }

    evaluateTimetableConflict();
})();
</script>
