<section class="dash-hero">
    <p class="dash-eyebrow">Coordinator Workspace</p>
    <h1>Oversee subject quality across moderator teams.</h1>
    <p class="dash-copy">Welcome back, <?= e($user['name']) ?>. Track subject consistency and align moderators on peer-learning standards.</p>
</section>

<section class="dash-kpi-grid">
    <article class="kpi-card">
        <span class="kpi-label">Available Subjects</span>
        <strong><?= count($subjects) ?></strong>
        <p>Subject inventory currently visible to coordinator role.</p>
    </article>
</section>

<section class="dash-panel">
    <header class="dash-panel-header">
        <h2>Coordinator Notes</h2>
    </header>
    <p class="text-muted">Use this space to review subject quality and coordinate changes with moderators before curriculum updates.</p>
</section>

<section class="dash-panel">
    <header class="dash-panel-header">
        <h2>All Subjects</h2>
        <a href="/dashboard/subjects" class="btn btn-sm btn-outline">View Full List</a>
    </header>
    <?php if (empty($subjects)): ?>
        <p class="text-muted">No subjects available yet.</p>
    <?php else: ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Subject</th>
                    <th>Credits</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($subjects as $subject): ?>
                    <?php
                    $avatarText = ui_initials((string) $subject['name']);
                    $avatarTone = ui_avatar_tone_class((string) (($subject['code'] ?? '') . '-' . ($subject['name'] ?? '')));
                    ?>
                    <tr>
                        <td><span class="badge"><?= e($subject['code']) ?></span></td>
                        <td>
                            <div class="table-identity">
                                <span class="table-avatar <?= e($avatarTone) ?>"><?= e($avatarText) ?></span>
                                <div class="table-identity-text">
                                    <strong><?= e($subject['name']) ?></strong>
                                </div>
                            </div>
                        </td>
                        <td><?= (int) $subject['credits'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</section>
