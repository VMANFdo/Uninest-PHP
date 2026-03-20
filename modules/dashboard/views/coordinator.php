<div class="dashboard-header">
    <h1>Coordinator Dashboard</h1>
    <p>Welcome back, <?= e($user['name']) ?>! <span class="badge badge-info">Subject Coordinator</span></p>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-number"><?= count($subjects) ?></div>
        <div class="stat-label">Available Subjects</div>
    </div>
</div>

<!-- Coordinator-specific panel -->
<div class="card">
    <div class="card-header">
        <h2>📋 Coordinator Panel</h2>
    </div>
    <div class="card-body">
        <p>As a subject coordinator, you can view all subjects and coordinate with moderators for updates.</p>
    </div>
</div>

<!-- Student-inherited view -->
<div class="card">
    <div class="card-header">
        <h2>All Subjects</h2>
        <a href="/dashboard/subjects" class="btn btn-sm btn-outline">View All</a>
    </div>
    <div class="card-body">
        <?php if (empty($subjects)): ?>
            <p class="text-muted">No subjects available yet.</p>
        <?php else: ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Subject Name</th>
                        <th>Credits</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($subjects as $subject): ?>
                        <tr>
                            <td><span class="badge"><?= e($subject['code']) ?></span></td>
                            <td><?= e($subject['name']) ?></td>
                            <td><?= (int) $subject['credits'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
