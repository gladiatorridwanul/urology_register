<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    header("Location: index.php");
    exit;
}

// Fetch patient first (for confirmation message + file cleanup)
$p = $conn->query("SELECT id, name, uro_id FROM patients WHERE id=$id")->fetch_assoc();

if (!$p) {
    header("Location: index.php?error=notfound");
    exit;
}

// ---------- Confirmation step (GET without confirm) ----------
if (!isset($_GET['confirm'])) {
    include __DIR__ . '/../includes/header.php';
    include __DIR__ . '/../includes/sidebar.php';
    ?>
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card border-danger">
                <div class="card-header bg-danger text-white">
                    <i class="fas fa-exclamation-triangle me-1"></i> Confirm Delete
                </div>
                <div class="card-body text-center py-4">
                    <i class="fas fa-user-slash text-danger" style="font-size:64px"></i>
                    <h5 class="mt-3">Delete this patient?</h5>
                    <p class="mb-1"><strong><?= htmlspecialchars($p['name']) ?></strong></p>
                    <p class="text-muted small"><?= htmlspecialchars($p['uro_id']) ?></p>
                    <div class="alert alert-warning small">
                        <i class="fas fa-info-circle me-1"></i>
                        This will permanently delete the patient and <strong>all their records</strong>
                        (chief complaints, history, exams, diagnosis, labs, imaging, operations, discharge summaries, follow-ups).
                        <br><strong>This action cannot be undone.</strong>
                    </div>
                    <div class="d-flex gap-2 justify-content-center">
                        <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
                        <a href="delete.php?id=<?= $id ?>&confirm=1" class="btn btn-danger">
                            <i class="fas fa-trash me-1"></i> Yes, Delete Permanently
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
    include __DIR__ . '/../includes/footer.php';
    exit;
}

// ---------- Perform delete (with confirm) ----------
// Clean up uploaded files first
$files = $conn->query("SELECT file_path FROM imaging_reports WHERE patient_id=$id AND file_path IS NOT NULL");
while ($f = $files->fetch_assoc()) {
    if (!empty($f['file_path']) && file_exists(UPLOAD_DIR . $f['file_path'])) {
        @unlink(UPLOAD_DIR . $f['file_path']);
    }
}
$files = $conn->query("SELECT file_path FROM followup WHERE patient_id=$id AND file_path IS NOT NULL");
while ($f = $files->fetch_assoc()) {
    if (!empty($f['file_path']) && file_exists(UPLOAD_DIR . $f['file_path'])) {
        @unlink(UPLOAD_DIR . $f['file_path']);
    }
}

// Delete patient (module rows cascade via FK ON DELETE CASCADE)
$stmt = $conn->prepare("DELETE FROM patients WHERE id=?");
$stmt->bind_param('i', $id);
$stmt->execute();

header("Location: index.php?deleted=1");
exit;
?>