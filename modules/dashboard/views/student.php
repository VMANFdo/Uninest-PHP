<?php
$batchId = (int) ($user['batch_id'] ?? 0);
$subjectRows = (array) ($subjects ?? []);
$featuredSubjects = array_slice($subjectRows, 0, 6);
$batchMeta = (array) ($batch_meta ?? []);
$recentActivityItems = (array) ($recent_activity_items ?? []);
$nextSession = (array) ($next_session ?? []);
$hasNextSession = !empty($nextSession['id']);

$subjectCount = (int) ($subject_count ?? count($subjectRows));
$resourceCount = (int) ($resource_count ?? 0);
$quizCount = (int) ($quiz_count ?? 0);
$communityCount = (int) ($community_count ?? 0);
$openKuppiCount = (int) ($open_kuppi_count ?? 0);
$upcomingSessionCount = (int) ($upcoming_session_count ?? 0);
$myPostCount = (int) ($my_post_count ?? 0);
$myResourceCount = (int) ($my_resource_count ?? 0);
$myQuizCount = (int) ($my_quiz_count ?? 0);
$myKuppiRequestCount = (int) ($my_kuppi_request_count ?? 0);
$todayBlockedCount = (int) ($today_blocked_count ?? 0);
$todayBlockedMinutes = (int) ($today_blocked_minutes ?? 0);

$quizSummary = (array) ($quiz_summary ?? []);
$quizAttemptCount = (int) ($quizSummary['attempt_count'] ?? 0);
$quizAvgScore = isset($quizSummary['avg_score']) ? (float) $quizSummary['avg_score'] : null;
$quizBestScore = isset($quizSummary['best_score']) ? (float) $quizSummary['best_score'] : null;
$quizTotalCorrect = (int) ($quizSummary['total_correct'] ?? 0);
$quizTotalQuestions = (int) ($quizSummary['total_questions'] ?? 0);
$quizAccuracy = $quizTotalQuestions > 0 ? ($quizTotalCorrect / $quizTotalQuestions) * 100 : null;

$gpaSummary = (array) ($gpa_summary ?? []);
$latestGpa = isset($gpaSummary['latest_gpa']) ? (float) $gpaSummary['latest_gpa'] : null;
$bestGpa = isset($gpaSummary['best_gpa']) ? (float) $gpaSummary['best_gpa'] : null;
$gpaLatestYear = isset($gpaSummary['latest_academic_year']) ? (int) $gpaSummary['latest_academic_year'] : null;
$gpaLatestSemester = isset($gpaSummary['latest_semester']) ? (int) $gpaSummary['latest_semester'] : null;

$batchCode = trim((string) ($batchMeta['batch_code'] ?? ''));
$batchName = trim((string) ($batchMeta['name'] ?? ''));
$batchUniversityName = trim((string) ($batchMeta['university_name'] ?? ''));

$todayBlockedHours = $todayBlockedMinutes > 0 ? $todayBlockedMinutes / 60 : 0;

$formatDateTime = static function (?string $value, string $fallback = '-'): string {
    if ($value === null || trim($value) === '') {
        return $fallback;
    }

    $timestamp = strtotime($value);
    if ($timestamp === false) {
        return $fallback;
    }

    return date('M d, Y h:i A', $timestamp);
};

$truncate = static function (string $text, int $length = 120): string {
    $normalized = trim((string) preg_replace('/\s+/', ' ', $text));
    if (strlen($normalized) <= $length) {
        return $normalized;
    }
    return substr($normalized, 0, $length - 3) . '...';
};
?>

