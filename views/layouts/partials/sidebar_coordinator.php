<?php
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
?>
<nav class="sidebar-nav">
    <?php $isCoordinatorSubjects = str_starts_with($currentPath, '/coordinator/subjects'); ?>
    <div class="sidebar-section-label">Overview</div>
    <ul>
        <li><a href="/dashboard" data-icon="DB" class="<?= is_current_url('/dashboard') ? 'active' : '' ?>"><span>Dashboard</span></a></li>
        <li><a href="/dashboard/subjects" data-icon="SB" class="<?= str_starts_with($currentPath, '/dashboard/subjects') ? 'active' : '' ?>"><span>Browse Subjects</span></a></li>
    </ul>

    <div class="sidebar-section-label">Coordinator</div>
    <ul>
        <li><a href="/coordinator/subjects" data-icon="CP" class="<?= $isCoordinatorSubjects ? 'active' : '' ?>"><span>Manage Subjects</span></a></li>
    </ul>
</nav>
