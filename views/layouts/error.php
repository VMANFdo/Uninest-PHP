<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title) ?> — <?= e(config('app.name')) ?></title>
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="error-page">
        <h1><?= $code ?></h1>
        <p><?= e($message) ?></p>
        <a href="/" class="btn btn-primary">Go Home</a>
    </div>
</body>
</html>