<section class="student-home-hero">
    <div class="student-home-hero-main">
        <p class="student-home-eyebrow">Student Dashboard</p>
        <h1>Focus your week. Learn faster with your batch.</h1>
        <p class="student-home-copy">
            Welcome back, <?= e((string) ($user['name'] ?? 'Student')) ?>.
            <?php if ($batchCode !== ''): ?>
                You are in <span class="student-home-inline-strong"><?= e($batchCode) ?></span>
                <?php if ($batchName !== ''): ?> · <?= e($batchName) ?><?php endif; ?>
                <?php if ($batchUniversityName !== ''): ?> · <?= e($batchUniversityName) ?><?php endif; ?>.
            <?php elseif ($batchId > 0): ?>
                Your active learning space is <span class="student-home-inline-strong">Batch #<?= $batchId ?></span>.
            <?php endif; ?>
        </p>

        <div class="student-home-scope-pills">
            <span><?= ui_lucide_icon('folder-open') ?> <?= $resourceCount ?> resources</span>
            <span><?= ui_lucide_icon('clipboard-check') ?> <?= $quizCount ?> approved quizzes</span>
            <span><?= ui_lucide_icon('message-square') ?> <?= $communityCount ?> community posts</span>
        </div>

        <div class="student-home-priority-actions">
            <a href="/dashboard/feed" class="btn btn-primary"><?= ui_lucide_icon('newspaper') ?> Central Feed</a>
            <a href="/dashboard/subjects" class="btn btn-primary"><?= ui_lucide_icon('book-open') ?> Subjects</a>
            <a href="/dashboard/quizzes" class="btn btn-outline"><?= ui_lucide_icon('clipboard-check') ?> Quiz Hub</a>
            <a href="/dashboard/kuppi" class="btn btn-outline"><?= ui_lucide_icon('calendar-plus') ?> Kuppi Sessions</a>
            <a href="/dashboard/gpa/analytics" class="btn btn-outline"><?= ui_lucide_icon('line-chart') ?> GPA Analytics</a>
            <a href="/dashboard/community" class="btn btn-outline"><?= ui_lucide_icon('message-square') ?> Community</a>
        </div>
    </div>

    <aside class="student-home-next-card">
        <div class="student-home-next-card-head">
            <h2><?= ui_lucide_icon('calendar-clock') ?> Next Kuppi Session</h2>
            <span class="badge"><?= $upcomingSessionCount ?> upcoming</span>
        </div>
        <?php if ($hasNextSession): ?>
            <?php
            $sessionId = (int) ($nextSession['id'] ?? 0);
            $sessionTitle = trim((string) ($nextSession['title'] ?? 'Scheduled Session'));
            $sessionSubjectCode = trim((string) ($nextSession['subject_code'] ?? ''));
            $sessionDate = trim((string) ($nextSession['session_date'] ?? ''));
            $sessionStart = trim((string) ($nextSession['start_time'] ?? ''));
            $sessionEnd = trim((string) ($nextSession['end_time'] ?? ''));
            $sessionDateLabel = $sessionDate !== '' ? date('D, M d', strtotime($sessionDate)) : 'TBD date';
            $sessionTimeLabel = ($sessionStart !== '' && $sessionEnd !== '')
                ? substr($sessionStart, 0, 5) . ' - ' . substr($sessionEnd, 0, 5)
                : 'TBD time';
            $sessionLocationType = (string) ($nextSession['location_type'] ?? '');
            $sessionLocationText = trim((string) ($nextSession['location_text'] ?? ''));
            $sessionMeetingLink = trim((string) ($nextSession['meeting_link'] ?? ''));
            $locationLabel = $sessionLocationType === 'online'
                ? ($sessionMeetingLink !== '' ? 'Online session link ready' : 'Online session')
                : ($sessionLocationText !== '' ? $sessionLocationText : 'Physical location');
            ?>
            <p class="student-home-next-title">
                <?php if ($sessionSubjectCode !== ''): ?>
                    <span class="badge"><?= e($sessionSubjectCode) ?></span>
                <?php endif; ?>
                <strong><?= e($sessionTitle) ?></strong>
            </p>
            <ul class="student-home-next-meta">
                <li><?= ui_lucide_icon('calendar-days') ?> <?= e($sessionDateLabel) ?></li>
                <li><?= ui_lucide_icon('clock-3') ?> <?= e($sessionTimeLabel) ?></li>
                <li><?= ui_lucide_icon('map-pin') ?> <?= e($locationLabel) ?></li>
            </ul>
            <a href="/dashboard/kuppi/scheduled/<?= $sessionId ?>" class="btn btn-outline btn-sm">Open Session</a>
        <?php else: ?>
            <p class="text-muted">No scheduled sessions yet. Explore open requests and help your batch plan the next Kuppi.</p>
            <a href="/dashboard/kuppi" class="btn btn-outline btn-sm">Browse Kuppi Requests</a>
        <?php endif; ?>
    </aside>
