<div class="auth-container">
    <div class="auth-card">
        <h2>Create Account</h2>
        <p class="auth-subtitle">Join <?= e(config('app.name')) ?></p>

        <?php if ($error = get_flash('error')): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="/register">
            <?= csrf_field() ?>

            <div class="form-group">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" value="<?= old('name') ?>" required autofocus>
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" value="<?= old('email') ?>" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required minlength="6">
            </div>

            <div class="form-group">
                <label for="password_confirmation">Confirm Password</label>
                <input type="password" id="password_confirmation" name="password_confirmation" required minlength="6">
            </div>

            <button type="submit" class="btn btn-primary btn-block">Create Account</button>
        </form>

        <p class="auth-link">
            Already have an account? <a href="/login">Sign in</a>
        </p>
    </div>
</div>
