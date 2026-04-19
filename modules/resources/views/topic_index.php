<?php
$canSaveResources = !empty($can_save_resources);
$currentUri = (string) ($current_uri ?? ($_SERVER['REQUEST_URI'] ?? ''));
?>

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
        <a href="/dashboard/subjects/<?= (int) $subject['id'] ?>/topics" class="btn btn-outline"><?= ui_lucide_icon('arrow-left') ?> Back to Topics</a>
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
            $resourceId = (int) ($resource['id'] ?? 0);
            $detailUrl = '/dashboard/subjects/' . (int) $subject['id'] . '/topics/' . (int) $topic['id'] . '/resources/' . $resourceId;
            $isSaved = (int) ($resource['is_saved_by_viewer'] ?? 0) === 1;
            $isFileSource = (string) ($resource['source_type'] ?? '') === 'file';
            $hasDownload = $isFileSource && trim((string) ($resource['file_path'] ?? '')) !== '';
            $hasOpenLink = !$isFileSource && !empty($resource['external_url']);
            $toneClass = ui_avatar_tone_class((string) (($resource['title'] ?? '') . '-' . ($resource['id'] ?? '')));
            $previewLabel = $isFileSource
                ? resources_file_extension_label((string) ($resource['file_name'] ?? ''), (string) ($resource['file_path'] ?? ''))
                : resources_link_host_label((string) ($resource['external_url'] ?? ''));
            ?>
            <article class="resource-card">
                <a href="<?= e($detailUrl) ?>" class="resource-card-link" aria-label="Open resource <?= e((string) ($resource['title'] ?? 'Resource')) ?>">
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
                </a>
                <div class="resource-card-actions">
                    <div class="resource-card-actions-left">
                        <?php if ($hasDownload): ?>
                            <a href="/resources/<?= $resourceId ?>/download" class="btn btn-sm btn-outline">Download</a>
                        <?php elseif ($hasOpenLink): ?>
                            <a href="<?= e((string) $resource['external_url']) ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline">Open Link</a>
                        <?php endif; ?>
                    </div>
                    <?php if ($canSaveResources): ?>
                        <form method="POST" action="/resources/<?= $resourceId ?>/save/<?= $isSaved ? 'delete' : 'create' ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="return_to" value="<?= e($currentUri) ?>">
                            <button type="submit" class="btn btn-sm <?= $isSaved ? 'btn-outline' : 'btn-primary' ?>">
                                <?= $isSaved ? 'Saved' : 'Save' ?>
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
