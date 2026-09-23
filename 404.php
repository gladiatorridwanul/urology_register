<?php require_once __DIR__ . '/config/database.php'; ?>
<!DOCTYPE html>
<html><head><meta charset="UTF-8"><title>404 Not Found</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="bg-light">
<div class="container text-center py-5">
    <h1 class="display-1 text-primary">404</h1>
    <p class="lead">The page you are looking for was not found.</p>
    <a href="<?= BASE_URL ?>index.php" class="btn btn-primary">← Back to Dashboard</a>
</div>
</body></html>