<nav class="sidebar-nav">
    <ul>
        <li><a href="/dashboard" class="<?= is_current_url('/dashboard') ? 'active' : '' ?>">📊 Dashboard</a></li>
    </ul>
    <div class="sidebar-section-label">Onboarding</div>
    <ul>
        <li><a href="/admin/batch-requests" class="<?= is_current_url('/admin/batch-requests') ? 'active' : '' ?>">🧾 Batch Requests</a></li>
        <li><a href="/admin/student-requests" class="<?= is_current_url('/admin/student-requests') ? 'active' : '' ?>">✅ Student Requests</a></li>
    </ul>
    <div class="sidebar-section-label">Subject Management</div>
    <ul>
        <li><a href="/subjects" class="<?= is_current_url('/subjects') ? 'active' : '' ?>">📚 All Subjects</a></li>
        <li><a href="/subjects/create" class="<?= is_current_url('/subjects/create') ? 'active' : '' ?>">➕ New Subject</a></li>
    </ul>
</nav>
