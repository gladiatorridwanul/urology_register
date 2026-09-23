<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
$id = intval($_GET['id'] ?? 0);
$p = $conn->query("SELECT * FROM patients WHERE id=$id")->fetch_assoc();
if (!$p) die("Patient not found");
$pageTitle = 'Patient: ' . $p['name'];

$isActive = empty($p['discharge_date']) || $p['discharge_date'] === '0000-00-00';

$modules = [
    'chief_complaints'    => 'Chief Complaints',
    'urological_history'  => 'Previous Urological History',
    'comorbidity'         => 'Comorbidity',
    'drug_history'        => 'Drug History',
    'personal_history'    => 'Personal History',
    'family_history'      => 'Family History',
    'menstrual_history'   => 'Menstrual & Obstetric History',
    'general_exam'        => 'General Examination',
    'genitourinary_exam'  => 'Genitourinary Examination',
    'other_system_exam'   => 'Other System Examination',
    'diagnosis'           => 'Diagnosis',
    'lab_investigations'  => 'Laboratory Investigations',
    'imaging'             => 'Imaging / Reports',
    'operation'           => 'Operation Procedure',
    'discharge'           => 'Discharge Summary',
    'followup'            => 'Follow-up',
];

// ---- Split modules into "with data" and "empty" ----
$modulesWithData = [];
$modulesEmpty    = [];

foreach ($modules as $key => $label) {
    $tbl = $key === 'imaging' ? 'imaging_reports' : ($key === 'operation' ? 'operations' : ($key === 'discharge' ? 'discharge_summary' : $key));
    $r = $conn->query("SELECT COUNT(*) c, MAX(created_at) last FROM $tbl WHERE patient_id=$id")->fetch_assoc();
    $row = [
        'key'    => $key,
        'label'  => $label,
        'count'  => (int)$r['c'],
        'last'   => $r['last'],
    ];
    if ($row['count'] > 0) {
        $modulesWithData[] = $row;
    } else {
        $modulesEmpty[]    = $row;
    }
}

// ---- Max 16 per section: 8 first column + 8 second column ----
$MAX_PER_SUBCOL = 8;

$withDataCol1 = array_slice($modulesWithData, 0, $MAX_PER_SUBCOL);
$withDataCol2 = array_slice($modulesWithData, $MAX_PER_SUBCOL, $MAX_PER_SUBCOL);
$withDataMore = max(0, count($modulesWithData) - ($MAX_PER_SUBCOL * 2));

$emptyCol1 = array_slice($modulesEmpty, 0, $MAX_PER_SUBCOL);
$emptyCol2 = array_slice($modulesEmpty, $MAX_PER_SUBCOL, $MAX_PER_SUBCOL);
$emptyMore = max(0, count($modulesEmpty) - ($MAX_PER_SUBCOL * 2));

$hasWithData = count($withDataCol1) > 0;
$hasEmpty    = count($emptyCol1)    > 0;