</section>

<section class="student-home-kpi-grid">
    <article class="student-home-kpi-card">
        <span>Subjects</span>
        <strong><?= $subjectCount ?></strong>
        <p>Available in your batch scope</p>
    </article>
    <article class="student-home-kpi-card">
        <span>Open Kuppi Requests</span>
        <strong><?= $openKuppiCount ?></strong>
        <p>Student demand for new sessions</p>
    </article>
    <article class="student-home-kpi-card">
        <span>Quiz Attempts</span>
        <strong><?= $quizAttemptCount ?></strong>
        <p>Best <?= $quizBestScore !== null ? number_format($quizBestScore, 1) . '%' : '-' ?> · Avg <?= $quizAvgScore !== null ? number_format($quizAvgScore, 1) . '%' : '-' ?></p>
    </article>
    <article class="student-home-kpi-card">
        <span>Latest GPA</span>
        <strong><?= $latestGpa !== null ? number_format($latestGpa, 2) : '-' ?></strong>
        <p>
            <?php if ($gpaLatestYear !== null && $gpaLatestSemester !== null): ?>
                Saved for Y<?= $gpaLatestYear ?> · S<?= $gpaLatestSemester ?>
            <?php else: ?>
                Add your first GPA term record
            <?php endif; ?>
        </p>
    </article>
</section>

<section class="student-home-layout">
    <article class="student-home-card">
        <header class="student-home-card-head">
            <h2><?= ui_lucide_icon('sparkles') ?> Recent Batch Activity</h2>
            <a href="/dashboard/feed" class="btn btn-sm btn-outline">Open Feed</a>
        </header>

        <?php if (empty($recentActivityItems)): ?>
            <p class="text-muted">No recent activity in your batch yet.</p>
        <?php else: ?>
            <div class="student-home-activity-list">
                <?php foreach ($recentActivityItems as $item): ?>
                    <?php
                    $itemType = (string) ($item['item_type'] ?? 'community');
                    $itemTypeLabel = function_exists('feed_item_type_label')
                        ? feed_item_type_label($itemType)
                        : ucfirst(str_replace('_', ' ', $itemType));
                    $itemTitle = trim((string) ($item['title'] ?? 'Update'));
                    $itemSummary = $truncate((string) ($item['summary'] ?? ''), 140);
                    $itemActor = trim((string) ($item['actor_name'] ?? 'Unknown User'));
                    $itemSubjectCode = trim((string) ($item['subject_code'] ?? ''));
                    $itemUrl = trim((string) ($item['target_url'] ?? '/dashboard/feed'));
                    $itemEventAt = (string) ($item['event_at'] ?? '');
                    ?>
                    <a href="<?= e($itemUrl) ?>" class="student-home-activity-item">
                        <div class="student-home-activity-head">
                            <span class="badge"><?= e($itemTypeLabel) ?></span>
                            <?php if ($itemSubjectCode !== ''): ?>
                                <span class="student-home-activity-subject"><?= e($itemSubjectCode) ?></span>
                            <?php endif; ?>
                            <time><?= e($formatDateTime($itemEventAt, 'Recently')) ?></time>
                        </div>
                        <h3><?= e($itemTitle) ?></h3>
                        <p><?= e($itemSummary !== '' ? $itemSummary : 'Open to view details.') ?></p>
                        <small><?= ui_lucide_icon('user-round') ?> <?= e($itemActor) ?></small>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </article>

    <article class="student-home-card">
        <header class="student-home-card-head">
            <h2><?= ui_lucide_icon('target') ?> Progress Snapshot</h2>
            <a href="/my-quiz-analytics" class="btn btn-sm btn-outline">Quiz Analytics</a>
        </header>

        <ul class="student-home-progress-list">
            <li>
                <span>Quiz Accuracy</span>
                <strong><?= $quizAccuracy !== null ? number_format($quizAccuracy, 1) . '%' : '-' ?></strong>
            </li>
            <li>
                <span>Best Quiz Score</span>
                <strong><?= $quizBestScore !== null ? number_format($quizBestScore, 1) . '%' : '-' ?></strong>
            </li>
            <li>
                <span>Latest GPA</span>
                <strong><?= $latestGpa !== null ? number_format($latestGpa, 2) : '-' ?></strong>
            </li>
            <li>
                <span>Best GPA</span>
                <strong><?= $bestGpa !== null ? number_format($bestGpa, 2) : '-' ?></strong>
            </li>
        </ul>

        <div class="student-home-mini-kpi-grid">
            <article>
                <span>My Kuppi Requests</span>
                <strong><?= $myKuppiRequestCount ?></strong>
            </article>
            <article>
                <span>My Quizzes</span>
                <strong><?= $myQuizCount ?></strong>
            </article>
            <article>
                <span>My Posts</span>
                <strong><?= $myPostCount ?></strong>
            </article>
            <article>
                <span>My Resources</span>
                <strong><?= $myResourceCount ?></strong>
            </article>
        </div>

        <div class="student-home-day-note">
            <p>
                <?= ui_lucide_icon('calendar-range') ?>
                Today has <strong><?= $todayBlockedCount ?></strong> official lecture block<?= $todayBlockedCount === 1 ? '' : 's' ?>
                (<?= number_format($todayBlockedHours, 1) ?>h). Plan Kuppi outside those slots.
            </p>
            <a href="/dashboard/kuppi/timetable" class="btn btn-sm btn-outline">View Timetable</a>
        </div>
    </article>
