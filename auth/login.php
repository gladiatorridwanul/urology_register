<?php
require_once __DIR__ . '/../config/database.php';

if (isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND status = 1");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name']    = $user['name'];
            $_SESSION['email']   = $user['email'];
            $_SESSION['role']    = $user['role'];
            header('Location: ' . BASE_URL . 'index.php');
            exit;
        } else {
            $error = 'Invalid credentials';
        }
    } else {
        $error = 'Invalid credentials';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login — Urology Patient Registry | Dhaka Medical College Hospital</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
body {
    background: linear-gradient(135deg, #2563eb 0%, #7c3aed 100%);
    min-height: 100vh;
    display: flex;
    align-items: center;
    font-family: 'Segoe UI', system-ui, sans-serif;
}
.login-card {
    max-width: 440px;
    margin: auto;
    border-radius: 16px;
    box-shadow: 0 20px 60px rgba(0,0,0,.3);
    border: none;
}
.login-card .card-body { padding: 40px 36px; }

/* ============ LOGO ============ */
.logo-circle {
    width: 110px;
    height: 110px;
    background: #fff;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 18px;
    color: #2563eb;
    font-size: 36px;
    overflow: hidden;
    border: 3px solid #e0e7ff;
    box-shadow: 0 6px 20px rgba(37,99,235,.20);
}
.logo-circle img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    padding: 6px;
}

/* ============ TEXT ============ */
.hospital-name {
    font-size: 15px;
    font-weight: 600;
    color: #2563eb;
    letter-spacing: .3px;
    line-height: 1.3;
}
.registry-title {
    font-size: 1.25rem;
    font-weight: 700;
    color: #1e293b;
}
.subtitle-text {
    font-size: 13px;
    color: #64748b;
}
</style>
</head>
<body>
<div class="container">
    <div class="card login-card">
        <div class="card-body">

            <!-- ============ DMCH LOGO ============ -->
            <div class="logo-circle">
                <img src="<?= BASE_URL ?>assets/images/logo.png" alt="Dhaka Medical College Hospital">
            </div>

            <!-- ============ HOSPITAL + APP NAME ============ -->
            <h4 class="text-center registry-title mb-1">Urology Patient Registry</h4>
            <h6 class="text-center hospital-name mb-2">
                 Dhaka Medical College Hospital
            </h6>
            <p class="text-center subtitle-text mb-4">Sign in to your account</p>

            <!-- ============ ERROR ============ -->
            <?php if ($error): ?>
                <div class="alert alert-danger py-2 small">
                    <i class="fas fa-exclamation-circle me-1"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <!-- ============ LOGIN FORM ============ -->
            <form method="POST" autocomplete="off">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Email</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light">
                            <i class="fas fa-envelope text-muted"></i>
                        </span>
                        <input type="email" name="email" class="form-control"
                               placeholder="Enter your email" required autofocus>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label small fw-semibold">Password</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light">
                            <i class="fas fa-lock text-muted"></i>
                        </span>
                        <input type="password" name="password" id="loginPassword"
                               class="form-control" placeholder="Enter your password" required>
                        <button type="button" class="btn btn-outline-secondary toggle-pw"
                                data-target="loginPassword" tabindex="-1">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <button class="btn btn-primary w-100 py-2 fw-semibold">
                    <i class="fas fa-sign-in-alt me-1"></i> Sign In
                </button>
            </form>

            <!-- ============ FOOTER ============ -->
            <div class="mt-4 text-center text-muted small">
                <i class="fas fa-info-circle me-1"></i>
                Default: <code>admin@urology.com</code> / <code>admin123</code>
            </div>

            <div class="text-center mt-3 small text-muted">
                <i class="fas fa-shield-alt me-1"></i>
                Authorized personnel only
            </div>

        </div>
    </div>
</div>

<!-- ============ SHOW/HIDE PASSWORD ============ -->
<script>
document.querySelectorAll('.toggle-pw').forEach(function (btn) {
    btn.addEventListener('click', function () {
        const input = document.getElementById(this.dataset.target);
        const icon  = this.querySelector('i');
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.replace('fa-eye', 'fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.replace('fa-eye-slash', 'fa-eye');
        }
    });
});
</script>

</body>
</html>