// Lookup latest discharge summary ID (for print/pdf buttons)
$dsLatest = $conn->query("SELECT id FROM discharge_summary WHERE patient_id=$id ORDER BY id DESC LIMIT 1")->fetch_assoc();
$dsId = $dsLatest['id'] ?? 0;

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<?php if (isset($_GET['updated'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle me-1"></i> Patient updated successfully.
        <button class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- ============ HEADER ============ -->
<div class="d-flex justify-content-between mb-3 flex-wrap gap-2 align-items-start">
    <div>
        <h5 class="mb-0">
            <?= htmlspecialchars($p['name']) ?>
            <span class="badge bg-<?= $isActive ? 'success' : 'secondary' ?> align-middle ms-2">
                <?= $isActive ? 'Active' : 'Discharged' ?>
            </span>
        </h5>
        <small class="text-muted"><?= htmlspecialchars($p['uro_id']) ?></small>
    </div>

    <div class="pc-actions-view">
        <a href="edit.php?id=<?= $id ?>" class="btn btn-icon btn-primary" title="Edit Patient">
            <i class="fas fa-edit"></i>
        </a>
        <a href="../modules/discharge_print.php?id=<?= $id ?>" target="_blank"
           class="btn btn-icon btn-warning <?= $dsId ? '' : 'disabled' ?>" title="Print Full Report">
            <i class="fas fa-print"></i>
        </a>
        <?php if ($dsId): ?>
            <a href="../modules/discharge_pdf.php?id=<?= $dsId ?>" class="btn btn-icon btn-danger" title="Export PDF">
                <i class="fas fa-file-pdf"></i>
            </a>
        <?php endif; ?>
        <a href="index.php" class="btn btn-icon btn-secondary" title="Back to Patients">
            <i class="fas fa-arrow-left"></i>
        </a>
    </div>
</div>

<!-- ============ PATIENT PROFILE (4 COLUMNS) ============ -->
<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center py-2">
        <span class="fw-semibold"><i class="fas fa-id-card me-2 text-primary"></i>Patient Profile</span>
        <span class="badge bg-<?= $isActive ? 'success' : 'secondary' ?>">
            <?= $isActive ? 'ACTIVE' : 'DISCHARGED' ?>
        </span>
    </div>
    <div class="card-body py-2">
        <div class="profile-4col">

            <!-- ============ COLUMN 1: IDENTIFICATION (Name + URO + Address) ============ -->
            <div class="profile-col">
                <div class="ps-title">
                    <i class="fas fa-fingerprint text-primary me-1"></i> Identification
                </div>
                <div class="pg-stack">
                    <div class="pg-item">
                        <span class="pg-label">Name</span>
                        <span class="pg-value"><?= htmlspecialchars($p['name']) ?></span>
                    </div>
                    <div class="pg-item">
                        <span class="pg-label">URO ID</span>
                        <span class="pg-value text-primary"><?= htmlspecialchars($p['uro_id']) ?></span>
                    </div>
                    <div class="pg-item">
                        <span class="pg-label">NID</span>
                        <span class="pg-value"><?= htmlspecialchars($p['nid'] ?: '—') ?></span>
                    </div>
                    <div class="pg-item">
                        <span class="pg-label">Blood Group</span>
                        <span class="pg-value"><?= htmlspecialchars($p['blood_group'] ?: '—') ?></span>
                    </div>
                    <div class="pg-item">
                        <span class="pg-label">Address</span>
                        <span class="pg-value pg-address"><?= nl2br(htmlspecialchars($p['address'] ?: '—')) ?></span>
                    </div>
                </div>
            </div>

            <!-- ============ COLUMN 2: PERSONAL DETAILS + CONTACT ============ -->
            <div class="profile-col">
                <div class="ps-title">
                    <i class="fas fa-user text-primary me-1"></i> Personal &amp; Contact
                </div>
                <div class="pg-stack">
                    <div class="pg-item">
                        <span class="pg-label">Age / Sex</span>
                        <span class="pg-value"><?= (int)$p['age'] ?> yrs / <?= htmlspecialchars($p['sex']) ?></span>
                    </div>
                    <div class="pg-item">
                        <span class="pg-label">Date of Birth</span>
                        <span class="pg-value"><?= $p['dob'] ? date('d M Y', strtotime($p['dob'])) : '—' ?></span>
                    </div>
                    <div class="pg-item">
                        <span class="pg-label">Guardian</span>
                        <span class="pg-value"><?= htmlspecialchars($p['guardian_name'] ?: '—') ?></span>
                    </div>
                    <div class="pg-item">
                        <span class="pg-label">Occupation</span>
                        <span class="pg-value"><?= htmlspecialchars($p['occupation'] ?: '—') ?></span>
                    </div>
                    <div class="pg-item">
                        <span class="pg-label">Mobile No.</span>
                        <span class="pg-value"><?= htmlspecialchars($p['mobile'] ?: '—') ?></span>
                    </div>
                </div>
            </div>

            <!-- ============ COLUMN 3: ADMISSION ============ -->
            <div class="profile-col">
                <div class="ps-title">
                    <i class="fas fa-hospital text-primary me-1"></i> Admission
                </div>
                <div class="pg-stack">
                    <div class="pg-item">
                        <span class="pg-label">First Admission</span>
                        <span class="pg-value"><?= $p['first_admission_date'] ? date('d M Y', strtotime($p['first_admission_date'])) : '—' ?></span>
                    </div>
                    <div class="pg-item">
                        <span class="pg-label">Current Visit</span>
                        <span class="pg-value"><?= $p['current_visit_date'] ? date('d M Y', strtotime($p['current_visit_date'])) : '—' ?></span>
                    </div>
                    <div class="pg-item">
                        <span class="pg-label">Mode of Admission</span>
                        <span class="pg-value"><?= htmlspecialchars($p['mode_of_admission'] ?: '—') ?></span>
                    </div>
                    <div class="pg-item">
                        <span class="pg-label">Unit / Ward / Bed</span>
                        <span class="pg-value">
                            <?= htmlspecialchars($p['unit'] ?: '—') ?> /
                            <?= htmlspecialchars($p['ward'] ?: '—') ?> /
                            <?= htmlspecialchars($p['bed']  ?: '—') ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- ============ COLUMN 4: STATUS (Hospital Reg + Discharge + Status) ============ -->
            <div class="profile-col">
                <div class="ps-title">
                    <i class="fas fa-clipboard-check text-primary me-1"></i> Status
                </div>
                <div class="pg-stack">
                    <div class="pg-item">
                        <span class="pg-label">Hospital Reg No</span>
                        <span class="pg-value"><?= htmlspecialchars($p['hospital_reg_no'] ?: '—') ?></span>
                    </div>
                    <div class="pg-item">
                        <span class="pg-label">Discharge Date</span>
                        <span class="pg-value <?= $isActive ? 'text-muted' : 'text-danger' ?>">
                            <?= $isActive ? '— (still admitted)' : date('d M Y', strtotime($p['discharge_date'])) ?>
                        </span>
                    </div>
                    <div class="pg-item">
                        <span class="pg-label">Current Status</span>
                        <span class="pg-value">
                            <span class="badge bg-<?= $isActive ? 'success' : 'secondary' ?>">
                                <?= $isActive ? 'ACTIVE' : 'DISCHARGED' ?>
                            </span>
                        </span>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- ============ QUICK MODULE TABS ============ -->
<div class="module-tabs mb-3">
    <?php foreach ($modules as $key => $label): ?>
        <a href="../modules/<?= $key ?>.php?patient_id=<?= $id ?>"><?= $label ?></a>
    <?php endforeach; ?>
</div>

<!-- ============ RECORDS OVERVIEW — TWO CARDS, EACH 8+8 ============ -->
<div class="row g-3">

    <!-- ============ CARD 1: RECORDED INFORMATION ============ -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2 py-2">
                <span class="fw-semibold">
                    <i class="fas fa-database text-primary me-2"></i>
                    Recorded Information
                </span>
                <span class="badge bg-primary"><?= count($modulesWithData) ?> / <?= count($modules) ?></span>
            </div>
            <div class="card-body">
                <?php if (!$hasWithData): ?>
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-inbox fa-2x mb-2 d-block opacity-50"></i>
                        No records yet.
                    </div>
                <?php else: ?>
                    <div class="row g-2">
                        <div class="col-sm-6">
                            <div class="rec-grid">
                                <?php foreach ($withDataCol1 as $m): ?>
                                    <a class="rec-card rec-filled"
                                       href="../modules/<?= $m['key'] ?>.php?patient_id=<?= $id ?>">
                                        <div class="rec-icon"><i class="fas fa-check-circle text-success"></i></div>
                                        <div class="rec-body">
                                            <div class="rec-title"><?= htmlspecialchars($m['label']) ?></div>
                                            <div class="rec-meta">
                                                <span class="badge bg-primary">
                                                    <?= $m['count'] ?> record<?= $m['count'] > 1 ? 's' : '' ?>
                                                </span>
                                                <?php if ($m['last']): ?>
                                                    <small class="text-muted ms-1">
                                                        <i class="fas fa-clock"></i>
                                                        <?= date('d M', strtotime($m['last'])) ?>
                                                    </small>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="rec-arrow"><i class="fas fa-arrow-right"></i></div>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="rec-grid">
                                <?php foreach ($withDataCol2 as $m): ?>
                                    <a class="rec-card rec-filled"
                                       href="../modules/<?= $m['key'] ?>.php?patient_id=<?= $id ?>">
                                        <div class="rec-icon"><i class="fas fa-check-circle text-success"></i></div>
                                        <div class="rec-body">
                                            <div class="rec-title"><?= htmlspecialchars($m['label']) ?></div>
                                            <div class="rec-meta">
                                                <span class="badge bg-primary">
                                                    <?= $m['count'] ?> record<?= $m['count'] > 1 ? 's' : '' ?>
                                                </span>
                                                <?php if ($m['last']): ?>
                                                    <small class="text-muted ms-1">
                                                        <i class="fas fa-clock"></i>
                                                        <?= date('d M', strtotime($m['last'])) ?>
                                                    </small>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="rec-arrow"><i class="fas fa-arrow-right"></i></div>
                                    </a>
                                <?php endforeach; ?>
                                <?php if (empty($withDataCol2)): ?>
                                    <div class="text-muted small text-center py-3">
                                        <i class="fas fa-ellipsis-h"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php if ($withDataMore > 0): ?>
                        <div class="rec-more">
                            <i class="fas fa-ellipsis-h me-1"></i>
                            + <?= $withDataMore ?> more module<?= $withDataMore > 1 ? 's' : '' ?> with data
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ============ CARD 2: MISSING INFORMATION ============ -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2 py-2">
                <span class="fw-semibold">
                    <i class="fas fa-folder-open text-warning me-2"></i>
                    Missing Information
                </span>
                <span class="badge bg-warning text-dark"><?= count($modulesEmpty) ?></span>
            </div>
            <div class="card-body">
                <?php if (!$hasEmpty): ?>
                    <div class="text-center text-success py-4">
                        <i class="fas fa-check-double fa-2x mb-2 d-block"></i>
                        <strong>All modules completed!</strong>
                        <div class="text-muted small mt-1">Every module has data.</div>
                    </div>
                <?php else: ?>
                    <div class="row g-2">
                        <div class="col-sm-6">
                            <div class="rec-grid">
                                <?php foreach ($emptyCol1 as $m): ?>
                                    <a class="rec-card rec-empty"
                                       href="../modules/<?= $m['key'] ?>.php?patient_id=<?= $id ?>">
                                        <div class="rec-icon"><i class="fas fa-plus-circle text-warning"></i></div>
                                        <div class="rec-body">
                                            <div class="rec-title"><?= htmlspecialchars($m['label']) ?></div>
                                            <div class="rec-meta"><small class="text-muted">Click to add</small></div>
                                        </div>
                                        <div class="rec-arrow"><i class="fas fa-arrow-right"></i></div>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="rec-grid">
                                <?php foreach ($emptyCol2 as $m): ?>
                                    <a class="rec-card rec-empty"
                                       href="../modules/<?= $m['key'] ?>.php?patient_id=<?= $id ?>">
                                        <div class="rec-icon"><i class="fas fa-plus-circle text-warning"></i></div>
                                        <div class="rec-body">
                                            <div class="rec-title"><?= htmlspecialchars($m['label']) ?></div>
                                            <div class="rec-meta"><small class="text-muted">Click to add</small></div>
                                        </div>
                                        <div class="rec-arrow"><i class="fas fa-arrow-right"></i></div>
                                    </a>
                                <?php endforeach; ?>
                                <?php if (empty($emptyCol2)): ?>
                                    <div class="text-muted small text-center py-3">
                                        <i class="fas fa-ellipsis-h"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php if ($emptyMore > 0): ?>
                        <div class="rec-more">
                            <i class="fas fa-ellipsis-h me-1"></i>
                            + <?= $emptyMore ?> more module<?= $emptyMore > 1 ? 's' : '' ?> to complete
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>

<!-- ============ STYLES ============ -->
<style>
/* ============ UNIFORM ICON BUTTONS ============ */
.pc-actions-view { display: flex; gap: 8px; flex-wrap: wrap; }
.btn-icon {
    width: 34px; height: 34px; padding: 0;
    display: inline-flex; align-items: center; justify-content: center;
    border-radius: 7px; font-size: 13px; color: #fff; border: none;
    transition: transform .15s, box-shadow .15s, filter .15s;
}
.btn-icon:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 10px rgba(0,0,0,.18);
    color: #fff; filter: brightness(1.05);
}
.btn-icon.disabled { opacity: .4; pointer-events: none; }

/* ============ PROFILE — 4 COLUMNS + CAMBRIA + BLACK ============ */
.profile-4col {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 10px;
    font-family: Cambria, Georgia, "Times New Roman", serif;
}
.profile-col {
    padding: 10px 12px;
    background: #f8fafc;
    border-radius: 8px;
    border-left: 3px solid #2563eb;
    min-width: 0;
    height: 100%;
}
.ps-title {
    font-family: Cambria, Georgia, "Times New Roman", serif;
    font-size: 12px;
    font-weight: 600;
    color: #1e40af;
    text-transform: uppercase;
    letter-spacing: .5px;
    margin-bottom: 8px;
    padding-bottom: 5px;
    border-bottom: 1px solid #dbeafe;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.pg-stack {
    display: flex;
    flex-direction: column;
    gap: 6px;
}
.pg-item {
    display: flex;
    flex-direction: column;
    gap: 0;
    min-width: 0;
}
.pg-label {
    font-family: Cambria, Georgia, "Times New Roman", serif;
    font-size: 10.5px;
    color: #000000;
    text-transform: uppercase;
    letter-spacing: .3px;
    font-weight: 400;
    line-height: 1.2;
    opacity: .65;
}
.pg-value {
    font-family: Cambria, Georgia, "Times New Roman", serif;
    font-size: 14.5px;
    color: #000000;
    font-weight: 400;
    line-height: 1.3;
    word-break: break-word;
    margin-top: 1px;
}
.pg-value.text-primary { color: #1d4ed8 !important; }
.pg-value.text-muted { color: #6b7280 !important; }
.pg-value.text-danger { color: #b91c1c !important; }
.pg-address {
    font-size: 14.5px;
    line-height: 1.4;
}

/* ============ RECORD CARDS ============ */
.rec-grid { display: flex; flex-direction: column; gap: 6px; }
.rec-card {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 6px 10px;
    border: 1px solid #e2e8f0;
    border-radius: 7px;
    text-decoration: none;
    color: inherit;
    transition: .15s;
    background: #fff;
    min-width: 0;
    font-family: Cambria, Georgia, "Times New Roman", serif;
}
.rec-card:hover {
    border-color: #2563eb;
    background: #f8fafc;
    transform: translateX(2px);
    box-shadow: 0 2px 8px rgba(37,99,235,.10);
    color: inherit;
}
.rec-card.rec-filled { border-left: 3px solid #16a34a; }
.rec-card.rec-empty  { border-left: 3px solid #f59e0b; }
.rec-icon { width: 18px; text-align: center; font-size: 13px; flex-shrink: 0; }
.rec-body { flex: 1; min-width: 0; }
.rec-title {
    font-family: Cambria, Georgia, "Times New Roman", serif;
    font-size: 13px;
    font-weight: 400;
    color: #000;
    line-height: 1.3;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.rec-meta { margin-top: 1px; font-size: 10.5px; line-height: 1.3; }
.rec-meta .badge { font-size: 9.5px; padding: 1px 6px; border-radius: 10px; }
.rec-arrow { color: #94a3b8; font-size: 9px; transition: .15s; flex-shrink: 0; }
.rec-card:hover .rec-arrow { color: #2563eb; transform: translateX(2px); }

.rec-more {
    text-align: center;
    font-size: 11px;
    color: #000;
    padding: 6px 0 0;
    font-style: italic;
    font-family: Cambria, Georgia, "Times New Roman", serif;
}

/* ============ RESPONSIVE ============ */
@media (max-width: 1200px) {
    .profile-4col { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
@media (max-width: 768px) {
    .profile-4col { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; }
    .pg-value { font-size: 14px; }
}
@media (max-width: 576px) {
    .btn-icon { width: 30px; height: 30px; font-size: 12px; }
    .profile-4col { grid-template-columns: 1fr; gap: 8px; }
    .pg-value { font-size: 13.5px; }
    .pg-label { font-size: 10px; }
    .rec-card { padding: 6px 8px; }
    .rec-title { font-size: 12px; }
    .rec-icon { font-size: 12px; }
    .ps-title { font-size: 11px; }
}
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>