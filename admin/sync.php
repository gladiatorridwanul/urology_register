<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();
$pageTitle = 'Server Sync';
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $target = $_POST['target'] ?? 'remote';
    try {
        $source = getDB('local');
        $dest = getDB($target === 'remote' ? 'remote' : 'local');
        
        // Sync: for each table, compare IDs and copy missing rows
        $tables = ['users','patients','chief_complaints','urological_history','comorbidity',
                   'drug_history','personal_history','family_history','menstrual_history',
                   'general_exam','genitourinary_exam','other_system_exam','diagnosis',
                   'lab_investigations','imaging_reports','operations','discharge_summary','followup'];
        
        $copied = 0;
        foreach ($tables as $t) {
            $rows = $source->query("SELECT * FROM $t");
            while ($row = $rows->fetch_assoc()) {
                $id = $row['id'];
                $exists = $dest->query("SELECT id FROM $t WHERE id=$id")->num_rows;
                if (!$exists) {
                    $cols = array_keys($row);
                    $vals = array_map(fn($v) => $dest->real_escape_string($v ?? ''), array_values($row));
                    $sql = "INSERT INTO $t (`".implode('`,`', $cols)."`) VALUES ('".implode("','", $vals)."')";
                    $dest->query($sql);
                    $copied++;
                }
            }
        }
        $msg = "Sync complete. $copied rows copied to $target server.";
    } catch (Exception $e) {
        $msg = "Sync failed: " . $e->getMessage();
    }
}
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<h5 class="mb-3"><i class="fas fa-sync text-primary me-2"></i>Server Synchronization</h5>
<?php if ($msg): ?><div class="alert alert-info"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

<div class="card">
    <div class="card-header">Sync Between XAMPP (Local) and cPanel (Remote)</div>
    <div class="card-body">
        <p class="text-muted">Use this to push local data to the cPanel server or pull remote data into XAMPP. Ensure remote credentials in <code>config/database.php</code> are correct.</p>
        <form method="POST" class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Direction</label>
                <select name="target" class="form-select">
                    <option value="remote">Local (XAMPP) → Remote (cPanel)</option>
                    <option value="local">Remote (cPanel) → Local (XAMPP)</option>
                </select>
            </div>
            <div class="col-md-6 d-flex align-items-end">
                <button class="btn btn-primary w-100"><i class="fas fa-sync me-2"></i>Run Sync</button>
            </div>
        </form>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>