<div class="dashboard-header">
    <h1>Student Dashboard</h1>
    <p>Welcome back, <?= e($user['name']) ?>!</p>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-number"><?= count($subjects) ?></div>
        <div class="stat-label">Available Subjects</div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2>Available Subjects</h2>
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
                    <?php foreach (array_slice($subjects, 0, 5) as $subject): ?>
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
