
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>SMS Simulator</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- ✅ Bootstrap -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">

    <!-- ✅ Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

    <style>
        body { background:#f4f6f9; }

        .card {
            border-radius: 14px;
        }

        .btn-primary {
            border-radius: 8px;
        }

        .shadow-soft {
            box-shadow: 0 8px 18px rgba(0,0,0,.08);
        }

        .form-control, .form-select {
            border-radius: 8px;
            padding: 10px;
        }

        textarea {
            resize: none;
        }
    </style>
</head>

<body>

<!-- ✅ NAVBAR (MATCH DASHBOARD) -->
<nav class="navbar navbar-dark bg-dark px-4">
    <span class="navbar-brand fw-bold">
        <i class="bi bi-phone-fill me-2"></i>SMS Simulator
    </span>

    <div class="d-flex gap-2">
        <a class="btn btn-outline-light btn-sm" href="dashboard/dashboard.php">
            <i class="bi bi-speedometer2 me-1"></i>Dashboard
        </a>

        <a class="btn btn-outline-light btn-sm" href="index.php">
            <i class="bi bi-house-door me-1"></i>Home
        </a>

        <a class="btn btn-outline-light btn-sm" href="dashboard/reports.php">
            <i class="bi bi-bar-chart me-1"></i>Reports
        </a>
    </div>
</nav>


<!-- ✅ MAIN CONTAINER -->
<div class="container mt-5">

    <div class="row justify-content-center">
        <div class="col-lg-8">

            <!-- ✅ CARD -->
            <div class="card shadow-soft p-4">

                <h4 class="mb-4">
                    <i class="bi bi-phone-history me-2"></i>Send Test SMS
                </h4>

                <form method="POST" action="simulate_handler.php">

                    <!-- FULL NAME -->
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="full_name"
                               class="form-control"
                               placeholder="e.g John Smith"
                               required>
                    </div>

                    <!-- SENDER -->
                    <div class="mb-3">
                        <label class="form-label">Sender Number</label>
                        <input type="text" name="sender"
                               class="form-control"
                               placeholder="e.g 0820000000"
                               required>
                    </div>

                    <!-- MESSAGE -->
                    <div class="mb-3">
                        <label class="form-label">Message</label>
                        <textarea name="message"
                                  class="form-control"
                                  rows="4"
                                  placeholder="Enter SMS message..."
                                  required></textarea>
                    </div>

                    <!-- CATEGORY -->
                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <select name="category" class="form-select">
                            <option value="ICT">ICT</option>
                            <option value="ICT-CONSUMABLE">ICT-CONSUMABLE</option>
                            <option value="NETWORK">NETWORK</option>
                            <option value="SYSTEMS">ISISELE</option> <!-- ✅ ADDED -->
                            <option value="General">General</option>
                        </select>
                    </div>

                    <!-- BUTTON -->
                    <div class="d-grid">
                        <button class="btn btn-primary btn-lg">
                            <i class="bi bi-send-fill me-1"></i>Send SMS
                        </button>
                    </div>

                </form>

            </div>

        </div>
    </div>

</div>

</body>
</html>
