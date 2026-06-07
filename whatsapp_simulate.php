<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>WhatsApp Simulator</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

    <style>
        body { background:#f4f6f9; }
        .card { border-radius: 14px; }
        .btn-primary { border-radius: 8px; }
        .shadow-soft { box-shadow: 0 8px 18px rgba(0,0,0,.08); }
        .form-control, .form-select { border-radius: 8px; padding: 10px; }
        textarea { resize: none; }
    </style>
</head>

<body>

<nav class="navbar navbar-dark bg-dark px-4">
    <span class="navbar-brand fw-bold">
        <i class="bi bi-whatsapp me-2"></i>WhatsApp Simulator
    </span>

    <div class="d-flex gap-2">
        <a class="btn btn-outline-light btn-sm" href="dashboard/dashboard.php">
            <i class="bi bi-speedometer2 me-1"></i>Dashboard
        </a>

        <a class="btn btn-outline-light btn-sm" href="login.php">
            <i class="bi bi-house-door me-1"></i>Home
        </a>

        <a class="btn btn-outline-light btn-sm" href="dashboard/reports.php">
            <i class="bi bi-bar-chart me-1"></i>Reports
        </a>
    </div>
</nav>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-soft p-4">
                <h4 class="mb-4">
                    <i class="bi bi-chat-left-text me-2"></i>Send Test WhatsApp
                </h4>

                <form method="POST" action="whatsapp_simulate_handler.php">

                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="full_name" class="form-control" placeholder="e.g John Smith" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Sender Number</label>
                        <input type="text" name="sender" class="form-control" placeholder="e.g +27820000000" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Type</label>
                        <select name="message_type" class="form-select">
                            <option value="text">Message</option>
                            <option value="call">Call</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Message / Call Notes</label>
                        <textarea name="message" class="form-control" rows="4" placeholder="Enter message or call notes..." required></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <select name="category" class="form-select">
                            <option value="ICT">ICT</option>
                            <option value="ICT-CONSUMABLE">ICT-CONSUMABLE</option>
                            <option value="NETWORK">NETWORK</option>
                            <option value="SYSTEMS">SYSTEMS</option>
                            <option value="General">General</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">User Email (optional)</label>
                        <input type="email" name="user_email" class="form-control" placeholder="user@example.com">
                    </div>

                    <div class="d-grid">
                        <button class="btn btn-success btn-lg">
                            <i class="bi bi-send-fill me-1"></i>Send WhatsApp
                        </button>
                    </div>

                </form>

            </div>
        </div>
    </div>
</div>

</body>
</html>