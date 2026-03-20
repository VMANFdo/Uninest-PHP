<div class="auth-container">
    <div class="auth-card">
        <h2>Sign In</h2>
        <p class="auth-subtitle">Welcome back to <?= e(config('app.name')) ?></p>

        <?php if ($error = get_flash('error')): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="/login">
            <?= csrf_field() ?>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" value="<?= old('email') ?>" required autofocus>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>

            <button type="submit" class="btn btn-primary btn-block">Sign In</button>
        </form>

        <p class="auth-link">
            Don't have an account? <a href="/register">Create one</a>
        </p>
    </div>
</div>
