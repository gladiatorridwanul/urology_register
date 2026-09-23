<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$pageTitle = 'Dashboard';

// ============================================================
// STATS
// ============================================================
$stats = [
    'patients'   => $conn->query("SELECT COUNT(*) c FROM patients")->fetch_assoc()['c'],
    'active'     => $conn->query("SELECT COUNT(*) c FROM patients WHERE discharge_date IS NULL OR discharge_date = '' OR discharge_date = '0000-00-00'")->fetch_assoc()['c'],
    'discharged' => $conn->query("SELECT COUNT(*) c FROM patients WHERE discharge_date IS NOT NULL AND discharge_date != '' AND discharge_date != '0000-00-00'")->fetch_assoc()['c'],
    'doctors'    => $conn->query("SELECT COUNT(*) c FROM users WHERE role='doctor' AND status=1")->fetch_assoc()['c'],
    'operations' => $conn->query("SELECT COUNT(*) c FROM operations")->fetch_assoc()['c'],
    'followups'  => $conn->query("SELECT COUNT(*) c FROM followup")->fetch_assoc()['c'],
];

// ============================================================
// RECENT LISTS
// ============================================================
$recent            = $conn->query("SELECT * FROM patients ORDER BY id DESC LIMIT 5");
$activeRecent      = $conn->query("SELECT * FROM patients 
                                   WHERE discharge_date IS NULL OR discharge_date = '' OR discharge_date = '0000-00-00' 
                                   ORDER BY id DESC LIMIT 5");
$dischargedRecent  = $conn->query("SELECT * FROM patients 
                                   WHERE discharge_date IS NOT NULL AND discharge_date != '' AND discharge_date != '0000-00-00' 
                                   ORDER BY id DESC LIMIT 5");

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
?>

<!-- ============ HEADER ============ -->
<div class="d-flex justify-content-between mb-3 flex-wrap gap-2 align-items-start">
    <div>
        <h4 class="mb-1">Overview</h4>
        <p class="text-muted small mb-0">Quick snapshot of your urology registry.</p>
    </div>
</div>

<!-- ============ STAT CARDS ============ -->
<div class="row g-3 mb-4">
    <?php
    $cards = [
        ['Total Patients',     $stats['patients'],   'fa-users',          'primary'],
        ['Active Patients',    $stats['active'],     'fa-user-check',     'success'],
        ['Discharged',         $stats['discharged'], 'fa-user-slash',     'warning'],
        ['Registered Doctors', $stats['doctors'],    'fa-user-md',        'info'],
        ['Operations',         $stats['operations'], 'fa-procedures',     'danger'],
        ['Follow-ups',         $stats['followups'],  'fa-calendar-check', 'secondary'],
    ];
    foreach ($cards as $c): ?>
        <div class="col-6 col-md-6 col-lg-4">
            <div class="stat-card">
                <div class="stat-card-body">
                    <p><?= $c[0] ?></p>
                    <h3><?= $c[1] ?></h3>
                </div>
                <div class="icon-circle bg-<?= $c[3] ?>"><i class="fas <?= $c[2] ?>"></i></div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- ============ ACTIVE + DISCHARGED SIDE BY SIDE ============ -->
<div class="row g-3 mb-4">

    <!-- ============ ACTIVE PATIENTS ============ -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <i class="fas fa-user-check text-success me-2"></i>
                    <span class="fw-semibold">Active Patients</span>
                    <span class="badge bg-success ms-1"><?= $stats['active'] ?></span>
                </div>
                <a href="<?= BASE_URL ?>patients/index.php?filter=active"
                   class="btn btn-sm btn-outline-success">View All →</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 dash-table">
                        <thead>
                            <tr>
                                <th style="width:120px">URO ID</th>
                                <th>Name</th>
                                <th style="width:70px">Age</th>
                                <th style="width:110px">Visit</th>
                                <th style="width:60px"></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if ($activeRecent->num_rows === 0): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-3">
                                    <i class="fas fa-inbox me-1"></i> No active patients yet.
                                </td>
                            </tr>
                        <?php else: while ($p = $activeRecent->fetch_assoc()): ?>
                            <tr>
                                <td><span class="badge bg-light text-primary"><?= htmlspecialchars($p['uro_id']) ?></span></td>
                                <td>
                                    <div class="fw-semibold"><?= htmlspecialchars($p['name']) ?></div>
                                    <small class="text-muted d-md-none">
                                        <?= (int)$p['age'] ?> yrs · <?= htmlspecialchars($p['sex']) ?>
                                    </small>
                                </td>
                                <td><?= (int)$p['age'] ?></td>
                                <td><?= $p['current_visit_date'] ? date('d-m-Y', strtotime($p['current_visit_date'])) : '—' ?></td>
                                <td class="text-end">
                                    <a href="<?= BASE_URL ?>patients/view.php?id=<?= $p['id'] ?>"
                                       class="btn btn-sm btn-outline-success" title="Open">
                                        <i class="fas fa-arrow-right"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- ============ DISCHARGED PATIENTS ============ -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <i class="fas fa-user-slash text-warning me-2"></i>
                    <span class="fw-semibold">Discharged Patients</span>
                    <span class="badge bg-warning text-dark ms-1"><?= $stats['discharged'] ?></span>
                </div>
                <a href="<?= BASE_URL ?>patients/index.php?filter=discharged"
                   class="btn btn-sm btn-outline-warning">View All →</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 dash-table">
                        <thead>
                            <tr>
                                <th style="width:120px">URO ID</th>
                                <th>Name</th>
                                <th style="width:90px">Age/Sex</th>
                                <th style="width:110px">Discharge</th>
                                <th style="width:60px"></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if ($dischargedRecent->num_rows === 0): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-3">
                                    <i class="fas fa-inbox me-1"></i> No discharged patients yet.
                                </td>
                            </tr>
                        <?php else: while ($p = $dischargedRecent->fetch_assoc()): ?>
                            <tr>
                                <td><span class="badge bg-light text-primary"><?= htmlspecialchars($p['uro_id']) ?></span></td>
                                <td>
                                    <div class="fw-semibold"><?= htmlspecialchars($p['name']) ?></div>
                                    <small class="text-muted d-md-none">
                                        <?= (int)$p['age'] ?> yrs · <?= htmlspecialchars($p['sex']) ?>
                                    </small>
                                </td>
                                <td>
                                    <?= (int)$p['age'] ?> / <?= htmlspecialchars(substr($p['sex'], 0, 1)) ?>
                                </td>
                                <td><?= $p['discharge_date'] ? date('d-m-Y', strtotime($p['discharge_date'])) : '—' ?></td>
                                <td class="text-end">
                                    <a href="<?= BASE_URL ?>patients/view.php?id=<?= $p['id'] ?>"
                                       class="btn btn-sm btn-outline-warning" title="Open">
                                        <i class="fas fa-arrow-right"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- ============ RECENT PATIENTS (ALL) ============ -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <i class="fas fa-history text-primary me-2"></i>
            <span class="fw-semibold">Recent Patients</span>
        </div>
        <a href="<?= BASE_URL ?>patients/index.php" class="btn btn-sm btn-primary">
            View All →
        </a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 dash-table">
                <thead>
                    <tr>
                        <th style="width:130px">URO ID</th>
                        <th>Name</th>
                        <th style="width:100px">Age/Sex</th>
                        <th style="width:130px">Mobile</th>
                        <th style="width:110px">Visit</th>
                        <th style="width:60px"></th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($recent->num_rows === 0): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted py-3">
                            <i class="fas fa-inbox me-1"></i> No patients registered yet.
                        </td>
                    </tr>
                <?php else: while ($p = $recent->fetch_assoc()): ?>
                    <tr>
                        <td><span class="badge bg-light text-primary"><?= htmlspecialchars($p['uro_id']) ?></span></td>
                        <td>
                            <div class="fw-semibold"><?= htmlspecialchars($p['name']) ?></div>
                            <small class="text-muted d-md-none">
                                <?= htmlspecialchars($p['mobile']) ?>
                            </small>
                        </td>
                        <td><?= (int)$p['age'] ?> / <?= htmlspecialchars(substr($p['sex'], 0, 1)) ?></td>
                        <td class="d-none d-md-table-cell"><?= htmlspecialchars($p['mobile']) ?></td>
                        <td><?= $p['current_visit_date'] ? date('d-m-Y', strtotime($p['current_visit_date'])) : '—' ?></td>
                        <td class="text-end">
                            <a href="<?= BASE_URL ?>patients/view.php?id=<?= $p['id'] ?>"
                               class="btn btn-sm btn-outline-primary" title="Open">
                                <i class="fas fa-arrow-right"></i>
                            </a>
                        </td>
                    </tr>
                <?php endwhile; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ============ DASHBOARD STYLES ============ -->
<style>
/* ============ STAT CARDS ============ */
.stat-card {
    background: #fff;
    border-radius: 12px;
    padding: 18px 20px;
    box-shadow: 0 1px 6px rgba(0,0,0,.06);
    border: 1px solid #e9eef5;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    height: 100%;
    transition: transform .15s, box-shadow .15s;
}
.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 14px rgba(37,99,235,.10);
}
.stat-card-body p {
    margin: 0;
    color: #64748b;
    font-size: 11.5px;
    text-transform: uppercase;
    letter-spacing: .6px;
    font-weight: 600;
    line-height: 1.3;
}
.stat-card-body h3 {
    margin: 4px 0 0;
    font-size: 26px;
    font-weight: 700;
    color: #1e293b;
    line-height: 1;
}
.icon-circle {
    width: 48px;
    height: 48px;
    min-width: 48px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    color: #fff;
    flex-shrink: 0;
}

/* ============ DASHBOARD TABLES ============ */
.dash-table {
    font-size: 13px;
}
.dash-table thead th {
    background: #f8fafc;
    color: #475569;
    font-weight: 600;
    font-size: 11.5px;
    text-transform: uppercase;
    letter-spacing: .4px;
    padding: 10px 12px;
    border-bottom: 1px solid #e2e8f0;
    white-space: nowrap;
}
.dash-table tbody td {
    padding: 10px 12px;
    vertical-align: middle;
    border-bottom: 1px solid #f1f5f9;
}
.dash-table tbody tr:last-child td {
    border-bottom: none;
}
.dash-table .badge {
    font-size: 10.5px;
    font-weight: 600;
    padding: 4px 8px;
}
.dash-table .btn-sm {
    padding: 3px 8px;
    border-radius: 5px;
    font-size: 11px;
    line-height: 1;
}

/* ============ MOBILE RESPONSIVE ============ */
@media (max-width: 991px) {
    .stat-card-body h3 { font-size: 22px; }
    .icon-circle { width: 42px; height: 42px; min-width: 42px; font-size: 17px; }
}
@media (max-width: 576px) {
    .stat-card { padding: 14px 16px; }
    .stat-card-body p { font-size: 10.5px; }
    .stat-card-body h3 { font-size: 20px; }
    .icon-circle { width: 38px; height: 38px; min-width: 38px; font-size: 15px; }

    /* Force horizontal scroll on tables */
    .table-responsive {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    .dash-table {
        min-width: 480px;
        font-size: 12px;
    }
    .dash-table thead th { padding: 8px 10px; font-size: 10.5px; }
    .dash-table tbody td { padding: 8px 10px; }

    h4 { font-size: 1.15rem; }
}
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>