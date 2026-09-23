<?php
require_once __DIR__ . '/../config/database.php';

/**
 * Check if a user is currently logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Redirect to login if not authenticated
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . 'auth/login.php');
        exit;
    }
}

/**
 * Require admin role
 */
function requireAdmin() {
    requireLogin();
    if ($_SESSION['role'] !== 'admin') {
        header('Location: ' . BASE_URL . 'index.php');
        exit;
    }
}

/**
 * Get current user data
 */
function currentUser() {
    return [
        'id'    => $_SESSION['user_id'] ?? null,
        'name'  => $_SESSION['name'] ?? '',
        'role'  => $_SESSION['role'] ?? '',
        'email' => $_SESSION['email'] ?? ''
    ];
}

/**
 * Auto-generate URO ID like URO-2026-00001
 */
function generateUroID($conn) {
    $year = date('Y');
    $result = $conn->query("SELECT COUNT(*) AS c FROM patients WHERE YEAR(created_at) = $year");
    $row = $result->fetch_assoc();
    $next = str_pad($row['c'] + 1, 5, '0', STR_PAD_LEFT);
    return "URO-{$year}-{$next}";
}

/**
 * Render Edit + Delete action buttons for a module record row.
 *
 * Usage in a list view:
 *     <?= rowActions('chief_complaints', $r['id']) ?>
 *
 * @param string $module        Module base name (e.g. 'chief_complaints')
 * @param int    $id            Record ID
 * @param bool   $confirmDelete Add "confirm-delete" class for JS confirmation
 * @param bool   $editBtn       Show Edit button
 * @param bool   $deleteBtn     Show Delete button
 * @return string               HTML for the <td>...</td> cell
 */
function rowActions($module, $id, $confirmDelete = true, $editBtn = true, $deleteBtn = true) {
    $id = (int)$id;
    $module = preg_replace('/[^a-z0-9_]/i', '', $module);
    $confirm = $confirmDelete ? 'confirm-delete' : '';
    $html = '<td class="text-nowrap">';
    if ($editBtn) {
        $html .= '<a href="' . $module . '_edit.php?id=' . $id . '" '
               . 'class="btn btn-sm btn-outline-primary" title="Edit">'
               . '<i class="fas fa-edit"></i></a> ';
    }
    if ($deleteBtn) {
        $html .= '<a href="' . $module . '_delete.php?id=' . $id . '" '
               . 'class="btn btn-sm btn-outline-danger ' . $confirm . '" title="Delete">'
               . '<i class="fas fa-trash"></i></a>';
    }
    $html .= '</td>';
    return $html;
}

/**
 * Render action buttons with Print + PDF + Edit + Delete.
 * Used for the discharge module.
 */
function rowActionsWithPdf($module, $id, $confirmDelete = true) {
    $id = (int)$id;
    $module = preg_replace('/[^a-z0-9_]/i', '', $module);
    $confirm = $confirmDelete ? 'confirm-delete' : '';
    return '<td class="text-nowrap">'
         . '<a href="' . $module . '_print.php?id=' . $id . '" target="_blank" class="btn btn-sm btn-outline-primary" title="Print"><i class="fas fa-print"></i></a> '
         . '<a href="' . $module . '_pdf.php?id=' . $id . '" class="btn btn-sm btn-outline-danger" title="PDF"><i class="fas fa-file-pdf"></i></a> '
         . '<a href="' . $module . '_edit.php?id=' . $id . '" class="btn btn-sm btn-outline-warning" title="Edit"><i class="fas fa-edit"></i></a> '
         . '<a href="' . $module . '_delete.php?id=' . $id . '" class="btn btn-sm btn-outline-danger ' . $confirm . '" title="Delete"><i class="fas fa-trash"></i></a>'
         . '</td>';
}
?>