<?php
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$isKuppiScheduleFlow = $currentPath === '/dashboard/kuppi/schedule' || str_starts_with($currentPath, '/dashboard/kuppi/schedule/');
$isKuppiScheduled = str_starts_with($currentPath, '/dashboard/kuppi/scheduled');
$isKuppiRequested = (
    str_starts_with($currentPath, '/dashboard/kuppi')
    && !$isKuppiScheduleFlow
    && !$isKuppiScheduled
) || str_starts_with($currentPath, '/my-kuppi-requests');
?>
<nav class="sidebar-nav">
    <?php $isCoordinatorSubjects = str_starts_with($currentPath, '/coordinator/subjects'); ?>
    <?php $isResourceRequests = str_starts_with($currentPath, '/coordinator/resource-requests'); ?>
    <div class="sidebar-section-label">Overview</div>
    <ul>
        <li><a href="/dashboard" class="<?= is_current_url('/dashboard') ? 'active' : '' ?>"><span class="sidebar-nav-icon"><?= ui_lucide_icon('home') ?></span><span>Dashboard</span></a></li>
        <li><a href="/dashboard/subjects" class="<?= str_starts_with($currentPath, '/dashboard/subjects') ? 'active' : '' ?>"><span class="sidebar-nav-icon"><?= ui_lucide_icon('book-open') ?></span><span>Browse Subjects</span></a></li>
        <li><a href="/dashboard/community" class="<?= str_starts_with($currentPath, '/dashboard/community') ? 'active' : '' ?>"><span class="sidebar-nav-icon"><?= ui_lucide_icon('message-square') ?></span><span>Community Feed</span></a></li>
        <li><a href="/dashboard/kuppi" class="<?= $isKuppiRequested ? 'active' : '' ?>"><span class="sidebar-nav-icon"><?= ui_lucide_icon('book-open') ?></span><span>Requested Kuppi</span></a></li>
        <li><a href="/dashboard/kuppi/scheduled" class="<?= $isKuppiScheduled ? 'active' : '' ?>"><span class="sidebar-nav-icon"><?= ui_lucide_icon('calendar') ?></span><span>Scheduled Kuppi</span></a></li>
        <li><a href="/dashboard/kuppi/schedule" class="<?= $isKuppiScheduleFlow ? 'active' : '' ?>"><span class="sidebar-nav-icon"><?= ui_lucide_icon('calendar-plus') ?></span><span>Schedule Session</span></a></li>
        <li><a href="/saved-posts" class="<?= str_starts_with($currentPath, '/saved-posts') ? 'active' : '' ?>"><span class="sidebar-nav-icon"><?= ui_lucide_icon('bookmark') ?></span><span>Saved Posts</span></a></li>
        <li><a href="/my-posts" class="<?= str_starts_with($currentPath, '/my-posts') ? 'active' : '' ?>"><span class="sidebar-nav-icon"><?= ui_lucide_icon('file-text') ?></span><span>My Posts</span></a></li>
        <li><a href="/my-resources" class="<?= str_starts_with($currentPath, '/my-resources') ? 'active' : '' ?>"><span class="sidebar-nav-icon"><?= ui_lucide_icon('file-text') ?></span><span>My Resources</span></a></li>
    </ul>

    <div class="sidebar-section-label">Coordinator</div>
    <ul>
        <li><a href="/coordinator/subjects" class="<?= $isCoordinatorSubjects ? 'active' : '' ?>"><span class="sidebar-nav-icon"><?= ui_lucide_icon('layers') ?></span><span>Manage Subjects</span></a></li>
        <li><a href="/coordinator/resource-requests" class="<?= $isResourceRequests ? 'active' : '' ?>"><span class="sidebar-nav-icon"><?= ui_lucide_icon('inbox') ?></span><span>Resource Requests</span></a></li>
    </ul>
</nav>
