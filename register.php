
<?php
session_start();

if (isset($_SESSION['user'])) {
    header('Location: dashboard/dashboard.php');
    exit();
}

require_once __DIR__ . '/includes/db.php';

$districts = [
    'Head Office', // ✅ ADDED
    'Alfred Nzo',
    'Amathole',
    'Buffalo City Metro',
    'Chris Hani',
    'Joe Gqabi',
    'Nelson Mandela Bay',
    'OR Tambo',
    'Sarah Baartman',
];

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';
    $name     = trim($_POST['name'] ?? '');
    $surname  = trim($_POST['surname'] ?? '');
    $district = trim($_POST['district'] ?? '');

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif ($name === '' || $surname === '') {
        $error = 'Name and surname are required.';
    } elseif (!in_array($district, $districts, true)) {
        $error = 'Please select a valid district.';
    } else {
        try {
            $pdo = getDb();

            $hash = password_hash($password, PASSWORD_DEFAULT);

            // ✅ Combine name + surname (fix)
            $full_name = trim($name . ' ' . $surname);

            // ✅ Insert using correct DB structure
            $stmt = $pdo->prepare(
                'INSERT INTO technicians (full_name, email, password, category, status) VALUES (?, ?, ?, ?, ?)'
            );
            $stmt->execute([$full_name, $email, $hash, $district, 'active']);

            header('Location: login.php?registered=1');
            exit();

        } catch (PDOException $e) {
            if (isset($e->errorInfo[1]) && (int)$e->errorInfo[1] === 1062) {
                $error = 'This email is already registered.';
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Technician Registration</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body style="background:#f4f6f9;">

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-5">

            <div class="card shadow p-4">

                <h3 class="text-center mb-4">Technician Registration</h3>

                <?php if ($error !== ''): ?>
                    <div class="alert alert-danger text-center"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="POST">

                    <label class="form-label">Email (username)</label>
                    <input type="email" name="email" class="form-control mb-3"
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>

                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control mb-3" minlength="6" required>

                    <label class="form-label">Confirm password</label>
                    <input type="password" name="confirm_password" class="form-control mb-3" minlength="6" required>

                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label">Name</label>
                            <input type="text" name="name" class="form-control mb-3"
                                   value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Surname</label>
                            <input type="text" name="surname" class="form-control mb-3"
                                   value="<?= htmlspecialchars($_POST['surname'] ?? '') ?>" required>
                        </div>
                    </div>

                    <label class="form-label">District</label>
                    <select name="district" class="form-select mb-3" required>
                        <option value="">Select district</option>
                        <?php foreach ($districts as $d): ?>
                            <option value="<?= htmlspecialchars($d) ?>"
                                <?= (($_POST['district'] ?? '') === $d) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($d) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <button type="submit" class="btn btn-success w-100 mb-3">Register</button>

                    <a href="login.php" class="btn btn-outline-secondary w-100">Back to Login</a>

                </form>

            </div>

        </div>
    </div>
</div>

</body>
</html>
