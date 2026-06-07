
<?php
session_start();

if (isset($_SESSION['user'])) {
    header('Location: dashboard/dashboard.php');
    exit();
}

require_once __DIR__ . '/includes/db.php';

$error = '';
$success = '';

if (isset($_GET['registered'])) {
    $success = 'Registration successful. You can log in with your email and password.';
} elseif (isset($_GET['reset'])) {
    $success = 'Password updated. You can log in with your new password.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // Demo admin account — change before production (see SETUP.md)
    if ($username === 'demo' && hash_equals('demo123', $password)) {
        $_SESSION['user'] = 'demo';
        $_SESSION['role'] = 'admin';
        header('Location: dashboard/dashboard.php');
        exit();
    }

    try {
        $pdo = getDb();

        $stmt = $pdo->prepare("SELECT * FROM technicians WHERE email = ? LIMIT 1");
        $stmt->execute([$username]);
        $technician = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($technician) {
            $storedPassword = $technician['password'] ?? '';

            $passwordOk = false;

            // Supports current secure password_hash() registrations
            if ($storedPassword && password_verify($password, $storedPassword)) {
                $passwordOk = true;
            }
            // Optional fallback for any older MD5-based technician records
            elseif ($storedPassword && md5($password) === $storedPassword) {
                $passwordOk = true;
            }

            if ($passwordOk) {
                $_SESSION['user'] = $technician['email'];
                $_SESSION['role'] = 'technician';
                $_SESSION['technician_id'] = (int)$technician['id'];
                $_SESSION['technician_name'] = $technician['full_name'] ?? '';
                $_SESSION['technician_district'] = $technician['category'] ?? '';

                header('Location: dashboard/dashboard.php');
                exit();
            }
        }
    } catch (PDOException $e) {
        $error = 'Login unavailable. Please try again later.';
    }

    if ($error === '') {
        $error = 'Invalid email or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body style="background:#f4f6f9;">

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-4">

            <div class="card shadow p-4">

                <h3 class="text-center mb-4">Login</h3>

                <?php if ($success !== ''): ?>
                    <div class="alert alert-success text-center"><?= htmlspecialchars($success) ?></div>
                <?php endif; ?>

                <?php if ($error !== ''): ?>
                    <div class="alert alert-danger text-center"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="POST">

                    <label class="form-label">Email</label>
                    <input type="email" name="username" class="form-control mb-3" required autocomplete="username"
                           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">

                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control mb-3" required autocomplete="current-password">

                    <button type="submit" class="btn btn-dark w-100">Login</button>

                </form>

                <a href="register.php" class="btn btn-success w-100 mt-3">Register as Technician</a>
                <a href="reset_password.php" class="btn btn-link w-100 mt-1">Forgot password?</a>

            </div>

        </div>
    </div>
</div>

</body>
</html>
