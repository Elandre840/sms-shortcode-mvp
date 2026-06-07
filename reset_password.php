<?php
session_start();

if (isset($_SESSION['user'])) {
    header('Location: dashboard/dashboard.php');
    exit();
}

require_once __DIR__ . '/includes/db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        try {
            $pdo = getDb();
            $technician = findTechnicianByEmail($pdo, $email);

            if (!$technician) {
                $error = 'No account found for that email.';
            } else {
                $update = $pdo->prepare('UPDATE technicians SET `password` = ? WHERE id = ?');
                $update->execute([password_hash($password, PASSWORD_DEFAULT), (int)$technician['id']]);

                header('Location: login.php?reset=1');
                exit();
            }
        } catch (PDOException $e) {
            $error = 'Could not reset password. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body style="background:#f4f6f9;">

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-4">
            <div class="card shadow p-4">
                <h3 class="text-center mb-4">Reset Password</h3>

                <?php if ($error !== ''): ?>
                    <div class="alert alert-danger text-center"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="POST">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control mb-3"
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>

                    <label class="form-label">New password</label>
                    <input type="password" name="password" class="form-control mb-3" minlength="6" required>

                    <label class="form-label">Confirm password</label>
                    <input type="password" name="confirm_password" class="form-control mb-3" minlength="6" required>

                    <button type="submit" class="btn btn-primary w-100">Update Password</button>
                </form>

                <a href="login.php" class="btn btn-outline-secondary w-100 mt-3">Back to Login</a>
            </div>
        </div>
    </div>
</div>

</body>
</html>
