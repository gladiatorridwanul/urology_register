<?php
// Sidebar — Urology Patient Registry

$currentFile   = basename($_SERVER['PHP_SELF']);
$currentDir    = basename(dirname($_SERVER['PHP_SELF']));
$currentFilter = $_GET['filter'] ?? '';

$isRootScript = (
    $currentFile === 'index.php'
    && !in_array($currentDir, ['patients','admin','modules','auth','settings'], true)
);

$active = [
    'dashboard'   => false,
    'patients'    => false,
    'active'      => false,
    'discharged'  => false,
    'profile'     => false,
    'users'       => false,
    'units'       => false,
    'wards'       => false,
    'beds'        => false,
    'op_types'    => false,
    'op_subtypes' => false,
];

if ($isRootScript) {
    $active['dashboard'] = true;
} elseif ($currentDir === 'patients') {
    if ($currentFile === 'index.php') {
        if     ($currentFilter === 'active')     $active['active']     = true;
        elseif ($currentFilter === 'discharged') $active['discharged'] = true;
        else                                     $active['patients']   = true;
    } else {
        $active['patients'] = true;
    }
} elseif ($currentDir === 'settings') {
    if     ($currentFile === 'units.php')       $active['units']       = true;
    elseif ($currentFile === 'wards.php')       $active['wards']       = true;
    elseif ($currentFile === 'beds.php')        $active['beds']        = true;
    elseif ($currentFile === 'op_types.php')    $active['op_types']    = true;
    elseif ($currentFile === 'op_subtypes.php') $active['op_subtypes'] = true;
} elseif ($currentDir === 'admin') {
    if     ($currentFile === 'profile.php') $active['profile'] = true;
    elseif ($currentFile === 'users.php')   $active['users']   = true;
} elseif ($currentDir === 'modules') {
    $active['patients'] = true;
}

function navActive($key, $active) {
    return !empty($active[$key]) ? ' active' : '';
}

$settingsOpen = ($active['units'] || $active['wards'] || $active['beds'] || $active['op_types'] || $active['op_subtypes']);
?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="user-avatar">
            <i class="fas fa-user-md"></i>
        </div>
        <div class="user-info">
            <strong><?= htmlspecialchars($user['name']) ?></strong>
            <small><?= ucfirst($user['role']) ?></small>
        </div>
    </div>

    <nav class="sidebar-nav">
        <a href="<?= BASE_URL ?>index.php" class="nav-item<?= navActive('dashboard', $active) ?>">
            <i class="fas fa-tachometer-alt"></i> <span>Dashboard</span>
        </a>

        <a href="<?= BASE_URL ?>patients/index.php" class="nav-item<?= navActive('patients', $active) ?>">
            <i class="fas fa-users"></i> <span>Patients</span>
        </a>

        <a href="<?= BASE_URL ?>patients/index.php?filter=active" class="nav-item<?= navActive('active', $active) ?>">
            <i class="fas fa-user-check"></i> <span>Active Patients</span>
        </a>

        <a href="<?= BASE_URL ?>patients/index.php?filter=discharged" class="nav-item<?= navActive('discharged', $active) ?>">
            <i class="fas fa-user-slash"></i> <span>Discharged</span>
        </a>

        <!-- ============ SETTINGS (collapsible) ============ -->
        <?php if ($user['role'] === 'admin'): ?>
        <div class="nav-group<?= $settingsOpen ? ' is-open' : '' ?>" id="settingsGroup">
            <div class="nav-item nav-toggle" id="settingsToggle"
                 role="button" tabindex="0"
                 aria-expanded="<?= $settingsOpen ? 'true' : 'false' ?>">
                <i class="fas fa-cog"></i>
                <span>Settings</span>
                <i class="fas fa-chevron-right nav-chevron"></i>
            </div>

            <div class="nav-sub" id="settingsSub">
                <a href="<?= BASE_URL ?>settings/units.php" class="nav-sub-item<?= navActive('units', $active) ?>">
                    <i class="fas fa-building"></i> <span>Units</span>
                </a>
                <a href="<?= BASE_URL ?>settings/wards.php" class="nav-sub-item<?= navActive('wards', $active) ?>">
                    <i class="fas fa-hospital"></i> <span>Wards</span>
                </a>
                <a href="<?= BASE_URL ?>settings/beds.php" class="nav-sub-item<?= navActive('beds', $active) ?>">
                    <i class="fas fa-bed"></i> <span>Beds</span>
                </a>
                <a href="<?= BASE_URL ?>settings/op_types.php" class="nav-sub-item<?= navActive('op_types', $active) ?>">
                    <i class="fas fa-layer-group"></i> <span>Operation Types</span>
                </a>
                <a href="<?= BASE_URL ?>settings/op_subtypes.php" class="nav-sub-item<?= navActive('op_subtypes', $active) ?>">
                    <i class="fas fa-list-ul"></i> <span>Operation Sub-Types</span>
                </a>
            </div>
        </div>
        <?php endif; ?>

        <a href="<?= BASE_URL ?>admin/profile.php" class="nav-item<?= navActive('profile', $active) ?>">
            <i class="fas fa-user-circle"></i> <span>My Profile</span>
        </a>

        <?php if ($user['role'] === 'admin'): ?>
        <a href="<?= BASE_URL ?>admin/users.php" class="nav-item<?= navActive('users', $active) ?>">
            <i class="fas fa-user-cog"></i> <span>Manage Users</span>
        </a>
        <?php endif; ?>

        <a href="<?= BASE_URL ?>auth/logout.php" class="nav-item nav-logout">
            <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
        </a>
    </nav>
</aside>

<main class="main-content">
    <header class="topbar">
        <button class="btn btn-link d-lg-none" id="sidebarToggle"><i class="fas fa-bars"></i></button>
        <h5 class="mb-0"><?= $pageTitle ?? 'Dashboard' ?></h5>
        <div class="ms-auto d-flex align-items-center gap-3">
            <span class="badge bg-primary"><?= ucfirst($user['role']) ?></span>
        </div>
    </header>
    <div class="content-area">