</section>

<section class="student-home-card student-home-subjects">
    <header class="student-home-card-head">
        <h2><?= ui_lucide_icon('book-open') ?> Subject Focus</h2>
        <div class="student-home-head-actions">
            <span class="badge"><?= $subjectCount ?> subjects</span>
            <a href="/dashboard/subjects" class="btn btn-sm btn-outline">View All</a>
        </div>
    </header>

    <?php if (empty($featuredSubjects)): ?>
        <p class="text-muted">No subjects are available in your batch yet.</p>
    <?php else: ?>
        <div class="student-home-subject-grid">
            <?php foreach ($featuredSubjects as $subject): ?>
                <?php
                $subjectId = (int) ($subject['id'] ?? 0);
                $subjectCode = trim((string) ($subject['code'] ?? 'SUBJECT'));
                $subjectName = trim((string) ($subject['name'] ?? 'Untitled Subject'));
                $subjectDescription = trim((string) ($subject['description'] ?? ''));
                $subjectStatus = (string) ($subject['status'] ?? 'upcoming');
                $statusClass = match ($subjectStatus) {
                    'in_progress' => 'badge-info',
                    'completed' => 'badge-warning',
                    default => '',
                };
                ?>
                <a href="/dashboard/subjects/<?= $subjectId ?>/topics" class="student-home-subject-card">
                    <div class="student-home-subject-head">
                        <span class="badge"><?= e($subjectCode) ?></span>
                        <span class="badge <?= e($statusClass) ?>"><?= e(subjects_status_label($subjectStatus)) ?></span>
                    </div>
                    <h3><?= e($subjectName) ?></h3>
                    <p><?= e($subjectDescription !== '' ? $truncate($subjectDescription, 120) : 'Description will be added by your moderator.') ?></p>
                    <div class="student-home-subject-meta">
                        <span><?= (int) ($subject['credits'] ?? 0) ?> credits</span>
                        <span>Y<?= (int) ($subject['academic_year'] ?? 1) ?> / S<?= (int) ($subject['semester'] ?? 1) ?></span>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
