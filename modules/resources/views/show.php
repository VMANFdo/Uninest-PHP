<?php
$toneClass = ui_avatar_tone_class((string) (($resource['title'] ?? '') . '-' . ($resource['id'] ?? '')));
$isFileSource = (string) ($resource['source_type'] ?? '') === 'file';
$previewLabel = $isFileSource
    ? resources_file_extension_label((string) ($resource['file_name'] ?? ''), (string) ($resource['file_path'] ?? ''))
    : resources_link_host_label((string) ($resource['external_url'] ?? ''));
?>

<div class="page-header">
    <div class="page-header-content">
        <p class="page-breadcrumb">Subjects / Topics / Resources</p>
        <h1><?= e($resource['title']) ?></h1>
        <p class="page-subtitle">Detailed resource view for <strong><?= e($topic['title']) ?></strong>.</p>
    </div>
    <div class="page-header-actions">
        <a href="/dashboard/subjects/<?= (int) $subject['id'] ?>/topics/<?= (int) $topic['id'] ?>/resources/create" class="btn btn-primary">+ Upload Resource</a>
        <a href="/dashboard/subjects/<?= (int) $subject['id'] ?>/topics/<?= (int) $topic['id'] ?>/resources" class="btn btn-outline">← Back to Resources</a>
    </div>
</div>

<div class="resource-detail-layout">
    <article class="resource-detail-preview">
        <div class="resource-detail-thumb <?= e($toneClass) ?>">
            <span><?= e($previewLabel) ?></span>
        </div>
        <div class="resource-detail-preview-meta">
            <span class="badge"><?= e(resources_source_label((string) $resource['source_type'])) ?></span>
            <span class="badge"><?= e(resources_category_display((string) $resource['category'], (string) ($resource['category_other'] ?? ''))) ?></span>
        </div>
    </article>

    <article class="card resource-detail-content-card">
        <div class="card-body">
            <?php if (!empty($resource['description'])): ?>
                <p><?= nl2br(e((string) $resource['description'])) ?></p>
            <?php else: ?>
                <p class="text-muted">No description added for this resource.</p>
            <?php endif; ?>

            <div class="resource-detail-meta-grid">
                <div>
                    <small class="text-muted">Uploaded By</small>
                    <strong><?= e($resource['uploader_name'] ?? 'Unknown') ?></strong>
                </div>
                <div>
                    <small class="text-muted">Created</small>
                    <strong><?= e(date('Y-m-d H:i', strtotime((string) $resource['created_at']))) ?></strong>
                </div>
                <div>
                    <small class="text-muted">Updated</small>
                    <strong><?= e(date('Y-m-d H:i', strtotime((string) $resource['updated_at']))) ?></strong>
                </div>
            </div>

            <div class="form-actions">
                <?php if ($isFileSource): ?>
                    <a href="/resources/<?= (int) $resource['id'] ?>/download" class="btn btn-primary">Download File</a>
                    <span class="text-muted">
                        <?= e((string) ($resource['file_name'] ?? 'File')) ?>
                        (<?= e(resources_format_file_size((int) ($resource['file_size'] ?? 0))) ?>)
                    </span>
                <?php else: ?>
                    <a href="<?= e((string) $resource['external_url']) ?>" target="_blank" rel="noopener" class="btn btn-primary">Open Link</a>
                    <span class="text-muted"><?= e((string) $resource['external_url']) ?></span>
                <?php endif; ?>
            </div>
        </div>
    </article>
</div>

<?php if (resources_can_embed_in_iframe($resource)): ?>
    <div class="card resource-embed-card">
        <div class="card-body">
            <h3>Preview</h3>
            <?php if ($isFileSource): ?>
                <iframe
                    class="resource-embed-frame"
                    src="/resources/<?= (int) $resource['id'] ?>/inline"
                    title="Resource preview: <?= e($resource['title']) ?>"
                    loading="lazy"></iframe>
            <?php else: ?>
                <iframe
                    class="resource-embed-frame"
                    src="<?= e((string) $resource['external_url']) ?>"
                    title="Resource link preview: <?= e($resource['title']) ?>"
                    loading="lazy"></iframe>
                <p class="text-muted">If this website blocks embedding, use the <strong>Open Link</strong> button above.</p>
            <?php endif; ?>
        </div>
    </div>
<?php else: ?>
    <div class="card resource-embed-empty">
        <div class="card-body">
            <p class="text-muted">Inline preview is not available for this resource type. Use the action above to open or download it.</p>
        </div>
    </div>
<?php endif; ?>
