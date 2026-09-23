<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
$id = intval($_GET['id'] ?? 0);
$r = $conn->query("SELECT * FROM imaging_reports WHERE id=$id")->fetch_assoc();
if (!$r) die("Record not found");
$patient_id = $r['patient_id'];
$p = $conn->query("SELECT * FROM patients WHERE id=$patient_id")->fetch_assoc();
$pageTitle = 'Edit Imaging Report';

$INVESTIGATIONS = [
    'X-ray KUB','USG Whole Abdomen','Uroflowmetry','Inguinoscrotal USG',
    'CT Scan Abdomen/Pelvis','CT Scan Chest','DTPA Renogram','Bone Scan',
    'MRI','RGU and MCU','Histopathology','Others'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nz = fn($v) => (isset($v) && trim((string)$v) !== '') ? trim((string)$v) : null;

    $file_path = $r['file_path'];
    $ftype     = $r['file_type'];

    if (!empty($_FILES['file']['name'])) {
        $ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg','jpeg','png','pdf'])) {
            if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);
            $fname = uniqid('img_') . '.' . $ext;
            if (move_uploaded_file($_FILES['file']['tmp_name'], UPLOAD_DIR . $fname)) {
                if ($file_path && file_exists(UPLOAD_DIR . $file_path)) @unlink(UPLOAD_DIR . $file_path);
                $file_path = $fname;
                $ftype     = $_FILES['file']['type'];
            }
        }
    }

    $inv = $_POST['investigation_name'] ?? '';
    if ($inv === 'Others' && !empty($_POST['investigation_name_specify'])) {
        $inv = 'Others: ' . trim($_POST['investigation_name_specify']);
    }

    $values = [
        $inv,
        $nz($_POST['investigation_date'] ?? ''),
        $nz($_POST['report_text'] ?? ''),
        $file_path,
        $ftype,
        $id,
    ];
    $types = '';
    foreach ($values as $v) $types .= is_int($v) ? 'i' : 's';

    $stmt = $conn->prepare("UPDATE imaging_reports SET 
        investigation_name=?, investigation_date=?, report_text=?, file_path=?, file_type=? 
        WHERE id=?");
    if (!$stmt) die("Prepare failed: " . $conn->error);
    $stmt->bind_param($types, ...$values);
    $stmt->execute();

    header("Location: imaging.php?patient_id=$patient_id&saved=1"); exit;
}
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';

// Handle "Others: xxx" case
$isOthers = strpos((string)$r['investigation_name'], 'Others:') === 0;
$baseName = $isOthers ? 'Others' : $r['investigation_name'];
$specifyName = $isOthers ? trim(substr($r['investigation_name'], 7)) : '';
?>
<div class="d-flex justify-content-between mb-3 flex-wrap gap-2">
    <div>
        <h5 class="mb-0"><i class="fas fa-edit text-primary me-2"></i>Edit Imaging Report</h5>
        <small class="text-muted"><?= htmlspecialchars($p['name']) ?> (<?= $p['uro_id'] ?>)</small>
    </div>
    <a href="imaging.php?patient_id=<?= $patient_id ?>" class="btn btn-outline-secondary btn-sm">← Back</a>
</div>

<form method="POST" enctype="multipart/form-data">
    <div class="card">
        <div class="card-body row g-3">

            <div class="col-md-6">
                <label class="form-label">Investigation</label>
                <select name="investigation_name" class="form-select" required
                        onchange="document.getElementById('inv_specify_wrap').style.display=this.value==='Others'?'block':'none'">
                    <?php foreach ($INVESTIGATIONS as $o): ?>
                        <option <?= $baseName === $o ? 'selected' : '' ?>><?= htmlspecialchars($o) ?></option>
                    <?php endforeach; ?>
                </select>
                <div id="inv_specify_wrap" class="mt-2" style="display:<?= $isOthers ? 'block' : 'none' ?>">
                    <input name="investigation_name_specify" class="form-control form-control-sm"
                           value="<?= htmlspecialchars($specifyName) ?>"
                           placeholder="Specify investigation name">
                </div>
            </div>

            <div class="col-md-3">
                <label class="form-label">Investigation Date</label>
                <input type="date" name="investigation_date" class="form-control"
                       value="<?= htmlspecialchars($r['investigation_date']) ?>">
            </div>

            <div class="col-md-3">
                <label class="form-label">Replace File (optional)</label>
                <input type="file" name="file" class="form-control" accept=".jpg,.jpeg,.png,.pdf">
            </div>

            <?php if ($r['file_path']): ?>
                <div class="col-12">
                    <small class="text-muted">Current file:
                        <a href="<?= BASE_URL . UPLOAD_URL . $r['file_path'] ?>" target="_blank">
                            <?= htmlspecialchars($r['file_path']) ?>
                        </a>
                    </small>
                </div>
            <?php endif; ?>

            <div class="col-12">
                <label class="form-label">Report Text</label>
                <textarea name="report_text" class="form-control" rows="6"><?= htmlspecialchars($r['report_text']) ?></textarea>
            </div>

        </div>
        <div class="card-footer text-end">
            <a href="imaging.php?patient_id=<?= $patient_id ?>" class="btn btn-outline-secondary me-2">Cancel</a>
            <button class="btn btn-primary px-4"><i class="fas fa-save me-1"></i> Update</button>
        </div>
    </div>
</form>

<?php include __DIR__ . '/../includes/footer.php'; ?>