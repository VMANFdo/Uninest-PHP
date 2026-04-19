<?php
$userCount = (int) ($user_count ?? 0);
$studentCount = (int) ($student_count ?? 0);
$coordinatorCount = (int) ($coordinator_count ?? 0);
$moderatorCount = (int) ($moderator_count ?? 0);
$batchCount = (int) ($batch_count ?? 0);
$subjectCount = (int) ($subject_count ?? 0);
$resourceCount = (int) ($resource_count ?? 0);
$quizCount = (int) ($quiz_count ?? 0);
$openKuppiCount = (int) ($open_kuppi_count ?? 0);
$upcomingSessionCount = (int) ($upcoming_session_count ?? 0);
$announcementCount = (int) ($announcement_count ?? 0);

$pendingBatchRequests = (int) ($pending_batch_requests ?? 0);
$pendingStudentRequests = (int) ($pending_student_requests ?? 0);
$pendingQuizRequests = (int) ($pending_quiz_requests ?? 0);
$pendingResourceRequests = (int) ($pending_resource_requests ?? 0);
$openReportCount = (int) ($open_report_count ?? 0);

$latestPendingBatches = (array) ($latest_pending_batches ?? []);
$latestPendingStudents = (array) ($latest_pending_students ?? []);
$latestPendingQuizzes = (array) ($latest_pending_quizzes ?? []);
$latestOpenReports = (array) ($latest_open_reports ?? []);
?>

<section class="student-dash-hero role-hero role-hero--admin">
    <div class="student-dash-hero-main">
        <p class="student-dash-eyebrow">Admin Dashboard</p>
        <h1>Oversee platform operations, approvals, and quality controls.</h1>
        <p class="student-dash-copy">
            Welcome back, <?= e((string) ($user['name'] ?? 'Admin')) ?>.
            Use this workspace to keep onboarding, learning content, and moderation queues healthy.
        </p>

        <div class="student-dash-chips">
            <span><?= ui_lucide_icon('users') ?> <?= $userCount ?> users</span>
            <span><?= ui_lucide_icon('folder') ?> <?= $batchCount ?> batches</span>
            <span><?= ui_lucide_icon('book-open') ?> <?= $subjectCount ?> subjects</span>
            <span><?= ui_lucide_icon('clipboard-check') ?> <?= $quizCount ?> approved quizzes</span>
        </div>
    </div>

    <aside class="student-dash-today">
        <h2><?= ui_lucide_icon('inbox') ?> Global Queue</h2>
        <ul>
            <li><span>Batch Requests</span><strong><?= $pendingBatchRequests ?></strong></li>
            <li><span>Student Requests</span><strong><?= $pendingStudentRequests ?></strong></li>
            <li><span>Quiz Requests</span><strong><?= $pendingQuizRequests ?></strong></li>
            <li><span>Open Reports</span><strong><?= $openReportCount ?></strong></li>
        </ul>
        <div class="student-dash-inline-actions">
            <a href="/admin/batch-requests" class="btn btn-sm btn-primary">Review Batch Queue</a>
            <a href="/admin/student-requests" class="btn btn-sm btn-outline">Review Student Queue</a>
        </div>
    </aside>
</section>

