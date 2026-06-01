
<?php
session_start();

// ✅ If already logged in, go to dashboard (prevents unnecessary loop)
if (isset($_SESSION['user'])) {
    header("Location: dashboard/dashboard.php");
    exit();
}

$error = "";

// ✅ Handle login submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // ✅ Hardcoded login (your current setup)
    // DEMO CREDENTIALS - Change these in production!
    if ($username === "demo" && $password === "demo123") {

        $_SESSION['user'] = $username;  // ✅ CRITICAL (this was the missing piece before)

        header("Location: dashboard/dashboard.php");
        exit();

    } else {
        $error = "Invalid username or password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login</title>

    <!-- ✅ Bootstrap (IMPORTANT: keep this exactly like this) -->
 <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>

<body style="background:#f4f6f9;">

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-4">

            <div class="card shadow p-4">

                <h3 class="text-center mb-4">🔐 Login</h3>

                <?php if ($error): ?>
                    <div class="alert alert-danger text-center">
                        <?= $error ?>
                    </div>
                <?php endif; ?>

                <!-- ✅ LOGIN FORM -->
                <form method="POST">

                    <label>Username</label>
                    <input type="text" name="username" class="form-control mb-3" required>

                    <label>Password</label>
                    <input type="password" name="password" class="form-control mb-3" required>

                    <button type="submit" class="btn btn-dark w-100">Login</button>

                </form>

            </div>

        </div>
    </div>
</div>

</body>
</html>
