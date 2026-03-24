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
            <?php
            $status = (string) ($subject['status'] ?? 'upcoming');
            $statusClass = match ($status) {
                'in_progress' => 'badge-info',
                'completed' => 'badge-warning',
                default => '',
            };
            $thumbnailTone = ui_avatar_tone_class((string) (($subject['code'] ?? '') . '-' . ($subject['name'] ?? '')));
            ?>
            <a href="/dashboard/subjects/<?= (int) $subject['id'] ?>/topics" class="subject-card-link" aria-label="Open topics for <?= e($subject['name']) ?>">
                <article class="subject-card subject-card--thumb">
                    <div class="subject-card-thumb <?= e($thumbnailTone) ?>">
                        <span class="subject-card-thumb-code"><?= e($subject['code']) ?></span>
                    </div>
                    <div class="subject-card-content">
                        <div class="subject-meta">
                            <span class="badge <?= e($statusClass) ?>"><?= e(subjects_status_label($status)) ?></span>
                            <span class="badge"><?= (int) $subject['credits'] ?> Credits</span>
                        </div>
                        <h3><?= e($subject['code']) ?> - <?= e($subject['name']) ?></h3>
                        <p class="subject-card-term">Academic year <?= (int) ($subject['academic_year'] ?? 1) ?> · Semester <?= (int) ($subject['semester'] ?? 1) ?></p>
                        <?php if (!empty($subject['description'])): ?>
                            <p class="subject-card-description"><?= e($subject['description']) ?></p>
                        <?php endif; ?>
                    </div>
                </article>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
