<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
$patient_id = intval($_GET['patient_id'] ?? 0);
$p = $conn->query("SELECT * FROM patients WHERE id=$patient_id")->fetch_assoc();
if (!$p) die("Patient not found");
$pageTitle = 'Imaging / Reports';

$INVESTIGATIONS = [
    'X-ray KUB','USG Whole Abdomen','Uroflowmetry','Inguinoscrotal USG',
    'CT Scan Abdomen/Pelvis','CT Scan Chest','DTPA Renogram','Bone Scan',
    'MRI','RGU and MCU','Histopathology','Others'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nz = fn($v) => (isset($v) && trim((string)$v) !== '') ? trim((string)$v) : null;

    $file_path = null; $ftype = null;
    if (!empty($_FILES['file']['name'])) {
        $ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg','jpeg','png','pdf'])) {
            if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);
            $fname = uniqid('img_') . '.' . $ext;
            if (move_uploaded_file($_FILES['file']['tmp_name'], UPLOAD_DIR . $fname)) {
                $file_path = $fname;
                $ftype = $_FILES['file']['type'];
            }
        }
    }

    $inv = $_POST['investigation_name'] ?? '';
    if ($inv === 'Others' && !empty($_POST['investigation_name_specify'])) {
        $inv = 'Others: ' . trim($_POST['investigation_name_specify']);
    }

    $values = [
        $patient_id, $inv,
        $nz($_POST['investigation_date'] ?? ''),
        $nz($_POST['report_text'] ?? ''),
        $file_path, $ftype,
        $_SESSION['user_id'],
    ];
    $types = '';
    foreach ($values as $v) $types .= is_int($v) ? 'i' : 's';

    $stmt = $conn->prepare("INSERT INTO imaging_reports 
        (patient_id, investigation_name, investigation_date, report_text, file_path, file_type, created_by) 
        VALUES (?,?,?,?,?,?,?)");
    if (!$stmt) die("Prepare failed: " . $conn->error);
    $stmt->bind_param($types, ...$values);
    $stmt->execute();

    header("Location: imaging.php?patient_id=$patient_id&saved=1"); exit;
}
$records = $conn->query("SELECT * FROM imaging_reports WHERE patient_id=$patient_id ORDER BY id DESC");
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="d-flex justify-content-between mb-3 flex-wrap gap-2">
    <div>
        <h5 class="mb-0"><i class="fas fa-x-ray text-primary me-2"></i>Imaging / Reports / Document Upload</h5>
        <small class="text-muted"><?= htmlspecialchars($p['name']) ?> (<?= $p['uro_id'] ?>) — accepts JPG / JPEG / PNG / PDF</small>
    </div>
    <a href="../patients/view.php?id=<?= $patient_id ?>" class="btn btn-outline-secondary btn-sm">← Back</a>
</div>

<?php if (isset($_GET['saved'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle me-1"></i> Report saved.
        <button class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data">
    <div class="card mb-3">
        <div class="card-header"><i class="fas fa-plus-circle me-2 text-primary"></i>Add Investigation Report</div>
        <div class="card-body row g-3">
            <div class="col-md-6">
                <label class="form-label">Investigation <span class="text-danger">*</span></label>
                <select name="investigation_name" class="form-select" required onchange="document.getElementById('inv_specify_wrap').style.display=this.value==='Others'?'block':'none'">
                    <option value="">— Select —</option>
                    <?php foreach ($INVESTIGATIONS as $o): ?><option><?= htmlspecialchars($o) ?></option><?php endforeach; ?>
                </select>
                <div id="inv_specify_wrap" class="mt-2" style="display:none">
                    <input name="investigation_name_specify" class="form-control form-control-sm" placeholder="Specify investigation name">
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label">Investigation Date</label>
                <input type="date" name="investigation_date" class="form-control" value="<?= date('Y-m-d') ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Upload File</label>
                <input type="file" name="file" class="form-control" accept=".jpg,.jpeg,.png,.pdf">
                <small class="text-muted">JPG / JPEG / PNG / PDF</small>
            </div>
            <div class="col-12">
                <label class="form-label">Report (free text)</label>
                <textarea name="report_text" class="form-control" rows="4"></textarea>
            </div>
        </div>
        <div class="card-footer text-end">
            <button class="btn btn-primary px-4"><i class="fas fa-save me-1"></i> Save Report</button>
        </div>
    </div>
</form>

<!-- ================= SAVED RECORDS — FULL DETAIL ================= -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="fas fa-list me-2 text-primary"></i>Uploaded Reports (<?= $records->num_rows ?>)</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle full-rec-table">
            <thead>
                <tr>
                    <th style="width:40px">#</th>
                    <th>Date</th>
                    <th>Investigation</th>
                    <th>Report Text (Full)</th>
                    <th>File Type</th>
                    <th style="width:120px">File</th>
                    <th>Uploaded</th>
                    <th style="width:100px">Action</th>
                </tr>
            </thead>
            <tbody>
            <?php $i = 1; while ($r = $records->fetch_assoc()): ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td><?= $r['investigation_date'] ?: '—' ?></td>
                    <td><?= htmlspecialchars($r['investigation_name'] ?: '—') ?></td>
                    <td><?= nl2br(htmlspecialchars($r['report_text'] ?: '—')) ?></td>
                    <td><?= htmlspecialchars($r['file_type'] ?: '—') ?></td>
                    <td>
                        <?php if ($r['file_path']): ?>
                            <a href="<?= BASE_URL . UPLOAD_URL . $r['file_path'] ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-eye"></i> View
                            </a>
                        <?php else: ?>
                            <span class="text-muted small">No file</span>
                        <?php endif; ?>
                    </td>
                    <td><?= date('d-m-Y H:i', strtotime($r['created_at'])) ?></td>
                    <?= rowActions('imaging', $r['id']) ?>
                </tr>
            <?php endwhile; if ($i === 1): ?>
                <tr><td colspan="8" class="text-center text-muted py-3">No records yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>

<style>
.full-rec-table { font-size: 12px; }
.full-rec-table th { white-space: nowrap; font-size: 11px; background: #f1f5f9; }
.full-rec-table td { vertical-align: top; max-width: 400px; }
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>