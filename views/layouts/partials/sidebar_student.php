<?php
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$isKuppiScheduled = str_starts_with($currentPath, '/dashboard/kuppi/scheduled');
$isKuppiTimetable = str_starts_with($currentPath, '/dashboard/kuppi/timetable');
$isMyKuppiRequests = str_starts_with($currentPath, '/my-kuppi-requests');
$isKuppiRequested = (
    str_starts_with($currentPath, '/dashboard/kuppi')
    && !$isKuppiScheduled
    && !$isKuppiTimetable
);
$isQuizHub = $currentPath === '/dashboard/quizzes'
    || (str_starts_with($currentPath, '/dashboard/subjects/') && str_contains($currentPath, '/quizzes'));
$isCentralFeed = $currentPath === '/dashboard/feed';
$isMyQuizzes = str_starts_with($currentPath, '/my-quizzes');
$isMyQuizAnalytics = $currentPath === '/my-quiz-analytics';
$isGpaCalculator = $currentPath === '/dashboard/gpa';
$isGpaAnalytics = $currentPath === '/dashboard/gpa/analytics';
$isProfileSettings = $currentPath === '/dashboard/profile';
$isSubjects = str_starts_with($currentPath, '/dashboard/subjects');
$isCommunity = str_starts_with($currentPath, '/dashboard/community');
$isKuppiSectionActive = $isKuppiRequested || $isMyKuppiRequests || $isKuppiScheduled || $isKuppiTimetable;
$isQuizSectionActive = $isQuizHub || $isMyQuizzes || $isMyQuizAnalytics;
$isGpaSectionActive = $isGpaCalculator || $isGpaAnalytics;
?>
<nav class="sidebar-nav">
    <div class="sidebar-section-label">Overview</div>
    <ul>
        <li><a href="/dashboard" class="<?= is_current_url('/dashboard') ? 'active' : '' ?>"><span class="sidebar-nav-icon"><?= ui_lucide_icon('home') ?></span><span>Dashboard</span></a></li>
        <li><a href="/dashboard/feed" class="<?= $isCentralFeed ? 'active' : '' ?>"><span class="sidebar-nav-icon"><?= ui_lucide_icon('newspaper') ?></span><span>Feed</span></a></li>
        <li><a href="/dashboard/subjects" class="<?= $isSubjects ? 'active' : '' ?>"><span class="sidebar-nav-icon"><?= ui_lucide_icon('book-open') ?></span><span>Subjects</span></a></li>
        <li class="sidebar-accordion-item">
            <details class="sidebar-accordion" <?= $isKuppiSectionActive ? 'open' : '' ?>>
                <summary class="sidebar-accordion-toggle <?= $isKuppiSectionActive ? 'is-active' : '' ?>">
                    <span class="sidebar-nav-icon"><?= ui_lucide_icon('calendar-plus') ?></span>
                    <span>Kuppi</span>
                    <span class="sidebar-accordion-caret"><?= ui_lucide_icon('chevron-down') ?></span>
                </summary>
                <ul class="sidebar-subnav">
                    <li><a href="/dashboard/kuppi" class="<?= $isKuppiRequested ? 'active' : '' ?>">Requested Sessions</a></li>
                    <li><a href="/my-kuppi-requests" class="<?= $isMyKuppiRequests ? 'active' : '' ?>">My Kuppi</a></li>
                    <li><a href="/dashboard/kuppi/scheduled" class="<?= $isKuppiScheduled ? 'active' : '' ?>">Scheduled Sessions</a></li>
                </ul>
            </details>
        </li>
        <li class="sidebar-accordion-item">
            <details class="sidebar-accordion" <?= $isQuizSectionActive ? 'open' : '' ?>>
                <summary class="sidebar-accordion-toggle <?= $isQuizSectionActive ? 'is-active' : '' ?>">
                    <span class="sidebar-nav-icon"><?= ui_lucide_icon('clipboard-check') ?></span>
                    <span>Quiz</span>
                    <span class="sidebar-accordion-caret"><?= ui_lucide_icon('chevron-down') ?></span>
                </summary>
                <ul class="sidebar-subnav">
                    <li><a href="/dashboard/quizzes" class="<?= $isQuizHub ? 'active' : '' ?>">Quiz Hub</a></li>
                    <li><a href="/my-quizzes" class="<?= $isMyQuizzes ? 'active' : '' ?>">My Quizzes</a></li>
                    <li><a href="/my-quiz-analytics" class="<?= $isMyQuizAnalytics ? 'active' : '' ?>">Quiz Analytics</a></li>
                </ul>
            </details>
        </li>
        <li><a href="/dashboard/community" class="<?= $isCommunity ? 'active' : '' ?>"><span class="sidebar-nav-icon"><?= ui_lucide_icon('message-square') ?></span><span>Community</span></a></li>
        <li><a href="/dashboard/gpa/analytics" class="<?= $isGpaSectionActive ? 'active' : '' ?>"><span class="sidebar-nav-icon"><?= ui_lucide_icon('line-chart') ?></span><span>GPA Analytics</span></a></li>
        <li><a href="/dashboard/kuppi/timetable" class="<?= $isKuppiTimetable ? 'active' : '' ?>"><span class="sidebar-nav-icon"><?= ui_lucide_icon('calendar-clock') ?></span><span>University Timetable</span></a></li>
    </ul>

    <div class="sidebar-section-label">Account</div>
    <ul>
        <li><a href="/dashboard/profile" class="<?= $isProfileSettings ? 'active' : '' ?>"><span class="sidebar-nav-icon"><?= ui_lucide_icon('user-cog') ?></span><span>Profile Settings</span></a></li>
    </ul>
</nav>
