<nav class="sidebar-nav">
    <ul>
        <li><a href="/dashboard" class="<?= is_current_url('/dashboard') ? 'active' : '' ?>">📊 Dashboard</a></li>
        <li><a href="/moderator/join-requests" class="<?= is_current_url('/moderator/join-requests') ? 'active' : '' ?>">👥 Join Requests</a></li>
    </ul>
    <div class="sidebar-section-label">Subject Management</div>
    <ul>
        <li><a href="/subjects" class="<?= is_current_url('/subjects') ? 'active' : '' ?>">📚 My Batch Subjects</a></li>
        <li><a href="/subjects/create" class="<?= is_current_url('/subjects/create') ? 'active' : '' ?>">➕ New Subject</a></li>
    </ul>
</nav>
