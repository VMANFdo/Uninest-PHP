<?php
$resourceStatus = (string) ($resource['status'] ?? 'pending');
$updateStatus = (string) ($update_request['status'] ?? '');
$selectedCategory = old('category', (string) ($form_resource['category'] ?? ''));
$selectedSourceType = old('source_type', (string) ($form_resource['source_type'] ?? 'file'));
?>

<div class="page-header">
    <div class="page-header-content">
        <p class="page-breadcrumb">Resources / My Resources</p>
        <h1>Edit Resource</h1>
        <p class="page-subtitle">Update your uploaded resource details.</p>
    </div>
    <div class="page-header-actions">
        <a href="/my-resources" class="btn btn-outline">← Back to My Resources</a>
    </div>
</div>

<div class="card" style="margin-bottom: 1rem;">
    <div class="card-body">
        <p>
            <strong>Current Status:</strong>
            <span class="badge <?= e(resources_status_badge_class($resourceStatus)) ?>"><?= e(resources_status_label($resourceStatus)) ?></span>
            <?php if ($updateStatus !== ''): ?>
                <span class="badge <?= e(resources_update_status_badge_class($updateStatus)) ?>"><?= e(resources_update_status_label($updateStatus)) ?></span>
            <?php endif; ?>
        </p>
        <p class="text-muted">
            Subject: <?= e($resource['subject_code']) ?> — Topic: <?= e($resource['topic_title']) ?>
        </p>
        <?php if (!empty($resource['rejection_reason']) && $resourceStatus === 'rejected'): ?>
            <p class="text-muted"><strong>Rejection Reason:</strong> <?= e((string) $resource['rejection_reason']) ?></p>
        <?php endif; ?>
        <?php if ($updateStatus === 'rejected' && !empty($update_request['rejection_reason'])): ?>
            <p class="text-muted"><strong>Update Rejection Reason:</strong> <?= e((string) $update_request['rejection_reason']) ?></p>
        <?php endif; ?>
        <?php if ($requires_approval): ?>
            <p class="text-muted">As a student, updates to published resources require coordinator approval. Published content stays live until approval.</p>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="/my-resources/<?= (int) $resource['id'] ?>" enctype="multipart/form-data">
            <?= csrf_field() ?>

            <div class="form-group">
                <label for="title">Resource Title</label>
                <input type="text" id="title" name="title" value="<?= old('title', (string) ($form_resource['title'] ?? '')) ?>" maxlength="200" required>
            </div>

            <div class="form-group">
                <label for="description">Description (optional)</label>
                <textarea id="description" name="description" rows="4"><?= old('description', (string) ($form_resource['description'] ?? '')) ?></textarea>
            </div>

            <div class="form-group">
                <label for="category">Category</label>
                <select id="category" name="category" required>
                    <option value="">Select category</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= e($category) ?>" <?= $selectedCategory === $category ? 'selected' : '' ?>>
                            <?= e($category) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group" id="category-other-wrap" style="<?= $selectedCategory === 'Other' ? '' : 'display:none;' ?>">
                <label for="category_other">Other Category</label>
                <input type="text" id="category_other" name="category_other" value="<?= old('category_other', (string) ($form_resource['category_other'] ?? '')) ?>" maxlength="120">
            </div>

            <div class="form-group">
                <label for="source_type">Source Type</label>
                <select id="source_type" name="source_type" required>
                    <option value="file" <?= $selectedSourceType === 'file' ? 'selected' : '' ?>>File Upload</option>
                    <option value="link" <?= $selectedSourceType === 'link' ? 'selected' : '' ?>>External Link</option>
                </select>
                <small class="text-muted">Use exactly one source type at a time.</small>
            </div>

            <div class="form-group" id="file-wrap" style="<?= $selectedSourceType === 'file' ? '' : 'display:none;' ?>">
                <label for="resource_file">Resource File</label>
                <input type="file" id="resource_file" name="resource_file" accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.txt,.zip,.jpg,.jpeg,.png">
                <?php if (!empty($form_resource['file_name'])): ?>
                    <small class="text-muted">Current file: <?= e((string) $form_resource['file_name']) ?></small>
                <?php endif; ?>
            </div>

            <div class="form-group" id="link-wrap" style="<?= $selectedSourceType === 'link' ? '' : 'display:none;' ?>">
                <label for="external_url">Resource Link</label>
                <input type="url" id="external_url" name="external_url" value="<?= old('external_url', (string) ($form_resource['external_url'] ?? '')) ?>" placeholder="https://example.com/resource">
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <a href="/my-resources" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
    (function () {
        const category = document.getElementById('category');
        const categoryOtherWrap = document.getElementById('category-other-wrap');
        const sourceType = document.getElementById('source_type');
        const fileWrap = document.getElementById('file-wrap');
        const linkWrap = document.getElementById('link-wrap');

        function toggleCategoryOther() {
            if (!category || !categoryOtherWrap) return;
            categoryOtherWrap.style.display = category.value === 'Other' ? '' : 'none';
        }

        function toggleSourceFields() {
            if (!sourceType || !fileWrap || !linkWrap) return;
            const isFile = sourceType.value === 'file';
            fileWrap.style.display = isFile ? '' : 'none';
            linkWrap.style.display = isFile ? 'none' : '';
        }

        if (category) {
            category.addEventListener('change', toggleCategoryOther);
            toggleCategoryOther();
        }

        if (sourceType) {
            sourceType.addEventListener('change', toggleSourceFields);
            toggleSourceFields();
        }
    })();
</script>
