<div class="dashboard-header">
    <h1>Admin Dashboard</h1>
    <p>Welcome back, <?= e($user['name']) ?>! <span class="badge badge-danger">Administrator</span></p>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-number"><?= (int) $user_count ?></div>
        <div class="stat-label">Total Users</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?= (int) $subject_count ?></div>
        <div class="stat-label">Total Subjects</div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2>System Overview</h2>
    </div>
    <div class="card-body">
        <p>As an administrator, you have full access to the system. Use the sidebar to manage subjects and users.</p>

        <div class="quick-actions">
            <a href="/subjects" class="btn btn-primary">Manage Subjects</a>
            <a href="/subjects/create" class="btn btn-outline">Add New Subject</a>
        </div>
    </div>
</div>
