<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
$pageTitle = 'Patients';

/**
 * Render a very small thumbnail for an uploaded file.
 */
function renderThumb($file_path, $size = 40) {
    if (empty($file_path)) {
        return '<span class="text-muted small">—</span>';
    }
    $url = BASE_URL . UPLOAD_URL . $file_path;
    $ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));

    if (in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
        return '<a href="' . htmlspecialchars($url) . '" target="_blank" class="thumb-link" title="View full size">'
             . '<img src="' . htmlspecialchars($url) . '" alt="report" '
             . 'class="thumb-img" style="width:' . (int)$size . 'px;height:' . (int)$size . 'px">'
             . '</a>';
    }
    if ($ext === 'pdf') {
        return '<a href="' . htmlspecialchars($url) . '" target="_blank" '
             . 'class="thumb-link thumb-pdf" title="Open PDF" '
             . 'style="width:' . (int)$size . 'px;height:' . (int)$size . 'px">'
             . '<i class="fas fa-file-pdf"></i>'
             . '<span>PDF</span>'
             . '</a>';
    }
    return '<a href="' . htmlspecialchars($url) . '" target="_blank" class="text-primary" title="Open file">'
         . '<i class="fas fa-file"></i></a>';
}

$filter  = $_GET['filter'] ?? 'all';
$search  = trim($_GET['q'] ?? '');
$perPage = intval($_GET['show'] ?? 10);
if ($perPage < 1) $perPage = 10;

