<?php
session_start();

$user = $_SESSION['sms_user'] ?? ($_SESSION['user'] ?? null);
if (!$user) {
    header("Location: ../login.php");
    exit();
}

// TODO: Update with your database credentials
$db_host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "sms_shortcode";

try {
    $pdo = new PDO("mysql:host={$db_host};dbname={$db_name}", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed.");
}

// Optional date filters
$from = $_GET['from'] ?? '';
$to   = $_GET['to'] ?? '';

$where = "";
$params = [];

if ($from && $to) {
    $where = "WHERE created_at BETWEEN ? AND ?";
    $params[] = $from . " 00:00:00";
    $params[] = $to   . " 23:59:59";
}

// ==============================
// ✅ EXPORT MODE (RAW/SUMMARY CSV)
// ==============================
$export = $_GET['export'] ?? '';
if ($export === 'raw' || $export === 'summary') {

    $filename = "sms_" . $export . "_export_" . date("Ymd_His") . ".csv";
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename);

    $out = fopen('php://output', 'w');

    if ($export === 'raw') {
        fputcsv($out, ["id","sender","message","category","status","message_id","source","created_at"]);

        $sql = "SELECT id, sender, message, category, status, message_id, source, created_at
                FROM inbound_messages " . ($where ? $where : "") . "
                ORDER BY created_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($out, $r);
        }

    } else {
        // SUMMARY
        fputcsv($out, ["SUMMARY REPORT"]);
        if ($from && $to) fputcsv($out, ["Date Range", $from, $to]);
        fputcsv($out, []);

        // Total
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM inbound_messages " . ($where ? $where : ""));
        $stmt->execute($params);
        $total = (int)$stmt->fetchColumn();
        fputcsv($out, ["Total Messages", $total]);
        fputcsv($out, []);

        // Status totals
        fputcsv($out, ["STATUS TOTALS"]);
        fputcsv($out, ["Status", "Count"]);
        $stmt = $pdo->prepare("SELECT status, COUNT(*) total FROM inbound_messages " . ($where ? $where : "") . " GROUP BY status");
        $stmt->execute($params);
        while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($out, [$r['status'], $r['total']]);
        }
        fputcsv($out, []);

        // Category totals
        fputcsv($out, ["CATEGORY TOTALS"]);
        fputcsv($out, ["Category", "Count"]);
        $stmt = $pdo->prepare("SELECT category, COUNT(*) total FROM inbound_messages " . ($where ? $where : "") . " GROUP BY category ORDER BY total DESC");
        $stmt->execute($params);
        while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($out, [$r['category'], $r['total']]);
        }
        fputcsv($out, []);

        // Daily totals
        fputcsv($out, ["DAILY TOTALS"]);
        fputcsv($out, ["Day", "Count"]);
        $sqlDaily = "
            SELECT DATE(created_at) day, COUNT(*) total
            FROM inbound_messages
            " . ($where ? $where : "WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)") . "
            GROUP BY DATE(created_at)
            ORDER BY day ASC
        ";
        $stmt = $pdo->prepare($sqlDaily);
        $stmt->execute($params);
        while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($out, [$r['day'], $r['total']]);
        }
    }

    fclose($out);
    exit();
}

// ==============================
// ✅ CHART DATA (NORMAL PAGE VIEW)
// ==============================

// Daily trend (last 30 days if no filter)
$sqlDaily = "
    SELECT DATE(created_at) AS day, COUNT(*) AS total
    FROM inbound_messages
    " . ($where ? $where : "WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)") . "
    GROUP BY DATE(created_at)
    ORDER BY day ASC
";
$stmt = $pdo->prepare($sqlDaily);
$stmt->execute($params);
$daily = $stmt->fetchAll(PDO::FETCH_ASSOC);
$dailyLabels = array_map(fn($r) => $r['day'], $daily);
$dailyCounts = array_map(fn($r) => (int)$r['total'], $daily);

// Category distribution
$sqlCat = "
    SELECT category, COUNT(*) AS total
    FROM inbound_messages
    " . ($where ? $where : "") . "
    GROUP BY category
    ORDER BY total DESC
";
$stmt = $pdo->prepare($sqlCat);
$stmt->execute($params);
$cats = $stmt->fetchAll(PDO::FETCH_ASSOC);
$catLabels = array_map(fn($r) => $r['category'], $cats);
$catCounts = array_map(fn($r) => (int)$r['total'], $cats);

// Status distribution
$sqlStatus = "
    SELECT status, COUNT(*) AS total
    FROM inbound_messages
    " . ($where ? $where : "") . "
    GROUP BY status
