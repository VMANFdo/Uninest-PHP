<?php
$session = (array) ($session ?? []);
$candidates = (array) ($candidates ?? []);
$selectedHostIds = array_values(array_filter(array_map('intval', (array) ($selected_host_ids ?? [])), static fn(int $id): bool => $id > 0));
$selectedMap = array_fill_keys($selectedHostIds, true);
$statusOptions = (array) ($status_options ?? ['scheduled', 'completed']);
$availabilityOptions = (array) ($availability_options ?? []);

$sessionId = (int) ($session['id'] ?? 0);
$selectedStatus = (string) ($session['status'] ?? 'scheduled');
$selectedLocationType = (string) ($session['location_type'] ?? 'physical');
if (!in_array($selectedLocationType, ['physical', 'online'], true)) {
    $selectedLocationType = 'physical';
}
?>

<div class="page-header">
    <div class="page-header-content">
        <p class="page-breadcrumb">Dashboard / Kuppi Sessions / Scheduled Session / Edit</p>
        <h1>Edit Scheduled Session</h1>
        <p class="page-subtitle">Update schedule, hosts, and status.</p>
    </div>
    <div class="page-header-actions">
        <a href="/dashboard/kuppi/scheduled/<?= $sessionId ?>" class="btn btn-outline"><?= ui_lucide_icon('arrow-left') ?> Back to Session</a>
    </div>
</div>

<div class="card kuppi-scheduler-card">
    <div class="card-body">
        <form method="POST" action="/dashboard/kuppi/scheduled/<?= $sessionId ?>" class="kuppi-scheduler-form-grid">
            <?= csrf_field() ?>

            <div class="form-group">
                <label for="title">Session Title</label>
                <input type="text" id="title" name="title" maxlength="200" required value="<?= e((string) ($session['title'] ?? '')) ?>">
            </div>

            <div class="form-group">
                <label for="status">Status</label>
                <select id="status" name="status" required>
                    <?php foreach ($statusOptions as $status): ?>
                        <option value="<?= e((string) $status) ?>" <?= $selectedStatus === $status ? 'selected' : '' ?>>
                            <?= e(ucfirst((string) $status)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group form-group-span-2">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="4" maxlength="2000" required><?= e((string) ($session['description'] ?? '')) ?></textarea>
            </div>

            <div class="form-group">
                <label for="session_date">Session Date</label>
                <input type="date" id="session_date" name="session_date" required value="<?= e((string) ($session['session_date'] ?? '')) ?>">
            </div>

            <div class="form-group">
                <label for="start_time">Start Time</label>
                <input type="time" id="start_time" name="start_time" required value="<?= e(substr((string) ($session['start_time'] ?? ''), 0, 5)) ?>">
            </div>

            <div class="form-group">
                <label for="end_time">End Time</label>
                <input type="time" id="end_time" name="end_time" required value="<?= e(substr((string) ($session['end_time'] ?? ''), 0, 5)) ?>">
            </div>

            <div class="form-group">
                <label for="max_attendees">Maximum Attendees</label>
                <input type="number" id="max_attendees" name="max_attendees" min="1" max="2000" required value="<?= (int) ($session['max_attendees'] ?? 0) ?>">
            </div>

            <div class="form-group form-group-span-2">
                <label>Location Type</label>
                <div class="kuppi-scheduler-location-type">
                    <label class="kuppi-scheduler-choice">
                        <input type="radio" name="location_type" value="physical" <?= $selectedLocationType === 'physical' ? 'checked' : '' ?>>
                        <span><?= ui_lucide_icon('map-pin') ?> Physical</span>
                    </label>
                    <label class="kuppi-scheduler-choice">
                        <input type="radio" name="location_type" value="online" <?= $selectedLocationType === 'online' ? 'checked' : '' ?>>
                        <span><?= ui_lucide_icon('video') ?> Online</span>
                    </label>
                </div>
            </div>

            <div class="form-group">
                <label for="location_text">Physical Location</label>
                <input type="text" id="location_text" name="location_text" maxlength="255" value="<?= e((string) ($session['location_text'] ?? '')) ?>" placeholder="e.g., Main Library, Room 204">
                <small class="text-muted">Required for physical sessions.</small>
            </div>

            <div class="form-group">
                <label for="meeting_link">Meeting Link</label>
                <input type="url" id="meeting_link" name="meeting_link" maxlength="255" value="<?= e((string) ($session['meeting_link'] ?? '')) ?>" placeholder="https://meet.google.com/...">
                <small class="text-muted">Required for online sessions.</small>
            </div>

            <div class="form-group form-group-span-2">
                <label for="notes">Notes</label>
                <textarea id="notes" name="notes" rows="3" maxlength="3000"><?= e((string) ($session['notes'] ?? '')) ?></textarea>
            </div>

            <div class="form-group form-group-span-2">
                <label>Assigned Hosts</label>
                <?php if (empty($candidates)): ?>
                    <p class="text-muted">No host candidates available.</p>
                <?php else: ?>
                    <div class="kuppi-host-candidate-list">
                        <?php foreach ($candidates as $candidate): ?>
                            <?php
                            $hostId = (int) ($candidate['host_user_id'] ?? 0);
                            if ($hostId <= 0) {
                                continue;
                            }
                            $hostName = trim((string) ($candidate['host_name'] ?? 'Unknown User'));
                            if ($hostName === '') {
                                $hostName = 'Unknown User';
                            }
                            $toneClass = ui_avatar_tone_class((string) ($hostId . '-' . $hostName));
                            $availability = (array) ($candidate['availability'] ?? []);
                            $isSelected = !empty($selectedMap[$hostId]);
                            ?>
                            <label class="kuppi-host-candidate <?= $isSelected ? 'is-selected' : '' ?>">
                                <div class="kuppi-host-candidate-select">
                                    <input type="checkbox" name="host_user_ids[]" value="<?= $hostId ?>" <?= $isSelected ? 'checked' : '' ?>>
                                </div>
                                <div class="kuppi-host-candidate-body">
                                    <div class="kuppi-host-candidate-head">
                                        <div class="kuppi-host-candidate-identity">
                                            <span class="kuppi-conductor-avatar <?= e($toneClass) ?>"><?= e(ui_initials($hostName)) ?></span>
                                            <div>
                                                <h3><?= e($hostName) ?></h3>
                                                <p class="kuppi-request-meta">
                                                    <?= e(ucfirst((string) ($candidate['host_role'] ?? 'student'))) ?>
                                                    <?php if ((int) ($candidate['host_academic_year'] ?? 0) > 0): ?>
                                                        • Year <?= (int) ($candidate['host_academic_year'] ?? 0) ?>
                                                    <?php endif; ?>
                                                </p>
                                            </div>
                                        </div>

                                        <?php if ((int) ($candidate['vote_count'] ?? 0) > 0): ?>
                                            <span class="badge badge-info"><?= (int) ($candidate['vote_count'] ?? 0) ?> votes</span>
                                        <?php endif; ?>
                                    </div>

                                    <?php if (!empty($availability)): ?>
                                        <div class="kuppi-tags">
                                            <?php foreach ($availability as $slot): ?>
                                                <span class="badge"><?= e((string) ($availabilityOptions[$slot] ?? $slot)) ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="form-actions form-group-span-2">
                <a href="/dashboard/kuppi/scheduled/<?= $sessionId ?>" class="btn btn-outline">Cancel</a>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>
