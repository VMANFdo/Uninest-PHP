<div class="dashboard-header">
    <h1>Moderator Dashboard</h1>
    <p>
        Welcome back, <?= e($user['name']) ?>!
        <span class="badge badge-warning">Moderator</span>
        <?php if (!empty($batch['batch_code'])): ?>
            <span class="badge badge-info"><?= e($batch['batch_code']) ?></span>
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
        <h2>Invite Students</h2>
    </div>
    <div class="card-body">
        <?php if (!empty($invite_link) && !empty($invite_qr_url)): ?>
            <p>Share this invite link or QR code with students so the batch ID auto-fills during signup.</p>
            <div class="invite-grid">
                <div>
                    <p class="text-muted">
                        <strong>Batch ID:</strong> <span class="badge"><?= e($batch['batch_code']) ?></span>
                    </p>
                    <div class="invite-link-box">
                        <input type="text" id="invite-link-input" value="<?= e($invite_link) ?>" readonly>
                        <button type="button" class="btn btn-sm btn-primary" id="copy-invite-btn">Copy</button>
                    </div>
                    <a href="<?= e($invite_link) ?>" target="_blank" rel="noopener">Open invite link</a>
                </div>
                <div class="invite-qr">
                    <img src="<?= e($invite_qr_url) ?>" alt="Invite QR code for student signup">
                </div>
            </div>
        <?php else: ?>
            <p class="text-muted">Batch invite details will appear after admin approves your batch and assigns a batch ID.</p>
        <?php endif; ?>
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

<script>
    (function () {
        const input = document.getElementById('invite-link-input');
        const button = document.getElementById('copy-invite-btn');
        if (!input || !button) return;

        button.addEventListener('click', async function () {
            try {
                await navigator.clipboard.writeText(input.value);
                button.textContent = 'Copied';
                setTimeout(() => {
                    button.textContent = 'Copy';
                }, 1500);
            } catch (e) {
                input.focus();
                input.select();
                document.execCommand('copy');
                button.textContent = 'Copied';
                setTimeout(() => {
                    button.textContent = 'Copy';
                }, 1500);
            }
        });
    })();
</script>
