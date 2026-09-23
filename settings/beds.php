<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();
$pageTitle = 'Beds';

$msg = '';
$msgType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $ward_id = intval($_POST['ward_id'] ?? 0);
        $name    = trim($_POST['name'] ?? '');
        $desc    = trim($_POST['description'] ?? '');

        if (!$ward_id || !$name) {
            $msg = 'Ward and Bed name are required.';
            $msgType = 'danger';
        } else {
            $chk = $conn->prepare("SELECT id FROM beds WHERE ward_id=? AND name=?");
            $chk->bind_param('is', $ward_id, $name);
            $chk->execute();
            $chk->store_result();
            if ($chk->num_rows > 0) {
                $msg = 'Bed already exists in that ward.';
                $msgType = 'danger';
            } else {
                $stmt = $conn->prepare("INSERT INTO beds (ward_id, name, description) VALUES (?,?,?)");
                $stmt->bind_param('iss', $ward_id, $name, $desc);
                $stmt->execute();
                header("Location: beds.php?saved=1"); exit;
            }
        }
    }

    if ($action === 'update') {
        $id      = intval($_POST['id'] ?? 0);
        $ward_id = intval($_POST['ward_id'] ?? 0);
        $name    = trim($_POST['name'] ?? '');
        $desc    = trim($_POST['description'] ?? '');

        if (!$id || !$ward_id || !$name) {
            $msg = 'All required fields must be filled.';
            $msgType = 'danger';
        } else {
            $stmt = $conn->prepare("UPDATE beds SET ward_id=?, name=?, description=? WHERE id=?");
            $stmt->bind_param('issi', $ward_id, $name, $desc, $id);
            $stmt->execute();
            header("Location: beds.php?saved=1"); exit;
        }
    }

    if ($action === 'toggle') {
        $id = intval($_POST['id'] ?? 0);
        $conn->query("UPDATE beds SET status = 1 - status WHERE id=$id");
        header("Location: beds.php?saved=1"); exit;
    }
}

if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM beds WHERE id=$id");
    header("Location: beds.php?deleted=1"); exit;
}

