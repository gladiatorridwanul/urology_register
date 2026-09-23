<?php require_once __DIR__ . '/config/database.php'; ?>
<!DOCTYPE html>
<html><head><meta charset="UTF-8"><title>403 Forbidden</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="bg-light">
<div class="container text-center py-5">
    <h1 class="display-1 text-danger">403</h1>
    <p class="lead">You don't have permission to access this resource.</p>
    <a href="<?= BASE_URL ?>index.php" class="btn btn-primary">← Back to Dashboard</a>
</div>
</body></html>