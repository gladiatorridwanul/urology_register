<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
$id = intval($_GET['id'] ?? 0);
$r = $conn->query("SELECT * FROM urological_history WHERE id=$id")->fetch_assoc();
if (!$r) die("Record not found");
$patient_id = $r['patient_id'];
$p = $conn->query("SELECT * FROM patients WHERE id=$patient_id")->fetch_assoc();
$pageTitle = 'Edit Urological History';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $conn->prepare("UPDATE urological_history SET 
        trauma=?, trauma_details=?, 
        instrumentation=?, instrumentation_details=?,
        catheterization=?, catheterization_details=?,
        surgery=?, surgery_details=?, 
        stone_disease=?, stone_details=?, 
        malignancy=?, malignancy_details=?, 
        others=? 
        WHERE id=?");

    $stmt->bind_param('sssssssssssssi',
        $_POST['trauma'], $_POST['trauma_details'],
        $_POST['instrumentation'], $_POST['instrumentation_details'],
        $_POST['catheterization'], $_POST['catheterization_details'],
        $_POST['surgery'], $_POST['surgery_details'],
        $_POST['stone_disease'], $_POST['stone_details'],
        $_POST['malignancy'], $_POST['malignancy_details'],
        $_POST['others'],
        $id
    );
    $stmt->execute();
    header("Location: urological_history.php?patient_id=$patient_id&saved=1"); exit;
}
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<div class="d-flex justify-content-between mb-3 flex-wrap gap-2">
    <div>
        <h5 class="mb-0"><i class="fas fa-edit text-primary me-2"></i>Edit Urological History</h5>
        <small class="text-muted"><?= htmlspecialchars($p['name']) ?> (<?= $p['uro_id'] ?>)</small>
    </div>
    <a href="urological_history.php?patient_id=<?= $patient_id ?>" class="btn btn-outline-secondary btn-sm">← Back</a>
</div>

<form method="POST">
    <div class="card">
        <div class="card-body">

            <?php
            $fields = [
                ['trauma',          'History of trauma',                        'trauma_details'],
                ['instrumentation', 'History of per-urethral instrumentation',  'instrumentation_details'],
                ['catheterization', 'History of per-urethral catheterization',  'catheterization_details'],
                ['surgery',         'History of surgery',                       'surgery_details'],
                ['stone_disease',   'History of urinary stone disease',         'stone_details'],
                ['malignancy',      'History of urological malignancy',         'malignancy_details'],
            ];
            foreach ($fields as $f):
                $name = $f[0]; $label = $f[1]; $detailName = $f[2];
                $checked = ($r[$name] === 'Yes');
            ?>
                <div class="uh-block">
                    <div class="uh-row">
                        <div class="uh-label">
                            <i class="fas fa-notes-medical text-primary"></i>
                            <?= htmlspecialchars($label) ?>
                        </div>
                        <div class="uh-input">
                            <div class="d-flex align-items-center gap-3 flex-wrap">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="<?= $name ?>"
                                           id="<?= $name ?>" value="Yes" <?= $checked ? 'checked' : '' ?>
                                           onchange="document.getElementById('<?= $name ?>_extra').style.display=this.checked?'inline-flex':'none'">
                                    <label class="form-check-label" for="<?= $name ?>">Yes</label>
                                </div>
                                <div id="<?= $name ?>_extra" style="display:<?= $checked ? 'inline-flex' : 'none' ?>">
                                    <input name="<?= $detailName ?>" class="form-control form-control-sm d-inline-block"
                                           style="width:400px" value="<?= htmlspecialchars($r[$detailName]) ?>"
                                           placeholder="Specify details">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <div class="uh-block">
                <div class="uh-row">
                    <div class="uh-label"><i class="fas fa-ellipsis-h text-primary"></i> Others</div>
                    <div class="uh-input">
                        <textarea name="others" class="form-control form-control-sm" rows="2"><?= htmlspecialchars($r['others']) ?></textarea>
                    </div>
                </div>
            </div>

        </div>
        <div class="card-footer text-end">
            <a href="urological_history.php?patient_id=<?= $patient_id ?>" class="btn btn-outline-secondary me-2">Cancel</a>
            <button class="btn btn-primary px-4"><i class="fas fa-save me-1"></i> Update</button>
        </div>
    </div>
</form>

<style>
.uh-block { border-bottom: 1px solid #eef2f7; padding: 12px 0; }
.uh-block:last-child { border-bottom: none; }
.uh-row { display: grid; grid-template-columns: 300px 1fr; gap: 16px; align-items: center; }
.uh-label { font-weight: 600; color: #1e293b; font-size: 13px; line-height: 1.5; }
.uh-input { min-width: 0; }
@media (max-width: 768px) {
    .uh-row { grid-template-columns: 1fr; gap: 6px; }
    .uh-input input[style*="width:400px"] { width: 100% !important; }
}
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>