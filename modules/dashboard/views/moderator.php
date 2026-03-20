<div class="dashboard-header">
    <h1>Moderator Dashboard</h1>
    <p>
        Welcome back, <?= e($user['name']) ?>!
        <span class="badge badge-warning">Moderator</span>
        <?php if (!empty($user['batch_id'])): ?>
            <span class="badge badge-info">Batch #<?= (int) $user['batch_id'] ?></span>
        <?php endif; ?>
    </p>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-number"><?= (int) $subject_count ?></div>
        <div class="stat-label">Subjects in My Batch</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?= (int) $pending_student_requests ?></div>
        <div class="stat-label">Pending Join Requests</div>
    </div>
    <div class="stat-card accent">
        <div class="stat-number"><a href="/subjects/create" style="color:inherit;text-decoration:none;">+</a></div>
        <div class="stat-label">Add New Subject</div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2>Batch Operations</h2>
        <a href="/moderator/join-requests" class="btn btn-sm btn-primary">Review Join Requests</a>
    </div>
    <div class="card-body">
        <p>Manage student access to your batch and keep session content updated.</p>
        <div class="quick-actions">
            <a href="/moderator/join-requests" class="btn btn-outline">Student Join Requests</a>
            <a href="/subjects" class="btn btn-outline">Manage Batch Subjects</a>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2>Recent Batch Subjects</h2>
        <a href="/subjects" class="btn btn-sm btn-primary">Manage All</a>
    </div>
    <div class="card-body">
        <?php if (empty($subjects)): ?>
            <p class="text-muted">No subjects in your batch yet. <a href="/subjects/create">Create one</a>.</p>
        <?php else: ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Subject Name</th>
                        <th>Credits</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($subjects as $subject): ?>
                        <tr>
                            <td><span class="badge"><?= e($subject['code']) ?></span></td>
                            <td><?= e($subject['name']) ?></td>
                            <td><?= (int) $subject['credits'] ?></td>
                            <td>
                                <a href="/subjects/<?= $subject['id'] ?>/edit" class="btn btn-sm btn-outline">Edit</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
