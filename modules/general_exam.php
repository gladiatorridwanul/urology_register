<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
$patient_id = intval($_GET['patient_id'] ?? 0);
$p = $conn->query("SELECT * FROM patients WHERE id=$patient_id")->fetch_assoc();
if (!$p) die("Patient not found");
$pageTitle = 'General Examination';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nz = fn($v) => (isset($v) && trim((string)$v) !== '') ? trim((string)$v) : null;

    $anaemia  = $_POST['anaemia']  ?? 'Absent';
    $jaundice = $_POST['jaundice'] ?? 'Absent';
    $edema    = $_POST['edema']    ?? 'Absent';

    $anaemia_details  = ($anaemia  === 'Present') ? $nz($_POST['anaemia_details']  ?? '') : null;
    $jaundice_details = ($jaundice === 'Present') ? $nz($_POST['jaundice_details'] ?? '') : null;
    $edema_details    = ($edema    === 'Present') ? $nz($_POST['edema_details']    ?? '') : null;

    $values = [
        $patient_id, $anaemia, $anaemia_details,
        $jaundice, $jaundice_details,
        $edema, $edema_details,
        $nz($_POST['pulse'] ?? ''),
        $nz($_POST['bp'] ?? ''),
        $nz($_POST['temperature'] ?? ''),
        $nz($_POST['lymph_node'] ?? ''),
        $nz($_POST['others'] ?? ''),
        $_SESSION['user_id'],
    ];
    $types = '';
    foreach ($values as $v) $types .= is_int($v) ? 'i' : 's';

    $stmt = $conn->prepare("INSERT INTO general_exam 
        (patient_id, anaemia, anaemia_details, jaundice, jaundice_details, edema, edema_details, 
         pulse, bp, temperature, lymph_node, others, created_by) 
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)");
    if (!$stmt) die("Prepare failed: " . $conn->error);
    $stmt->bind_param($types, ...$values);
    $stmt->execute();

    header("Location: general_exam.php?patient_id=$patient_id&saved=1"); exit;
}
$records = $conn->query("SELECT * FROM general_exam WHERE patient_id=$patient_id ORDER BY id DESC");
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="d-flex justify-content-between mb-3 flex-wrap gap-2">
    <div>
        <h5 class="mb-0"><i class="fas fa-stethoscope text-primary me-2"></i>General Examination</h5>
        <small class="text-muted"><?= htmlspecialchars($p['name']) ?> (<?= $p['uro_id'] ?>)</small>
    </div>
    <a href="../patients/view.php?id=<?= $patient_id ?>" class="btn btn-outline-secondary btn-sm">← Back</a>
</div>

