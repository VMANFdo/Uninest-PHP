<div class="page-header">
    <div class="page-header-content">
        <p class="page-breadcrumb">Student / Subjects</p>
        <h1>My Subjects</h1>
        <p class="page-subtitle">Explore the subjects available in your approved batch.</p>
    </div>
    <div class="page-header-actions">
        <a href="/dashboard" class="btn btn-outline">← Back to Dashboard</a>
    </div>
</div>

<?php if (empty($subjects)): ?>
    <div class="card">
        <div class="card-body">
            <p class="text-muted">No subjects available yet. Check back later!</p>
        </div>
    </div>
<?php else: ?>
    <div class="subjects-grid">
        <?php foreach ($subjects as $subject): ?>
            <div class="subject-card">
                <div class="subject-code"><?= e($subject['code']) ?></div>
                <h3><?= e($subject['name']) ?></h3>
                <?php if (!empty($subject['description'])): ?>
                    <p><?= e($subject['description']) ?></p>
                <?php endif; ?>
                <div class="subject-meta">
                    <span class="badge"><?= (int) $subject['credits'] ?> Credits</span>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
