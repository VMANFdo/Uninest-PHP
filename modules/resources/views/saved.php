<?php
$savedResources = (array) ($resources ?? []);
$currentUri = (string) ($current_uri ?? ($_SERVER['REQUEST_URI'] ?? '/saved-resources'));
?>

<div class="page-header">
    <div class="page-header-content">
        <p class="page-breadcrumb">Resources / Saved Resources</p>
        <h1>Saved Resources</h1>
        <p class="page-subtitle">Published resources you bookmarked for later.</p>
    </div>
    <div class="page-header-actions">
        <a href="/dashboard/subjects" class="btn btn-outline"><?= ui_lucide_icon('arrow-left') ?> Browse Resources</a>
    </div>
</div>

<?php if (empty($savedResources)): ?>
    <article class="community-post-card community-empty-state" style="margin-top: 12px;">
        <h3>No saved resources yet</h3>
        <p class="text-muted">Save resources from topic pages or resource details to find them quickly here.</p>
    </article>
<?php else: ?>
    <div class="resource-grid">
        <?php foreach ($savedResources as $resource): ?>
            <?php
            $resourceId = (int) ($resource['id'] ?? 0);
            $subjectId = (int) ($resource['subject_id'] ?? 0);
            $topicId = (int) ($resource['topic_id'] ?? 0);
            $openUrl = '/dashboard/subjects/' . $subjectId . '/topics/' . $topicId . '/resources/' . $resourceId;
            $savedAt = (string) ($resource['saved_at'] ?? $resource['updated_at'] ?? 'now');
            $toneClass = ui_avatar_tone_class((string) (($resource['title'] ?? '') . '-' . ($resource['id'] ?? '')));
            $previewLabel = ((string) ($resource['source_type'] ?? '') === 'file')
                ? resources_file_extension_label((string) ($resource['file_name'] ?? ''), (string) ($resource['file_path'] ?? ''))
                : resources_link_host_label((string) ($resource['external_url'] ?? ''));
            ?>
            <article class="resource-card">
                <a href="<?= e($openUrl) ?>" class="resource-card-link" aria-label="Open resource <?= e((string) ($resource['title'] ?? 'Resource')) ?>">
                    <div class="resource-card-thumb <?= e($toneClass) ?>">
                        <span class="resource-card-thumb-label"><?= e($previewLabel) ?></span>
                    </div>
                    <div class="resource-card-content">
                        <div class="resource-card-meta">
                            <span class="badge"><?= e((string) ($resource['subject_code'] ?? '')) ?></span>
                            <span class="badge"><?= e(resources_source_label((string) ($resource['source_type'] ?? 'file'))) ?></span>
                        </div>
                        <h3><?= e((string) ($resource['title'] ?? 'Untitled Resource')) ?></h3>
                        <?php if (!empty($resource['description'])): ?>
                            <p><?= e(substr((string) $resource['description'], 0, 100)) ?><?= strlen((string) $resource['description']) > 100 ? '...' : '' ?></p>
                        <?php else: ?>
                            <p class="text-muted">No description added yet.</p>
                        <?php endif; ?>
                        <div class="resource-card-footer">
                            <span class="badge">By <?= e((string) ($resource['uploader_name'] ?? 'Unknown')) ?></span>
                            <span class="badge">Saved <?= e(date('M d, Y', strtotime($savedAt))) ?></span>
                        </div>
                    </div>
                </a>
                <div class="resource-card-actions">
                    <a href="<?= e($openUrl) ?>" class="btn btn-sm btn-outline">Open</a>
                    <form method="POST" action="/resources/<?= $resourceId ?>/save/delete">
                        <?= csrf_field() ?>
                        <input type="hidden" name="return_to" value="<?= e($currentUri) ?>">
                        <button type="submit" class="btn btn-sm btn-outline">Unsave</button>
                    </form>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