<?php if (isset($_GET['saved'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle me-1"></i> General examination saved.
        <button class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<form method="POST">
    <div class="card mb-3">
        <div class="card-header"><i class="fas fa-plus-circle me-2 text-primary"></i>Add Examination</div>
        <div class="card-body">
            <div class="ge-block"><div class="ge-row">
                <div class="ge-label"><i class="fas fa-tint text-primary"></i> Anaemia</div>
                <div class="ge-input">
                    <select name="anaemia" class="form-select form-select-sm" style="max-width:200px" onchange="document.getElementById('anaemia_details_wrap').style.display=this.value==='Present'?'block':'none'">
                        <option>Absent</option><option>Present</option>
                    </select>
                    <div id="anaemia_details_wrap" class="mt-2" style="display:none">
                        <input name="anaemia_details" class="form-control form-control-sm">
                    </div>
                </div>
            </div></div>
            <div class="ge-block"><div class="ge-row">
                <div class="ge-label"><i class="fas fa-circle text-warning"></i> Jaundice</div>
                <div class="ge-input">
                    <select name="jaundice" class="form-select form-select-sm" style="max-width:200px" onchange="document.getElementById('jaundice_details_wrap').style.display=this.value==='Present'?'block':'none'">
                        <option>Absent</option><option>Present</option>
                    </select>
                    <div id="jaundice_details_wrap" class="mt-2" style="display:none">
                        <input name="jaundice_details" class="form-control form-control-sm">
                    </div>
                </div>
            </div></div>
            <div class="ge-block"><div class="ge-row">
                <div class="ge-label"><i class="fas fa-water text-primary"></i> Edema</div>
                <div class="ge-input">
                    <select name="edema" class="form-select form-select-sm" style="max-width:200px" onchange="document.getElementById('edema_details_wrap').style.display=this.value==='Present'?'block':'none'">
                        <option>Absent</option><option>Present</option>
                    </select>
                    <div id="edema_details_wrap" class="mt-2" style="display:none">
                        <input name="edema_details" class="form-control form-control-sm">
                    </div>
                </div>
            </div></div>
            <div class="ge-block"><div class="ge-row">
                <div class="ge-label"><i class="fas fa-heartbeat text-danger"></i> Pulse / BP / Temperature</div>
                <div class="ge-input"><div class="row g-2">
                    <div class="col-md-4"><label class="form-label small mb-0">Pulse</label><input type="number" name="pulse" class="form-control form-control-sm"></div>
                    <div class="col-md-4"><label class="form-label small mb-0">BP</label><input name="bp" class="form-control form-control-sm" placeholder="120/80"></div>
                    <div class="col-md-4"><label class="form-label small mb-0">Temperature</label><input name="temperature" class="form-control form-control-sm"></div>
                </div></div>
            </div></div>
            <div class="ge-block"><div class="ge-row">
                <div class="ge-label"><i class="fas fa-project-diagram text-primary"></i> Lymph Node Status</div>
                <div class="ge-input"><input name="lymph_node" class="form-control form-control-sm"></div>
            </div></div>
            <div class="ge-block"><div class="ge-row">
                <div class="ge-label"><i class="fas fa-ellipsis-h text-primary"></i> Others</div>
                <div class="ge-input"><textarea name="others" class="form-control form-control-sm" rows="2"></textarea></div>
            </div></div>
        </div>
        <div class="card-footer text-end">
            <button class="btn btn-primary px-4"><i class="fas fa-save me-1"></i> Save Examination</button>
        </div>
    </div>
</form>

<!-- ================= SAVED RECORDS — FULL DETAIL ================= -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="fas fa-list me-2 text-primary"></i>Saved Records (<?= $records->num_rows ?>)</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle full-rec-table">
            <thead>
                <tr>
                    <th style="width:40px">#</th>
                    <th>Date</th>
                    <th>Anaemia</th>
                    <th>Anaemia Details</th>
                    <th>Jaundice</th>
                    <th>Jaundice Details</th>
                    <th>Edema</th>
                    <th>Edema Details</th>
                    <th>Pulse</th>
                    <th>BP</th>
                    <th>Temp</th>
                    <th>Lymph Node</th>
                    <th>Others</th>
                    <th style="width:100px">Action</th>
                </tr>
            </thead>
            <tbody>
            <?php $i = 1; while ($r = $records->fetch_assoc()): ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td><?= date('d-m-Y', strtotime($r['created_at'])) ?></td>
                    <td><?= htmlspecialchars($r['anaemia']) ?></td>
                    <td><?= htmlspecialchars($r['anaemia_details'] ?: '—') ?></td>
                    <td><?= htmlspecialchars($r['jaundice']) ?></td>
                    <td><?= htmlspecialchars($r['jaundice_details'] ?: '—') ?></td>
                    <td><?= htmlspecialchars($r['edema']) ?></td>
                    <td><?= htmlspecialchars($r['edema_details'] ?: '—') ?></td>
                    <td><?= htmlspecialchars($r['pulse'] ?: '—') ?></td>
                    <td><?= htmlspecialchars($r['bp'] ?: '—') ?></td>
                    <td><?= htmlspecialchars($r['temperature'] ?: '—') ?></td>
                    <td><?= htmlspecialchars($r['lymph_node'] ?: '—') ?></td>
                    <td><?= nl2br(htmlspecialchars($r['others'] ?: '—')) ?></td>
                    <?= rowActions('general_exam', $r['id']) ?>
                </tr>
            <?php endwhile; if ($i === 1): ?>
                <tr><td colspan="14" class="text-center text-muted py-3">No records yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>

<style>
.ge-block { border-bottom: 1px solid #eef2f7; padding: 12px 0; }
.ge-block:last-child { border-bottom: none; }
.ge-row { display: grid; grid-template-columns: 260px 1fr; gap: 16px; align-items: start; }
.ge-label { font-weight: 600; color: #1e293b; font-size: 13px; line-height: 1.5; }
.ge-input { min-width: 0; }
.full-rec-table { font-size: 12px; }
.full-rec-table th { white-space: nowrap; font-size: 11px; background: #f1f5f9; }
.full-rec-table td { vertical-align: top; max-width: 180px; }
@media (max-width: 768px) {
    .ge-row { grid-template-columns: 1fr; gap: 6px; }
    .ge-input select { max-width: 100% !important; }
}
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>