<section class="student-dash-actions">
    <a href="/admin/batch-requests" class="student-dash-action-card">
        <span class="student-dash-action-icon"><?= ui_lucide_icon('inbox') ?></span>
        <strong>Batch Requests</strong>
        <small>Approve moderator-owned batch creation</small>
    </a>
    <a href="/admin/student-requests" class="student-dash-action-card">
        <span class="student-dash-action-icon"><?= ui_lucide_icon('users') ?></span>
        <strong>Student Requests</strong>
        <small>Validate student batch joins</small>
    </a>
    <a href="/dashboard/quiz-requests" class="student-dash-action-card">
        <span class="student-dash-action-icon"><?= ui_lucide_icon('check-check') ?></span>
        <strong>Quiz Requests</strong>
        <small>Review pending quiz publications</small>
    </a>
    <a href="/dashboard/community/reports" class="student-dash-action-card">
        <span class="student-dash-action-icon"><?= ui_lucide_icon('flag') ?></span>
        <strong>Reports Queue</strong>
        <small>Resolve open post and comment reports</small>
    </a>
    <a href="/admin/moderators" class="student-dash-action-card">
        <span class="student-dash-action-icon"><?= ui_lucide_icon('shield') ?></span>
        <strong>Moderators</strong>
        <small>Provision and manage moderator accounts</small>
    </a>
    <a href="/admin/batches" class="student-dash-action-card">
        <span class="student-dash-action-icon"><?= ui_lucide_icon('folder') ?></span>
        <strong>Batches</strong>
        <small>Manage approved and pending batches</small>
    </a>
    <a href="/students" class="student-dash-action-card">
        <span class="student-dash-action-icon"><?= ui_lucide_icon('user-round') ?></span>
        <strong>Students</strong>
        <small>Manage student access records</small>
    </a>
    <a href="/subjects" class="student-dash-action-card">
        <span class="student-dash-action-icon"><?= ui_lucide_icon('layers') ?></span>
        <strong>Subjects</strong>
        <small>Open global subject catalog controls</small>
    </a>
</section>

<section class="student-dash-kpis">
    <article class="student-dash-kpi">
        <span>Students</span>
        <strong><?= $studentCount ?></strong>
        <p>Coordinators <?= $coordinatorCount ?> · Moderators <?= $moderatorCount ?></p>
    </article>
    <article class="student-dash-kpi">
        <span>Published Resources</span>
        <strong><?= $resourceCount ?></strong>
        <p>Pending resource requests <?= $pendingResourceRequests ?></p>
    </article>
    <article class="student-dash-kpi">
        <span>Kuppi Workload</span>
        <strong><?= $openKuppiCount ?></strong>
        <p>Open requests · Upcoming sessions <?= $upcomingSessionCount ?></p>
    </article>
    <article class="student-dash-kpi">
        <span>Announcements</span>
        <strong><?= $announcementCount ?></strong>
        <p>Official notices across all batches</p>
    </article>
</section>

<section class="student-dash-grid">
    <article class="student-dash-card">
        <header class="student-dash-card-head">
            <h2><?= ui_lucide_icon('list-checks') ?> Approval Priorities</h2>
        </header>
        <ul class="student-dash-personal-list">
            <li><span>Batch Requests</span><strong><?= $pendingBatchRequests ?></strong></li>
            <li><span>Student Requests</span><strong><?= $pendingStudentRequests ?></strong></li>
            <li><span>Quiz Requests</span><strong><?= $pendingQuizRequests ?></strong></li>
            <li><span>Community Reports</span><strong><?= $openReportCount ?></strong></li>
        </ul>
        <div class="student-dash-inline-actions">
            <a href="/admin/batch-requests" class="btn btn-sm btn-primary">Batch Queue</a>
            <a href="/admin/student-requests" class="btn btn-sm btn-outline">Student Queue</a>
            <a href="/dashboard/quiz-requests" class="btn btn-sm btn-outline">Quiz Queue</a>
            <a href="/dashboard/community/reports" class="btn btn-sm btn-outline">Reports Queue</a>
        </div>
    </article>

    <article class="student-dash-card">
        <header class="student-dash-card-head">
            <h2><?= ui_lucide_icon('zap') ?> Admin Quick Actions</h2>
        </header>
        <div class="student-dash-inline-actions">
            <a href="/students/create" class="btn btn-sm btn-primary">Add Student</a>
            <a href="/subjects/create" class="btn btn-sm btn-outline">Add Subject</a>
            <a href="/dashboard/announcements/create" class="btn btn-sm btn-outline">Create Announcement</a>
            <a href="/dashboard/gpa/grade-scale" class="btn btn-sm btn-outline">Grade Point Config</a>
            <a href="/dashboard/quiz-analytics" class="btn btn-sm btn-outline">Quiz Analytics</a>
            <a href="/dashboard/feed" class="btn btn-sm btn-outline">Central Feed</a>
        </div>
    </article>