$where = [];
if ($filter === 'active') {
    $where[] = "(discharge_date IS NULL OR discharge_date = '' OR discharge_date = '0000-00-00')";
}
if ($filter === 'discharged') {
    $where[] = "(discharge_date IS NOT NULL AND discharge_date != '' AND discharge_date != '0000-00-00')";
}
if ($search) {
    $s = $conn->real_escape_string($search);
    $where[] = "(name LIKE '%$s%' OR uro_id LIKE '%$s%' OR mobile LIKE '%$s%')";
}
$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$patients = $conn->query("SELECT * FROM patients $whereSQL ORDER BY id DESC LIMIT $perPage");

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<?php if (isset($_GET['deleted'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle me-1"></i> Patient deleted successfully.
        <button class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<?php if (isset($_GET['error']) && $_GET['error'] === 'notfound'): ?>
    <div class="alert alert-warning alert-dismissible fade show">
        <i class="fas fa-exclamation-triangle me-1"></i> Patient not found.
        <button class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- ============ PAGE HEADER ============ -->
<div class="card mb-3">
    <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h5 class="mb-0">
                <i class="fas fa-users text-primary me-2"></i>
                <?= $filter === 'active' ? 'Active Patients' : ($filter === 'discharged' ? 'Discharged Patients' : 'All Patients') ?>
            </h5>
            <small class="text-muted"><?= $patients->num_rows ?> record(s) found</small>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#exportModal">
                <i class="fas fa-file-excel me-1"></i> Export Excel
            </button>
            <a href="create.php" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i> Create
            </a>
        </div>
    </div>
</div>

<!-- ============ FILTER BAR ============ -->
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-lg-4 col-md-6">
                <input type="text" name="q" class="form-control form-control-sm"
                       placeholder="Search patients (name, URO ID, mobile)…"
                       value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="col-lg-3 col-md-6">
                <input type="text" class="form-control form-control-sm" placeholder="Other Category" disabled>
            </div>
            <div class="col-lg-3 col-md-6 d-flex gap-2">
                <a href="?filter=all&q=<?= urlencode($search) ?>"
                   class="btn btn-sm <?= $filter==='all'?'btn-dark':'btn-outline-secondary' ?>">All Patient</a>
                <a href="?filter=discharged&q=<?= urlencode($search) ?>"
                   class="btn btn-sm <?= $filter==='discharged'?'btn-danger':'btn-outline-danger' ?>">Discharge Patient</a>
                <a href="?filter=active&q=<?= urlencode($search) ?>"
                   class="btn btn-sm <?= $filter==='active'?'btn-success':'btn-outline-success' ?>">Active</a>
            </div>
            <div class="col-lg-2 col-md-6 d-flex gap-2 align-items-center justify-content-end">
                <label class="small text-muted mb-0">Show</label>
                <select name="show" class="form-select form-select-sm" style="width:80px" onchange="this.form.submit()">
                    <?php foreach ([10,25,50,100] as $n): ?>
                        <option value="<?= $n ?>" <?= $perPage==$n?'selected':'' ?>><?= $n ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">
                <button class="btn btn-sm btn-primary">Go</button>
            </div>
        </form>
    </div>
</div>

<!-- ============ PATIENT LIST ============ -->
<?php if ($patients->num_rows === 0): ?>
    <div class="alert alert-info">No patients found.</div>
<?php else: ?>
    <?php $idx = 1; while ($p = $patients->fetch_assoc()): 
        $pid = (int)$p['id'];

        // ---- Latest Operation ----
        $op = $conn->query("SELECT * FROM operations WHERE patient_id=$pid ORDER BY id DESC LIMIT 1")->fetch_assoc();

        // ---- Latest Diagnosis ----
        $dx = $conn->query("SELECT * FROM diagnosis WHERE patient_id=$pid ORDER BY id DESC LIMIT 1")->fetch_assoc();
        $dxStr = 'N/A';
        if ($dx) {
            $parts = [];
            if ($dx['renal_stone'] && $dx['renal_stone'] != 'None') $parts[] = 'Renal Stone (' . $dx['renal_stone'] . ')';
            if ($dx['ureteric_stone'] && $dx['ureteric_stone'] != 'None') $parts[] = 'Ureteric Stone' . ($dx['ureteric_location'] ? ' (' . $dx['ureteric_location'] . ')' : '');
            if ($dx['bladder_stone']) $parts[] = 'Bladder Stone';
            if ($dx['bph']) $parts[] = 'BPH';
            if ($dx['carcinoma_prostate']) $parts[] = 'Ca Prostate';
            if ($dx['renal_mass'] && $dx['renal_mass'] != 'None') $parts[] = 'Renal Mass';
            if ($dx['utuc'] && $dx['utuc'] != 'None') $parts[] = 'UTUC';
            if ($dx['bladder_mass']) $parts[] = 'Bladder Mass';
            if ($dx['stricture_urethra']) $parts[] = 'Stricture Urethra';
            if ($dx['puj_obstruction'] && $dx['puj_obstruction'] != 'None') $parts[] = 'PUJ Obstruction';
            if ($dx['testicular_tumor'] && $dx['testicular_tumor'] != 'None') $parts[] = 'Testicular Tumor';
            if ($dx['carcinoma_penis']) $parts[] = 'Ca Penis';
            if ($dx['varicocele'] && $dx['varicocele'] != 'None') $parts[] = 'Varicocele';
            if (!empty($dx['others'])) $parts[] = $dx['others'];
            $dxStr = $parts ? implode(', ', $parts) : 'Recorded';
        }

        // ---- Latest Comorbidity ----
        $cm = $conn->query("SELECT * FROM comorbidity WHERE patient_id=$pid ORDER BY id DESC LIMIT 1")->fetch_assoc();
        $comorb = [];
        if ($cm) {
            if ($cm['htn'])          $comorb[] = 'HTN';
            if ($cm['dm'])           $comorb[] = 'DM';
            if ($cm['ihd'])          $comorb[] = 'IHD';
            if ($cm['ckd'])          $comorb[] = 'CKD';
            if ($cm['copd'])         $comorb[] = 'COPD';
            if ($cm['neurological']) $comorb[] = 'Neuro';
        }
        $comorbStr = $comorb ? implode(', ', $comorb) : 'No Comorbidity';

        // ---- Latest Discharge Summary ----
        $ds = $conn->query("SELECT * FROM discharge_summary WHERE patient_id=$pid ORDER BY id DESC LIMIT 1")->fetch_assoc();

        // ---- Latest Follow-up ----
        $fu = $conn->query("SELECT * FROM followup WHERE patient_id=$pid ORDER BY id DESC LIMIT 1")->fetch_assoc();

        // ---- Latest Imaging report (for ADVICE "File" col) ----
        $img = $conn->query("SELECT file_path FROM imaging_reports WHERE patient_id=$pid AND file_path IS NOT NULL ORDER BY id DESC LIMIT 1")->fetch_assoc();

        // ---- Patient active state ----
        $isActive = empty($p['discharge_date']) || $p['discharge_date'] === '0000-00-00';

        // ---- All uploaded imaging reports (thumbnail gallery) ----
        $allReports = $conn->query("
            SELECT id, investigation_name, investigation_date, file_path
            FROM imaging_reports
            WHERE patient_id = $pid
            ORDER BY id DESC
            LIMIT 12
        ");
        $reportRows = [];
        while ($rr = $allReports->fetch_assoc()) $reportRows[] = $rr;

        // ---------- FIXED: only enable Print when a discharge summary exists ----------
        $canPrint = !empty($ds);
    ?>

    <div class="patient-card-modern">
        <!-- ======== LEFT: PATIENT INFO PANEL ======== -->
        <div class="pc-left">
            <div class="pc-index"><?= $idx++ ?></div>
            <div class="pc-info">
                <div class="pc-row"><span class="pc-label">Name:</span> <strong><?= htmlspecialchars($p['name']) ?></strong></div>
                <div class="pc-row"><span class="pc-label">Gender:</span> <?= htmlspecialchars($p['sex']) ?></div>
                <div class="pc-row"><span class="pc-label">Age:</span> <?= (int)$p['age'] ?> years</div>
                <div class="pc-row"><span class="pc-label">Phone:</span> <?= htmlspecialchars($p['mobile']) ?></div>
                <div class="pc-row"><span class="pc-label">URO ID:</span> <span class="text-primary fw-semibold"><?= htmlspecialchars($p['uro_id']) ?></span></div>
                <div class="pc-row"><span class="pc-label">Blood:</span> <?= htmlspecialchars($p['blood_group'] ?: '—') ?></div>
                <div class="pc-row"><span class="pc-label">Ward/Bed:</span> <?= htmlspecialchars($p['ward'] ?: '—') ?> / <?= htmlspecialchars($p['bed'] ?: '—') ?></div>
                <div class="pc-row">
                    <span class="badge bg-<?= $isActive ? 'success' : 'secondary' ?>">
                        <?= $isActive ? 'ACTIVE PATIENT' : 'DISCHARGED' ?>
                    </span>
                </div>
            </div>
            <!-- Icon action row -->
            <div class="pc-actions">
                <a href="edit.php?id=<?= $pid ?>" class="btn btn-icon btn-primary" title="Edit"><i class="fas fa-edit"></i></a>
                <a href="view.php?id=<?= $pid ?>" class="btn btn-icon btn-info" title="View"><i class="fas fa-eye"></i></a>
                <a href="../modules/imaging.php?patient_id=<?= $pid ?>" class="btn btn-icon btn-secondary" title="Upload Files"><i class="fas fa-upload"></i></a>

                <?php if ($canPrint): ?>
                    <!-- FIXED: pass patient_id, not discharge_summary id -->
                    <a href="../modules/discharge_print.php?id=<?= $pid ?>" target="_blank"
                       class="btn btn-icon btn-warning" title="Print Discharge Summary">
                        <i class="fas fa-print"></i>
                    </a>
                <?php else: ?>
                    <a href="#" class="btn btn-icon btn-warning disabled" title="No discharge summary to print" onclick="return false;">
                        <i class="fas fa-print"></i>
                    </a>
                <?php endif; ?>

                <a href="delete.php?id=<?= $pid ?>" class="btn btn-icon btn-danger confirm-delete" title="Delete"><i class="fas fa-trash"></i></a>
            </div>
        </div>

        <!-- ======== RIGHT: OPERATION INFO ======== -->
        <div class="pc-right">
            <div class="pc-visit-pill">1ST : VISIT DATE : <?= $p['current_visit_date'] ?: 'N/A' ?></div>

            <!-- Operation block -->
            <div class="pc-op-grid">
                <div class="pc-op-info-wrap">
                    <h6 class="pc-op-title">Operation Info</h6>
                    <div class="pc-op-details">
                        <div><span class="label">Provisional Diagnosis:</span> <?= htmlspecialchars($dxStr) ?></div>
                        <div><span class="label">Operation:</span> <?= $op ? htmlspecialchars($op['operation_name']) : 'N/A' ?></div>
                        <div><span class="label">Operation Date:</span> <?= $op['operation_date'] ?? 'N/A' ?></div>
                        <div><span class="label">Approach:</span> <?= $op['approach'] ?? 'N/A' ?></div>
                        <div><span class="label">Comorbidity:</span> <?= htmlspecialchars($comorbStr) ?></div>
                    </div>
                </div>

                <div class="pc-op-summary-wrap">
                    <h6 class="pc-op-title">Patient Summary</h6>
                    <div class="pc-op-summary-scroll">
                        <table class="pc-op-table">
                            <thead>
                                <tr>
                                    <th>Reg</th>
                                    <th>Ward No</th>
                                    <th>Bed No</th>
                                    <th>Blood</th>
                                    <th>Unit</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><?= htmlspecialchars($p['hospital_reg_no'] ?: 'N/A') ?></td>
                                    <td><?= htmlspecialchars($p['ward'] ?: 'N/A') ?></td>
                                    <td><?= htmlspecialchars($p['bed'] ?: 'N/A') ?></td>
                                    <td><?= htmlspecialchars($p['blood_group'] ?: 'N/A') ?></td>
                                    <td><?= htmlspecialchars($p['unit'] ?: 'N/A') ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-light border" data-bs-toggle="dropdown">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li><a class="dropdown-item" href="view.php?id=<?= $pid ?>"><i class="fas fa-eye me-2"></i>View Profile</a></li>
                                            <li><a class="dropdown-item" href="edit.php?id=<?= $pid ?>"><i class="fas fa-edit me-2"></i>Edit Patient</a></li>
                                            <li><a class="dropdown-item" href="../modules/operation.php?patient_id=<?= $pid ?>"><i class="fas fa-procedures me-2"></i>Add Operation</a></li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li><a class="dropdown-item text-danger confirm-delete" href="delete.php?id=<?= $pid ?>"><i class="fas fa-trash me-2"></i>Delete</a></li>
                                        </ul>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ======== ADVICE SECTION ======== -->
            <div class="pc-section-pill">ADVICE</div>
            <div class="table-responsive">
                <table class="pc-sub-table">
                    <thead>
                        <tr>
                            <th style="width:100px">Date</th>
                            <th>Investigation</th>
                            <th>Diagnosis</th>
                            <th>Advice</th>
                            <th>Drug Advice</th>
                            <th style="width:56px">File</th>
                            <th style="width:70px">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($ds): ?>
                        <tr>
                            <td><?= htmlspecialchars($ds['discharge_date'] ?: ($p['current_visit_date'] ?: '—')) ?></td>
                            <td><?= htmlspecialchars(substr((string)$ds['relevant_investigations'], 0, 40) ?: 'N/A') ?><?= strlen((string)$ds['relevant_investigations']) > 40 ? '…' : '' ?></td>
                            <td><?= htmlspecialchars(substr((string)$ds['diagnosis'], 0, 40) ?: 'N/A') ?><?= strlen((string)$ds['diagnosis']) > 40 ? '…' : '' ?></td>
                            <td><?= htmlspecialchars(substr((string)$ds['advice'], 0, 50) ?: 'N/A') ?><?= strlen((string)$ds['advice']) > 50 ? '…' : '' ?></td>
                            <td><?= htmlspecialchars(substr((string)$ds['medication'], 0, 50) ?: 'N/A') ?><?= strlen((string)$ds['medication']) > 50 ? '…' : '' ?></td>
                            <td class="text-center">
                                <?= renderThumb($img['file_path'] ?? null, 36) ?>
                            </td>
                            <td class="text-center">
                                <a href="../modules/discharge.php?patient_id=<?= $pid ?>" class="btn btn-sm btn-primary" title="Edit / Add">
                                    <i class="fas fa-edit"></i>
                                </a>
                            </td>
                        </tr>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-2">
                                <em>No discharge summary yet.</em>
                            </td>
                            <td class="text-center">
                                <a href="../modules/discharge.php?patient_id=<?= $pid ?>" class="btn btn-sm btn-primary" title="Add Discharge Summary">
                                    <i class="fas fa-plus"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- ======== FOLLOW UP SECTION ======== -->
            <div class="pc-section-pill">FOLLOW UP</div>
            <div class="table-responsive">
                <table class="pc-sub-table">
                    <thead>
                        <tr>
                            <th style="width:120px">Follow Up</th>
                            <th style="width:110px">Date</th>
                            <th>Scoring</th>
                            <th>Note</th>
                            <th style="width:56px">File</th>
                            <th style="width:70px">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($fu): ?>
                        <tr>
                            <td><?= htmlspecialchars($fu['visit_type'] ?: 'Routine') ?></td>
                            <td><?= htmlspecialchars($fu['followup_date'] ?: '—') ?></td>
                            <td><?= htmlspecialchars($fu['scoring'] ?: '—') ?></td>
                            <td><?= htmlspecialchars(substr((string)$fu['notes'], 0, 60) ?: '—') ?><?= strlen((string)$fu['notes']) > 60 ? '…' : '' ?></td>
                            <td class="text-center">
                                <?= renderThumb($fu['file_path'] ?? null, 36) ?>
                            </td>
                            <td class="text-center">
                                <a href="../modules/followup.php?patient_id=<?= $pid ?>" class="btn btn-sm btn-primary" title="Add Follow-up">
                                    <i class="fas fa-plus"></i>
                                </a>
                            </td>
                        </tr>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-2">
                                <em>No follow-up yet.</em>
                            </td>
                            <td class="text-center">
                                <a href="../modules/followup.php?patient_id=<?= $pid ?>" class="btn btn-sm btn-primary" title="Add Follow-up">
                                    <i class="fas fa-plus"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- ======== REPORTS / UPLOADS ======== -->
            <?php if (!empty($reportRows)): ?>
                <div class="pc-section-pill">REPORTS / UPLOADS</div>

                <div class="report-strip-scroll">
                    <div class="report-strip">
                        <?php foreach ($reportRows as $rr): ?>
                            <div class="report-thumb-card">
                                <?= renderThumb($rr['file_path'] ?? null, 52) ?>
                                <div class="report-thumb-label" title="<?= htmlspecialchars($rr['investigation_name'] ?: 'Report') ?>">
                                    <?= htmlspecialchars(mb_strimwidth($rr['investigation_name'] ?: 'Report', 0, 16, '…')) ?>
                                </div>
                                <div class="report-thumb-date">
                                    <?= $rr['investigation_date'] ? date('d-m-Y', strtotime($rr['investigation_date'])) : '—' ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="text-end mt-1">
                    <a href="../modules/imaging.php?patient_id=<?= $pid ?>" class="small text-primary">
                        <i class="fas fa-plus-circle me-1"></i> Add / Manage Reports
                    </a>
                </div>
            <?php endif; ?>

        </div>
    </div>
    <?php endwhile; ?>
<?php endif; ?>

<!-- ============ INLINE STYLES ============ -->
<style>
.patient-card-modern {
    display: grid;
    grid-template-columns: 250px 1fr;
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,.06);
    margin-bottom: 16px;
    overflow: hidden;
    border: 1px solid #e9eef5;
}
.pc-left {
    background: #f8fafc;
    padding: 16px;
    border-right: 1px solid #e9eef5;
    position: relative;
}
.pc-index {
    position: absolute;
    top: 8px;
    left: 12px;
    font-size: 11px;
    color: #94a3b8;
    font-weight: 600;
}
.pc-info { padding-top: 12px; }
.pc-row {
    font-size: 12.5px;
    margin-bottom: 4px;
    color: #334155;
    line-height: 1.5;
}
.pc-label { color: #64748b; font-weight: 500; margin-right: 4px; }
.pc-actions {
    display: flex;
    gap: 6px;
    margin-top: 12px;
    padding-top: 12px;
    border-top: 1px dashed #cbd5e1;
    flex-wrap: wrap;
}
.btn-icon {
    width: 30px; height: 30px;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 6px;
    font-size: 12px;
    color: #fff;
}
.btn-icon.disabled { opacity: .4; pointer-events: none; }

.pc-right { padding: 12px 16px; min-width: 0; }

.pc-visit-pill {
    display: inline-block;
    background: #2563eb;
    color: #fff;
    padding: 4px 18px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
    letter-spacing: .3px;
    margin-bottom: 10px;
}

.pc-op-grid {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(0, 420px);
    gap: 14px;
    margin-bottom: 12px;
    align-items: start;
}
.pc-op-info-wrap,
.pc-op-summary-wrap { min-width: 0; }
.pc-op-title {
    font-size: 13px;
    font-weight: 700;
    color: #1e293b;
    margin: 0 0 6px;
    padding-bottom: 4px;
    border-bottom: 1px solid #e2e8f0;
    line-height: 1.4;
}
.pc-op-details {
    background: #f8fafc;
    border: 1px dashed #cbd5e1;
    border-radius: 6px;
    padding: 8px 10px;
    font-size: 12px;
    line-height: 1.7;
    color: #334155;
}
.pc-op-details .label { color: #64748b; font-weight: 500; }

.pc-op-summary-scroll { overflow-x: auto; }
.pc-op-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 12px;
}
.pc-op-table thead th {
    background: #f1f5f9;
    color: #475569;
    font-weight: 600;
    padding: 6px 8px;
    border: 1px solid #e2e8f0;
    text-align: left;
    white-space: nowrap;
}
.pc-op-table tbody td {
    padding: 6px 8px;
    border: 1px solid #e2e8f0;
    color: #334155;
    vertical-align: middle;
}

.pc-section-pill {
    background: #2563eb;
    color: #fff;
    padding: 3px 14px;
    font-size: 11px;
    font-weight: 600;
    display: inline-block;
    border-radius: 4px;
    margin: 10px 0 6px;
    letter-spacing: .4px;
}

.pc-sub-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 12px;
    margin-bottom: 6px;
}
.pc-sub-table thead th {
    background: #f1f5f9;
    color: #475569;
    font-weight: 600;
    padding: 6px 8px;
    border: 1px solid #e2e8f0;
    text-align: left;
    white-space: nowrap;
}
.pc-sub-table tbody td {
    padding: 6px 8px;
    border: 1px solid #e2e8f0;
    color: #334155;
}
.pc-sub-table tbody tr:nth-child(odd) td { background: #f8fafc; }
.pc-sub-table .btn-sm {
    padding: 3px 8px;
    font-size: 11px;
    border-radius: 4px;
    color: #fff;
}

.thumb-link {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 5px;
    overflow: hidden;
    border: 1px solid #e2e8f0;
    background: #f8fafc;
    transition: transform .15s, box-shadow .15s, border-color .15s;
    vertical-align: middle;
    text-decoration: none;
}
.thumb-link:hover {
    transform: scale(1.08);
    box-shadow: 0 2px 8px rgba(37,99,235,.25);
    border-color: #2563eb;
}
.thumb-img {
    object-fit: cover;
    display: block;
    border-radius: 4px;
}
.thumb-pdf {
    flex-direction: column;
    gap: 0px;
    color: #dc2626;
    font-weight: 700;
}
.thumb-pdf i { font-size: 15px; line-height: 1; }
.thumb-pdf span { font-size: 7px; letter-spacing: .4px; line-height: 1; }

.report-strip-scroll {
    overflow-x: auto;
    overflow-y: hidden;
    padding-bottom: 4px;
    -webkit-overflow-scrolling: touch;
}
.report-strip {
    display: flex;
    flex-wrap: nowrap;
    gap: 8px;
    align-items: flex-start;
    padding: 2px 0;
}
.report-thumb-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 2px;
    padding: 4px;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    background: #fff;
    flex: 0 0 auto;
    min-width: 66px;
    max-width: 76px;
    transition: .15s;
}
.report-thumb-card:hover {
    border-color: #2563eb;
    box-shadow: 0 2px 8px rgba(37,99,235,.15);
    transform: translateY(-1px);
}
.report-thumb-label {
    font-size: 9.5px;
    color: #334155;
    font-weight: 600;
    text-align: center;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    width: 100%;
    line-height: 1.2;
}
.report-thumb-date {
    font-size: 9px;
    color: #64748b;
    text-align: center;
    line-height: 1.2;
    width: 100%;
}

@media (max-width: 991px) {
    .patient-card-modern { grid-template-columns: 1fr; }
    .pc-left {
        border-right: none;
        border-bottom: 1px solid #e9eef5;
    }
    .pc-op-grid { grid-template-columns: 1fr; }
}
@media (max-width: 576px) {
    .pc-left, .pc-right { padding: 12px; }
    .pc-visit-pill { font-size: 10px; padding: 3px 12px; }
    .pc-sub-table, .pc-op-table { font-size: 11px; }
    .pc-sub-table thead th, .pc-op-table thead th { padding: 4px 5px; }
    .pc-sub-table tbody td, .pc-op-table tbody td { padding: 4px 5px; }
    .report-thumb-card { min-width: 60px; max-width: 68px; }
}
</style>

<!-- ============================================================
     EXPORT EXCEL MODAL
     ============================================================ -->
<div class="modal fade" id="exportModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header export-modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-file-excel me-2"></i>Export Patients to Excel
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-3">
                    Choose the export type and optional date range (based on patient registration date).
                </p>

                <form id="exportForm" method="GET" action="../export/basic_patients.php" target="_blank">
                    <div class="row g-3">

                        <!-- Export Type -->
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Export Type <span class="text-danger">*</span></label>
                            <div class="export-type-grid">
                                <label class="export-type-card">
                                    <input type="radio" name="export_type" value="basic" checked>
                                    <div class="export-type-body">
                                        <i class="fas fa-users text-primary"></i>
                                        <strong>Basic Patient Data</strong>
                                        <small>Profile only — 1 sheet</small>
                                    </div>
                                </label>
                                <label class="export-type-card">
                                    <input type="radio" name="export_type" value="full">
                                    <div class="export-type-body">
                                        <i class="fas fa-layer-group text-success"></i>
                                        <strong>Full Patient Data</strong>
                                        <small>All 16 modules — 17 sheets</small>
                                    </div>
                                </label>
                                <label class="export-type-card">
                                    <input type="radio" name="export_type" value="all_in_one">
                                    <div class="export-type-body">
                                        <i class="fas fa-table text-warning"></i>
                                        <strong>All-in-One Sheet</strong>
                                        <small>Every module — 1 wide sheet</small>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Date Range -->
                        <div class="col-md-6">
                            <label class="form-label">From Date <small class="text-muted">(optional)</small></label>
                            <input type="date" name="from" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">To Date <small class="text-muted">(optional)</small></label>
                            <input type="date" name="to" class="form-control">
                        </div>

                        <!-- Quick Ranges -->
                        <div class="col-12">
                            <label class="form-label small text-muted">Quick Range</label>
                            <div class="d-flex gap-2 flex-wrap">
                                <button type="button" class="btn btn-sm btn-outline-secondary quick-range"
                                        data-from="" data-to="">All Time</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary quick-range"
                                        data-from="<?= date('Y-m-d') ?>" data-to="<?= date('Y-m-d') ?>">Today</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary quick-range"
                                        data-from="<?= date('Y-m-d', strtotime('-7 days')) ?>" data-to="<?= date('Y-m-d') ?>">Last 7 Days</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary quick-range"
                                        data-from="<?= date('Y-m-d', strtotime('-30 days')) ?>" data-to="<?= date('Y-m-d') ?>">Last 30 Days</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary quick-range"
                                        data-from="<?= date('Y-m-01') ?>" data-to="<?= date('Y-m-t') ?>">This Month</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary quick-range"
                                        data-from="<?= date('Y-01-01') ?>" data-to="<?= date('Y-12-31') ?>">This Year</button>
                            </div>
                        </div>

                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success px-4" onclick="submitExport()">
                    <i class="fas fa-download me-1"></i> Download Excel
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================
     EXPORT MODAL STYLES + JS
     ============================================================ -->
<style>
.export-modal-header {
    background: linear-gradient(135deg, #16a34a 0%, #2563eb 100%);
    color: #fff;
}
.export-type-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 10px;
}
@media (max-width: 768px) {
    .export-type-grid { grid-template-columns: 1fr; }
}
/*.export-type-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
}*/
.export-type-card {
    cursor: pointer;
    margin: 0;
}
.export-type-card input[type="radio"] {
    display: none;
}
.export-type-body {
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    padding: 14px;
    text-align: center;
    transition: .15s;
    background: #fff;
}
.export-type-body i {
    font-size: 24px;
    display: block;
    margin-bottom: 6px;
}
.export-type-body strong {
    display: block;
    font-size: 13.5px;
    color: #1e293b;
    margin-bottom: 2px;
}
.export-type-body small {
    color: #64748b;
    font-size: 11px;
}
.export-type-card input:checked + .export-type-body {
    border-color: #2563eb;
    background: #f0f9ff;
    box-shadow: 0 0 0 3px rgba(37,99,235,.12);
}
.export-type-card input:checked + .export-type-body strong {
    color: #1d4ed8;
}
@media (max-width: 576px) {
    .export-type-grid { grid-template-columns: 1fr; }
}
</style>

<script>
// Quick range buttons
document.querySelectorAll('.quick-range').forEach(function(btn) {
    btn.addEventListener('click', function() {
        document.querySelector('#exportForm [name="from"]').value = this.dataset.from;
        document.querySelector('#exportForm [name="to"]').value   = this.dataset.to;
    });
});

// Submit form to the correct export endpoint
function submitExport() {
    const form = document.getElementById('exportForm');
    const type = form.querySelector('input[name="export_type"]:checked').value;
    if (type === 'full')            form.action = '../export/full_patients.php';
    else if (type === 'all_in_one') form.action = '../export/all_in_one.php';
    else                             form.action = '../export/basic_patients.php';
    form.submit();
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>