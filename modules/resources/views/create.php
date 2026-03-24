<div class="page-header">
    <div class="page-header-content">
        <p class="page-breadcrumb">Subjects / Topics / Resources</p>
        <h1>Upload Resource</h1>
        <p class="page-subtitle">
            Add a new resource to <strong><?= e($topic['title']) ?></strong> in <?= e($subject['code']) ?>.
        </p>
    </div>
    <div class="page-header-actions">
        <a href="/dashboard/subjects/<?= (int) $subject['id'] ?>/topics/<?= (int) $topic['id'] ?>/resources" class="btn btn-outline">← Back to Resources</a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="/dashboard/subjects/<?= (int) $subject['id'] ?>/topics/<?= (int) $topic['id'] ?>/resources" enctype="multipart/form-data">
            <?= csrf_field() ?>

            <div class="form-group">
                <label for="title">Resource Title</label>
                <input type="text" id="title" name="title" value="<?= old('title') ?>" maxlength="200" required>
            </div>

            <div class="form-group">
                <label for="description">Description (optional)</label>
                <textarea id="description" name="description" rows="4"><?= old('description') ?></textarea>
            </div>

            <div class="form-group">
                <label for="category">Category</label>
                <?php $selectedCategory = old('category'); ?>
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
                <input type="text" id="category_other" name="category_other" value="<?= old('category_other') ?>" maxlength="120" placeholder="Enter category">
            </div>

            <?php $selectedSource = old('source_type', 'file'); ?>
            <div class="form-group">
                <label for="source_type">Source Type</label>
                <select id="source_type" name="source_type" required>
                    <option value="file" <?= $selectedSource === 'file' ? 'selected' : '' ?>>File Upload</option>
                    <option value="link" <?= $selectedSource === 'link' ? 'selected' : '' ?>>External Link</option>
                </select>
                <small class="text-muted">Choose either file upload or link. Do not provide both.</small>
            </div>

            <div class="form-group" id="file-wrap" style="<?= $selectedSource === 'file' ? '' : 'display:none;' ?>">
                <label for="resource_file">Resource File</label>
                <input type="file" id="resource_file" name="resource_file" accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.txt,.zip,.jpg,.jpeg,.png">
                <small class="text-muted">Max size 25MB. Allowed: pdf, doc, docx, ppt, pptx, xls, xlsx, txt, zip, jpg, jpeg, png.</small>
            </div>

            <div class="form-group" id="link-wrap" style="<?= $selectedSource === 'link' ? '' : 'display:none;' ?>">
                <label for="external_url">Resource Link</label>
                <input type="url" id="external_url" name="external_url" value="<?= old('external_url') ?>" placeholder="https://example.com/resource">
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Submit Resource</button>
                <a href="/dashboard/subjects/<?= (int) $subject['id'] ?>/topics/<?= (int) $topic['id'] ?>/resources" class="btn btn-outline">Cancel</a>
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