</section>

<section class="student-dash-grid">
    <article class="student-dash-card">
        <header class="student-dash-card-head">
            <h2><?= ui_lucide_icon('folder-clock') ?> Latest Pending Requests</h2>
        </header>

        <?php if (empty($latestPendingBatches) && empty($latestPendingStudents) && empty($latestPendingQuizzes)): ?>
            <p class="text-muted">No pending items right now.</p>
        <?php else: ?>
            <div class="student-dash-activity-list">
                <?php foreach ($latestPendingBatches as $row): ?>
                    <a href="/admin/batch-requests" class="student-dash-activity-item">
                        <div class="student-dash-activity-head">
                            <span class="badge badge-warning">Batch Request</span>
                            <time><?= e(date('M d, H:i', strtotime((string) ($row['created_at'] ?? 'now')))) ?></time>
                        </div>
                        <h3><?= e((string) ($row['name'] ?? 'Batch Request')) ?></h3>
                        <p><?= e((string) (($row['moderator_name'] ?? 'Moderator') . ' · ' . ($row['program'] ?? 'Program'))) ?></p>
                    </a>
                <?php endforeach; ?>

                <?php foreach ($latestPendingStudents as $row): ?>
                    <a href="/admin/student-requests" class="student-dash-activity-item">
                        <div class="student-dash-activity-head">
                            <span class="badge badge-warning">Student Request</span>
                            <time><?= e(date('M d, H:i', strtotime((string) ($row['created_at'] ?? 'now')))) ?></time>
                        </div>
                        <h3><?= e((string) ($row['student_name'] ?? 'Student')) ?></h3>
                        <p><?= e((string) (($row['batch_code'] ?? 'Batch') . ' · ' . ($row['student_email'] ?? ''))) ?></p>
                    </a>
                <?php endforeach; ?>

                <?php foreach ($latestPendingQuizzes as $row): ?>
                    <a href="/dashboard/quiz-requests" class="student-dash-activity-item">
                        <div class="student-dash-activity-head">
                            <span class="badge badge-warning">Quiz Request</span>
                            <time><?= e(date('M d, H:i', strtotime((string) ($row['created_at'] ?? 'now')))) ?></time>
                        </div>
                        <h3><?= e((string) ($row['title'] ?? 'Quiz Submission')) ?></h3>
                        <p><?= e((string) (($row['subject_code'] ?? 'Subject') . ' · By ' . ($row['creator_name'] ?? 'Unknown'))) ?></p>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </article>

    <article class="student-dash-card">
        <header class="student-dash-card-head">
            <h2><?= ui_lucide_icon('shield-alert') ?> Latest Open Reports</h2>
            <a href="/dashboard/community/reports" class="btn btn-sm btn-outline">Open Queue</a>
        </header>

        <?php if (empty($latestOpenReports)): ?>
            <p class="text-muted">No open reports right now.</p>
        <?php else: ?>
            <div class="student-dash-activity-list">
                <?php foreach ($latestOpenReports as $report): ?>
                    <a href="/dashboard/community/reports" class="student-dash-activity-item">
                        <div class="student-dash-activity-head">
                            <span class="badge badge-warning"><?= e(ucfirst((string) ($report['target_type'] ?? 'report'))) ?> Report</span>
                            <time><?= e(date('M d, H:i', strtotime((string) ($report['created_at'] ?? 'now')))) ?></time>
                        </div>
                        <h3><?= e('Reason: ' . ucfirst((string) ($report['reason'] ?? 'other'))) ?></h3>
                        <p><?= e((string) (($report['batch_code'] ?? 'Batch') . ' · Reporter: ' . ($report['reporter_name'] ?? 'Unknown'))) ?></p>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </article>
</section>
