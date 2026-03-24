<?php
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
?>
<nav class="sidebar-nav">
    <?php $isCoordinatorSubjects = str_starts_with($currentPath, '/coordinator/subjects'); ?>
    <?php $isResourceRequests = str_starts_with($currentPath, '/coordinator/resource-requests'); ?>
    <div class="sidebar-section-label">Overview</div>
    <ul>
        <li><a href="/dashboard" data-icon="DB" class="<?= is_current_url('/dashboard') ? 'active' : '' ?>"><span>Dashboard</span></a></li>
        <li><a href="/dashboard/subjects" data-icon="SB" class="<?= str_starts_with($currentPath, '/dashboard/subjects') ? 'active' : '' ?>"><span>Browse Subjects</span></a></li>
        <li><a href="/my-resources" data-icon="RS" class="<?= str_starts_with($currentPath, '/my-resources') ? 'active' : '' ?>"><span>My Resources</span></a></li>
    </ul>

    <div class="sidebar-section-label">Coordinator</div>
    <ul>
        <li><a href="/coordinator/subjects" data-icon="CP" class="<?= $isCoordinatorSubjects ? 'active' : '' ?>"><span>Manage Subjects</span></a></li>
        <li><a href="/coordinator/resource-requests" data-icon="RQ" class="<?= $isResourceRequests ? 'active' : '' ?>"><span>Resource Requests</span></a></li>
    </ul>
</nav>