$wards = $conn->query("
    SELECT w.id, w.name, u.name AS unit_name 
    FROM wards w LEFT JOIN units u ON u.id=w.unit_id 
    WHERE w.status=1 ORDER BY u.name, w.name
");

$filterWard = intval($_GET['ward_id'] ?? 0);
$whereSQL = $filterWard ? "WHERE b.ward_id = $filterWard" : '';

$beds = $conn->query("
    SELECT b.*, w.name AS ward_name, u.name AS unit_name
    FROM beds b
    LEFT JOIN wards w ON w.id = b.ward_id
    LEFT JOIN units u ON u.id = w.unit_id
    $whereSQL
    ORDER BY u.name, w.name, b.name
");

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<div class="d-flex justify-content-between mb-3 flex-wrap gap-2">
    <div>
        <h5 class="mb-0"><i class="fas fa-bed text-primary me-2"></i>Beds</h5>
        <small class="text-muted">Manage beds inside wards.</small>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createBedModal">
        <i class="fas fa-plus me-1"></i> New Bed
    </button>
</div>

<?php if (isset($_GET['saved'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle me-1"></i> Saved.
        <button class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<?php if (isset($_GET['deleted'])): ?>
    <div class="alert alert-warning alert-dismissible fade show">
        <i class="fas fa-trash me-1"></i> Bed deleted.
        <button class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<?php if ($msg): ?>
    <div class="alert alert-<?= $msgType ?> alert-dismissible fade show"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="fas fa-list me-2 text-primary"></i>All Beds</span>
        <span class="badge bg-primary"><?= $beds->num_rows ?></span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead>
                <tr>
                    <th style="width:60px">#</th>
                    <th>Unit</th>
                    <th>Ward</th>
                    <th>Bed</th>
                    <th>Description</th>
                    <th style="width:100px">Status</th>
                    <th style="width:180px" class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php $i = 1; while ($b = $beds->fetch_assoc()): ?>
                <tr class="<?= !$b['status'] ? 'table-warning' : '' ?>">
                    <td><?= $i++ ?></td>
                    <td><span class="badge bg-light text-dark"><?= htmlspecialchars($b['unit_name'] ?? '—') ?></span></td>
                    <td><?= htmlspecialchars($b['ward_name'] ?? '—') ?></td>
                    <td><strong><?= htmlspecialchars($b['name']) ?></strong></td>
                    <td><?= htmlspecialchars($b['description'] ?: '—') ?></td>
                    <td>
                        <?php if ($b['status']): ?>
                            <span class="badge bg-success">Active</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Inactive</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end text-nowrap">
                        <button class="btn btn-sm btn-outline-primary"
                                data-bs-toggle="modal" data-bs-target="#editBedModal"
                                data-id="<?= $b['id'] ?>"
                                data-ward-id="<?= $b['ward_id'] ?>"
                                data-name="<?= htmlspecialchars($b['name'], ENT_QUOTES) ?>"
                                data-desc="<?= htmlspecialchars($b['description'] ?? '', ENT_QUOTES) ?>">
                            <i class="fas fa-edit"></i>
                        </button>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="action" value="toggle">
                            <input type="hidden" name="id" value="<?= $b['id'] ?>">
                            <button class="btn btn-sm btn-outline-<?= $b['status'] ? 'secondary' : 'success' ?>">
                                <i class="fas fa-<?= $b['status'] ? 'toggle-on' : 'toggle-off' ?>"></i>
                            </button>
                        </form>
                        <a href="?delete=<?= $b['id'] ?>" class="btn btn-sm btn-outline-danger confirm-delete">
                            <i class="fas fa-trash"></i>
                        </a>
                    </td>
                </tr>
            <?php endwhile; if ($i === 1): ?>
                <tr><td colspan="7" class="text-center text-muted py-3">No beds yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>

<!-- CREATE BED MODAL -->
<div class="modal fade" id="createBedModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <input type="hidden" name="action" value="create">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-bed me-2 text-primary"></i>New Bed</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body row g-3">
                <div class="col-12">
                    <label class="form-label">Ward <span class="text-danger">*</span></label>
                    <select name="ward_id" class="form-select" required>
                        <option value="">— Select ward —</option>
                        <?php $wards->data_seek(0); while ($w = $wards->fetch_assoc()): ?>
                            <option value="<?= $w['id'] ?>"><?= htmlspecialchars($w['unit_name']) ?> / <?= htmlspecialchars($w['name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Bed Name / Number <span class="text-danger">*</span></label>
                    <input name="name" class="form-control" required>
                </div>
                <div class="col-12">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary"><i class="fas fa-save me-1"></i> Create</button>
            </div>
        </form>
    </div>
</div>

<!-- EDIT BED MODAL -->
<div class="modal fade" id="editBedModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" id="edit_bed_id">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit me-2 text-primary"></i>Edit Bed</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body row g-3">
                <div class="col-12">
                    <label class="form-label">Ward <span class="text-danger">*</span></label>
                    <select name="ward_id" id="edit_bed_ward" class="form-select" required>
                        <?php $wards->data_seek(0); while ($w = $wards->fetch_assoc()): ?>
                            <option value="<?= $w['id'] ?>"><?= htmlspecialchars($w['unit_name']) ?> / <?= htmlspecialchars($w['name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Bed Name / Number <span class="text-danger">*</span></label>
                    <input name="name" id="edit_bed_name" class="form-control" required>
                </div>
                <div class="col-12">
                    <label class="form-label">Description</label>
                    <textarea name="description" id="edit_bed_desc" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary"><i class="fas fa-save me-1"></i> Update</button>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('editBedModal').addEventListener('show.bs.modal', function (e) {
    const b = e.relatedTarget;
    document.getElementById('edit_bed_id').value    = b.dataset.id;
    document.getElementById('edit_bed_ward').value  = b.dataset.wardId;
    document.getElementById('edit_bed_name').value  = b.dataset.name;
    document.getElementById('edit_bed_desc').value  = b.dataset.desc;
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>