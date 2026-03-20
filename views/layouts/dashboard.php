<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — <?= e(config('app.name')) ?></title>
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="dashboard-body">
    <aside class="sidebar">
        <div class="sidebar-header">
            <a href="/dashboard" class="logo"><?= e(config('app.name')) ?></a>
            <span class="role-badge role-<?= e(user_role()) ?>"><?= ucfirst(e(user_role())) ?></span>
        </div>

        <?php
        $role = user_role();
        $sidebarFile = BASE_PATH . '/views/layouts/partials/sidebar_' . $role . '.php';
        if (file_exists($sidebarFile)) {
            require $sidebarFile;
        }
        ?>

        <div class="sidebar-footer">
            <div class="sidebar-user">
                <span class="user-name"><?= e(auth_user()['name']) ?></span>
                <span class="user-email"><?= e(auth_user()['email']) ?></span>
            </div>
            <a href="/logout" class="btn btn-sm btn-outline btn-block">Logout</a>
        </div>
    </aside>

    <main class="dashboard-main">
        <div class="dashboard-content">
            <?php if ($success = get_flash('success')): ?>
                <div class="alert alert-success"><?= e($success) ?></div>
            <?php endif; ?>
            <?= $content ?>
        </div>
    </main>
</body>
</html>