";
$stmt = $pdo->prepare($sqlStatus);
$stmt->execute($params);
$statuses = $stmt->fetchAll(PDO::FETCH_ASSOC);
$statusLabels = array_map(fn($r) => $r['status'], $statuses);
$statusCounts = array_map(fn($r) => (int)$r['total'], $statuses);

// Total count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM inbound_messages " . ($where ? $where : ""));
$stmt->execute($params);
$total = (int)$stmt->fetchColumn();

$queryParams = "";
if ($from && $to) $queryParams = "&from=" . urlencode($from) . "&to=" . urlencode($to);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Reports</title>

    <!-- ✅ Bootstrap -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">

    <!-- ✅ Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

    <style>
        body { background:#f4f6f9; }
        .card { border-radius: 12px; }
        .chart-box { height: 320px; }
    </style>
</head>
<body>

<nav class="navbar navbar-dark bg-dark px-4">
    <span class="navbar-brand fw-bold">📊 Reports</span>

    <div class="d-flex gap-2">
        <a class="btn btn-sm btn-outline-light" href="dashboard.php">⬅ Dashboard</a>
        <a class="btn btn-sm btn-outline-light" href="../simulate.php">🧪 Simulator</a>
        <a class="btn btn-sm btn-danger" href="../logout.php">🚪 Logout</a>
    </div>
</nav>

<div class="container mt-4">

    <div class="card shadow p-4 mb-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h5 class="mb-1">Report Overview</h5>
                <div class="text-muted">Total messages in this view: <strong><?= $total ?></strong></div>
            </div>

            <form class="d-flex gap-2 flex-wrap" method="GET">
                <input type="date" name="from" class="form-control form-control-sm" value="<?= htmlspecialchars($from) ?>">
                <input type="date" name="to" class="form-control form-control-sm" value="<?= htmlspecialchars($to) ?>">
                <button class="btn btn-sm btn-primary" type="submit">Apply Filter</button>
                <a class="btn btn-sm btn-secondary" href="reports.php">Reset</a>
            </form>
        </div>

        <hr>

        <div class="d-flex gap-2 flex-wrap">
            <a class="btn btn-sm btn-outline-success" href="reports.php?export=raw<?= $queryParams ?>">⬇ Export RAW (CSV)</a>
            <a class="btn btn-sm btn-outline-primary" href="reports.php?export=summary<?= $queryParams ?>">⬇ Export SUMMARY (CSV)</a>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card shadow p-4">
                <h6 class="mb-3">📈 Messages per Day</h6>
                <div class="chart-box">
                    <canvas id="dailyChart"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card shadow p-4">
                <h6 class="mb-3">📊 Categories Distribution</h6>
                <div class="chart-box">
                    <canvas id="categoryChart"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card shadow p-4">
                <h6 class="mb-3">🟢 Status Breakdown</h6>
                <div class="chart-box">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card shadow p-4">
                <h6 class="mb-3">✅ What exports include</h6>
                <ul class="mb-0">
                    <li><strong>RAW CSV:</strong> every message row (audit + investigation friendly)</li>
                    <li><strong>SUMMARY CSV:</strong> totals by status/category + daily counts (management reporting)</li>
                    <li>Use filters to export “this week / last 7 days / month”</li>
                </ul>
            </div>
        </div>
    </div>

</div>

<script>
const dailyLabels = <?= json_encode($dailyLabels) ?>;
const dailyCounts = <?= json_encode($dailyCounts) ?>;

const catLabels = <?= json_encode($catLabels) ?>;
const catCounts = <?= json_encode($catCounts) ?>;

const statusLabels = <?= json_encode($statusLabels) ?>;
const statusCounts = <?= json_encode($statusCounts) ?>;

// Line chart
new Chart(document.getElementById('dailyChart'), {
    type: 'line',
    data: {
        labels: dailyLabels,
        datasets: [{
            label: 'Messages',
            data: dailyCounts,
            borderWidth: 2,
            tension: 0.35
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: { y: { beginAtZero: true } }
    }
});

// Bar chart
new Chart(document.getElementById('categoryChart'), {
    type: 'bar',
    data: {
        labels: catLabels,
        datasets: [{
            label: 'Messages',
            data: catCounts,
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: { y: { beginAtZero: true } }
    }
});

// Doughnut chart
new Chart(document.getElementById('statusChart'), {
    type: 'doughnut',
    data: {
        labels: statusLabels,
        datasets: [{
            label: 'Count',
            data: statusCounts
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false
    }
});
</script>

</body>
</html>
