<?php
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$isSubjectCreate = $currentPath === '/subjects/create';
$isSubjects = str_starts_with($currentPath, '/subjects') && !$isSubjectCreate;
$isStudentCreate = $currentPath === '/students/create';
$isStudents = str_starts_with($currentPath, '/students') && !$isStudentCreate;
$isBatches = str_starts_with($currentPath, '/admin/batches');
$isModerators = str_starts_with($currentPath, '/admin/moderators');
$isCommunityReports = str_starts_with($currentPath, '/dashboard/community/reports');
$isCommunityFeed = str_starts_with($currentPath, '/dashboard/community') && !$isCommunityReports;
$isKuppi = str_starts_with($currentPath, '/dashboard/kuppi') || str_starts_with($currentPath, '/my-kuppi-requests');
?>
<nav class="sidebar-nav">
    <div class="sidebar-section-label">Overview</div>
    <ul>
        <li><a href="/dashboard" class="<?= is_current_url('/dashboard') ? 'active' : '' ?>"><span class="sidebar-nav-icon"><?= ui_lucide_icon('home') ?></span><span>Dashboard</span></a></li>
    </ul>

    <div class="sidebar-section-label">Approvals</div>
    <ul>
        <li><a href="/admin/batch-requests" class="<?= is_current_url('/admin/batch-requests') ? 'active' : '' ?>"><span class="sidebar-nav-icon"><?= ui_lucide_icon('inbox') ?></span><span>Batch Requests</span></a></li>
        <li><a href="/admin/student-requests" class="<?= is_current_url('/admin/student-requests') ? 'active' : '' ?>"><span class="sidebar-nav-icon"><?= ui_lucide_icon('users') ?></span><span>Student Requests</span></a></li>
    </ul>

    <div class="sidebar-section-label">Content</div>
    <ul>
        <li><a href="/dashboard/community" class="<?= $isCommunityFeed ? 'active' : '' ?>"><span class="sidebar-nav-icon"><?= ui_lucide_icon('message-square') ?></span><span>Community Feed</span></a></li>
        <li><a href="/dashboard/kuppi" class="<?= $isKuppi ? 'active' : '' ?>"><span class="sidebar-nav-icon"><?= ui_lucide_icon('book-open') ?></span><span>Requested Kuppi</span></a></li>
        <li><a href="/saved-posts" class="<?= str_starts_with($currentPath, '/saved-posts') ? 'active' : '' ?>"><span class="sidebar-nav-icon"><?= ui_lucide_icon('bookmark') ?></span><span>Saved Posts</span></a></li>
        <li><a href="/dashboard/community/reports" class="<?= $isCommunityReports ? 'active' : '' ?>"><span class="sidebar-nav-icon"><?= ui_lucide_icon('flag') ?></span><span>Reports Queue</span></a></li>
        <li><a href="/subjects" class="<?= $isSubjects ? 'active' : '' ?>"><span class="sidebar-nav-icon"><?= ui_lucide_icon('layers') ?></span><span>Subjects</span></a></li>
        <li><a href="/subjects/create" class="<?= $isSubjectCreate ? 'active' : '' ?>"><span class="sidebar-nav-icon"><?= ui_lucide_icon('plus') ?></span><span>New Subject</span></a></li>
        <li><a href="/my-resources" class="<?= str_starts_with($currentPath, '/my-resources') ? 'active' : '' ?>"><span class="sidebar-nav-icon"><?= ui_lucide_icon('file-text') ?></span><span>My Resources</span></a></li>
        <li><a href="/students" class="<?= $isStudents ? 'active' : '' ?>"><span class="sidebar-nav-icon"><?= ui_lucide_icon('users') ?></span><span>Students</span></a></li>
        <li><a href="/students/create" class="<?= $isStudentCreate ? 'active' : '' ?>"><span class="sidebar-nav-icon"><?= ui_lucide_icon('user-plus') ?></span><span>New Student</span></a></li>
    </ul>

    <div class="sidebar-section-label">Provisioning</div>
    <ul>
        <li><a href="/admin/moderators" class="<?= $isModerators ? 'active' : '' ?>"><span class="sidebar-nav-icon"><?= ui_lucide_icon('shield') ?></span><span>Moderators</span></a></li>
        <li><a href="/admin/batches" class="<?= $isBatches ? 'active' : '' ?>"><span class="sidebar-nav-icon"><?= ui_lucide_icon('folder') ?></span><span>Batches</span></a></li>
    </ul>
</nav>
