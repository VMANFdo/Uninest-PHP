<div class="page-header">
    <div class="page-header-content">
        <p class="page-breadcrumb">Subjects / Topics / Resources</p>
        <h1><?= e($topic['title']) ?> Resources</h1>
        <p class="page-subtitle">
            Published learning materials for <strong><?= e($subject['code']) ?> - <?= e($subject['name']) ?></strong>.
        </p>
    </div>
    <div class="page-header-actions">
        <a href="/dashboard/subjects/<?= (int) $subject['id'] ?>/topics/<?= (int) $topic['id'] ?>/resources/create" class="btn btn-primary">+ Upload Resource</a>
        <a href="/dashboard/subjects/<?= (int) $subject['id'] ?>/topics" class="btn btn-outline">← Back to Topics</a>
    </div>
</div>

<?php if (empty($resources)): ?>
    <div class="card">
        <div class="card-body">
            <p class="text-muted">
                No published resources yet for this topic.
                <a href="/dashboard/subjects/<?= (int) $subject['id'] ?>/topics/<?= (int) $topic['id'] ?>/resources/create">Upload the first resource</a>.
            </p>
        </div>
    </div>
<?php else: ?>
    <div class="resource-grid">
        <?php foreach ($resources as $resource): ?>
            <?php
            $toneClass = ui_avatar_tone_class((string) (($resource['title'] ?? '') . '-' . ($resource['id'] ?? '')));
            $previewLabel = ($resource['source_type'] ?? '') === 'file'
                ? resources_file_extension_label((string) ($resource['file_name'] ?? ''), (string) ($resource['file_path'] ?? ''))
                : resources_link_host_label((string) ($resource['external_url'] ?? ''));
            ?>
            <a href="/dashboard/subjects/<?= (int) $subject['id'] ?>/topics/<?= (int) $topic['id'] ?>/resources/<?= (int) $resource['id'] ?>" class="resource-card-link" aria-label="Open resource <?= e($resource['title']) ?>">
                <article class="resource-card">
                    <div class="resource-card-thumb <?= e($toneClass) ?>">
                        <span class="resource-card-thumb-label"><?= e($previewLabel) ?></span>
                    </div>
                    <div class="resource-card-content">
                        <div class="resource-card-meta">
                            <span class="badge"><?= e(resources_category_display((string) $resource['category'], (string) ($resource['category_other'] ?? ''))) ?></span>
                            <span class="badge"><?= e(resources_source_label((string) $resource['source_type'])) ?></span>
                        </div>
                        <h3><?= e($resource['title']) ?></h3>
                        <?php if (!empty($resource['description'])): ?>
                            <p><?= e(substr((string) $resource['description'], 0, 100)) ?><?= strlen((string) $resource['description']) > 100 ? '...' : '' ?></p>
                        <?php else: ?>
                            <p class="text-muted">No description added yet.</p>
                        <?php endif; ?>
                        <div class="resource-card-footer">
                            <span class="badge">By <?= e($resource['uploader_name'] ?? 'Unknown') ?></span>
                            <span class="badge"><?= e(date('Y-m-d', strtotime((string) $resource['updated_at']))) ?></span>
                            <span class="badge"><?= e(resources_rating_summary_label((float) ($resource['average_rating'] ?? 0), (int) ($resource['rating_count'] ?? 0))) ?></span>
                            <span class="badge"><?= e(resources_comment_count_label((int) ($resource['comment_count'] ?? 0))) ?></span>
                        </div>
                    </div>
                </article>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
