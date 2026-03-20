<div class="hero">
    <h1>Welcome to <?= e(config('app.name')) ?></h1>
    <p class="hero-subtitle">Your Learning Management System</p>
    <div class="hero-actions">
        <?php if (auth_check()): ?>
            <a href="/dashboard" class="btn btn-primary">Go to Dashboard</a>
        <?php else: ?>
            <a href="/login" class="btn btn-primary">Sign In</a>
            <a href="/register" class="btn btn-outline">Create Account</a>
        <?php endif; ?>
    </div>
</div>

<div class="features-grid">
    <div class="feature-card">
        <div class="feature-icon">📚</div>
        <h3>Browse Subjects</h3>
        <p>Access a wide range of subjects and course materials curated for your learning journey.</p>
    </div>
    <div class="feature-card">
        <div class="feature-icon">👥</div>
        <h3>Role-Based Access</h3>
        <p>Students, coordinators, moderators, and admins — each with tailored dashboards.</p>
    </div>
    <div class="feature-card">
        <div class="feature-icon">⚡</div>
        <h3>Simple & Scalable</h3>
        <p>Built with plain PHP and MySQL. No bloat, no complexity — just clean, fast code.</p>
    </div>
</div>
