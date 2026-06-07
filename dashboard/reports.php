
<?php
session_start();

$user = $_SESSION['sms_user'] ?? ($_SESSION['user'] ?? null);
if (!$user) {
    header("Location: ../login.php");
    exit();
}

try {
    $pdo = new PDO("mysql:host=localhost;dbname=sms_shortcode", "root", "", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die("Database connection failed.");
}

function buildReportFilters(array $input): array
{
    $from = trim($input['from'] ?? '');
    $to = trim($input['to'] ?? '');
    $channel = trim($input['channel'] ?? '');
    $category = trim($input['category'] ?? '');

    $clauses = [];
    $params = [];

    if ($from !== '') {
        $clauses[] = "created_at >= ?";
        $params[] = $from . " 00:00:00";
    }
    if ($to !== '') {
        $clauses[] = "created_at <= ?";
        $params[] = $to . " 23:59:59";
    }
    if ($channel !== '') {
        $clauses[] = "source = ?";
        $params[] = $channel;
    }
    if ($category !== '') {
        $clauses[] = "category = ?";
        $params[] = $category;
    }

    $where = $clauses ? ('WHERE ' . implode(' AND ', $clauses)) : '';

    return compact('from', 'to', 'channel', 'category', 'where', 'params');
}

function fetchGrouped(PDO $pdo, string $sql, array $params): array
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function fetchScalar(PDO $pdo, string $sql, array $params): int
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return (int)$stmt->fetchColumn();
}

function chartSeries(array $rows, string $labelKey, string $valueKey): array
{
    return [
        'labels' => array_map(fn($r) => (string)$r[$labelKey], $rows),
        'counts' => array_map(fn($r) => (int)$r[$valueKey], $rows),
    ];
}

function statusBadgeClass(string $status): string
{
    $s = strtolower($status);
    if ($s === 'new') return 'bg-warning text-dark';
    if ($s === 'processed') return 'bg-info text-dark';
    if ($s === 'resolved') return 'bg-success';
    return 'bg-secondary';
}

$filters = buildReportFilters($_GET);
extract($filters);

$export = $_GET['export'] ?? '';
if ($export === 'raw' || $export === 'summary') {
    $filename = "sms_{$export}_export_" . date("Ymd_His") . ".csv";
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename);

    $out = fopen('php://output', 'w');

    if ($export === 'raw') {
        fputcsv($out, [
            'id', 'ref', 'sender', 'full_name', 'message', 'category', 'status',
            'channel', 'assigned_to', 'ticket_status', 'source', 'created_at',
        ]);

        $sql = "SELECT id, sender, full_name, message, category, status,
                       COALESCE(source, 'sms') AS channel,
                       'Unassigned' AS assigned_to,
                       '' AS ticket_status,
                       source, created_at
                FROM inbound_messages {$where}
                ORDER BY created_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        while ($r = $stmt->fetch()) {
            fputcsv($out, [
                $r['id'],
                'SR-' . str_pad((int)$r['id'], 5, '0', STR_PAD_LEFT),
                $r['sender'],
                $r['full_name'],
                $r['message'],
                $r['category'],
                $r['status'],
                $r['channel'] ?? 'sms',
                $r['assigned_to'] ?? '',
                $r['ticket_status'] ?? '',
                $r['source'] ?? '',
                $r['created_at'],
            ]);
        }
    } else {
        fputcsv($out, ['SMS Help Desk Summary Report']);
        fputcsv($out, ['Generated', date('Y-m-d H:i:s')]);
        if ($from || $to || $channel || $category) {
            fputcsv($out, ['Filters', "from={$from}", "to={$to}", "channel={$channel}", "category={$category}"]);
        }
        fputcsv($out, []);

        $total = fetchScalar($pdo, "SELECT COUNT(*) FROM inbound_messages {$where}", $params);
        fputcsv($out, ['Total Messages', $total]);
        fputcsv($out, []);

        fputcsv($out, ['STATUS TOTALS']);
        fputcsv($out, ['Status', 'Count']);
        foreach (fetchGrouped($pdo, "SELECT status, COUNT(*) total FROM inbound_messages {$where} GROUP BY status ORDER BY total DESC", $params) as $r) {
            fputcsv($out, [$r['status'], $r['total']]);
        }
        fputcsv($out, []);

        fputcsv($out, ['CHANNEL TOTALS']);
        fputcsv($out, ['Channel', 'Count']);
        foreach (fetchGrouped($pdo, "SELECT COALESCE(source, 'sms') channel, COUNT(*) total FROM inbound_messages {$where} GROUP BY COALESCE(source, 'sms') ORDER BY total DESC", $params) as $r) {
            fputcsv($out, [$r['channel'], $r['total']]);
        }
        fputcsv($out, []);

        fputcsv($out, ['CATEGORY TOTALS']);
        fputcsv($out, ['Category', 'Count']);
        foreach (fetchGrouped($pdo, "SELECT category, COUNT(*) total FROM inbound_messages {$where} GROUP BY category ORDER BY total DESC", $params) as $r) {
            fputcsv($out, [$r['category'], $r['total']]);
        }
        fputcsv($out, []);

        fputcsv($out, ['TECHNICIAN WORKLOAD']);
        fputcsv($out, ['Assigned To', 'Count']);
        foreach (fetchGrouped($pdo, "SELECT 'Unassigned' assigned_to, COUNT(*) total FROM inbound_messages {$where}", $params) as $r) {
            fputcsv($out, [$r['assigned_to'], $r['total']]);
        }
        fputcsv($out, []);

        $dailyWhere = $where;
        $dailyParams = $params;
        if ($dailyWhere === '') {
            $dailyWhere = "WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
        }

        fputcsv($out, ['DAILY TOTALS']);
        fputcsv($out, ['Day', 'Count']);
        foreach (fetchGrouped($pdo, "SELECT DATE(created_at) day, COUNT(*) total FROM inbound_messages {$dailyWhere} GROUP BY DATE(created_at) ORDER BY day ASC", $dailyParams) as $r) {
            fputcsv($out, [$r['day'], $r['total']]);
        }
    }

    fclose($out);
    exit();
}

$total = fetchScalar($pdo, "SELECT COUNT(*) FROM inbound_messages {$where}", $params);
$newCount = fetchScalar($pdo, "SELECT COUNT(*) FROM inbound_messages {$where}" . ($where ? ' AND' : ' WHERE') . " status = 'new'", $params);
$processedCount = fetchScalar($pdo, "SELECT COUNT(*) FROM inbound_messages {$where}" . ($where ? ' AND' : ' WHERE') . " status = 'processed'", $params);
$resolvedCount = fetchScalar($pdo, "SELECT COUNT(*) FROM inbound_messages {$where}" . ($where ? ' AND' : ' WHERE') . " status = 'resolved'", $params);

$resolutionRate = $total > 0 ? round(($resolvedCount / $total) * 100, 1) : 0;

$dailyWhere = $where;
$dailyParams = $params;
if ($dailyWhere === '') {
    $dailyWhere = "WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
}

$dailyRows = fetchGrouped($pdo, "SELECT DATE(created_at) AS day, COUNT(*) AS total FROM inbound_messages {$dailyWhere} GROUP BY DATE(created_at) ORDER BY day ASC", $dailyParams);
$daily = chartSeries($dailyRows, 'day', 'total');
$avgPerDay = count($daily['counts']) > 0 ? round(array_sum($daily['counts']) / count($daily['counts']), 1) : 0;

$categoryRows = fetchGrouped($pdo, "SELECT category, COUNT(*) AS total FROM inbound_messages {$where} GROUP BY category ORDER BY total DESC", $params);
$categories = chartSeries($categoryRows, 'category', 'total');

$statusRows = fetchGrouped($pdo, "SELECT status, COUNT(*) AS total FROM inbound_messages {$where} GROUP BY status ORDER BY total DESC", $params);
$statuses = chartSeries($statusRows, 'status', 'total');

$channelRows = fetchGrouped($pdo, "SELECT COALESCE(source, 'sms') AS channel, COUNT(*) AS total FROM inbound_messages {$where} GROUP BY COALESCE(source, 'sms') ORDER BY total DESC", $params);
$channels = chartSeries($channelRows, 'channel', 'total');

$techRows = fetchGrouped($pdo, "SELECT 'Unassigned' AS assigned_to, COUNT(*) AS total FROM inbound_messages {$where} LIMIT 8", $params);
$technicians = chartSeries($techRows, 'assigned_to', 'total');

$topSenders = fetchGrouped($pdo, "SELECT sender, full_name, COUNT(*) AS total FROM inbound_messages {$where} GROUP BY sender, full_name ORDER BY total DESC LIMIT 5", $params);

$recentSql = "SELECT id, sender, full_name, category, status,
                     COALESCE(source, 'sms') AS channel,
                     'Unassigned' AS assigned_to, created_at
              FROM inbound_messages {$where}
              ORDER BY created_at DESC LIMIT 8";
$recentMessages = fetchGrouped($pdo, $recentSql, $params);

$availableChannels = fetchGrouped($pdo, "SELECT DISTINCT COALESCE(source, 'sms') AS channel FROM inbound_messages ORDER BY channel ASC", []);
$availableCategories = fetchGrouped($pdo, "SELECT DISTINCT category FROM inbound_messages ORDER BY category ASC", []);

$queryParams = http_build_query(array_filter([
    'from' => $from,
    'to' => $to,
    'channel' => $channel,
    'category' => $category,
]));
$exportSuffix = $queryParams ? '&' . $queryParams : '';

$rangeLabel = 'All time';
if ($from && $to) {
    $rangeLabel = date('d M Y', strtotime($from)) . ' – ' . date('d M Y', strtotime($to));
} elseif ($from) {
    $rangeLabel = 'From ' . date('d M Y', strtotime($from));
} elseif ($to) {
    $rangeLabel = 'Until ' . date('d M Y', strtotime($to));
}

$today = date('Y-m-d');
$weekStart = date('Y-m-d', strtotime('-6 days'));
$monthStart = date('Y-m-01');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reports — SMS Help Desk</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <style>
        body { background:#f4f6f9; }
        .card { border-radius:14px; }
        .shadow-soft { box-shadow:0 8px 18px rgba(0,0,0,.08); }
        .stat-card { border-radius:14px; color:#fff; padding:18px; min-height:110px; }
        .c-total { background:#0d6efd; }
        .c-new { background:#ffc107; color:#111; }
        .c-processed { background:#0dcaf0; color:#111; }
        .c-resolved { background:#198754; }
        .c-rate { background:#6f42c1; }
        .c-avg { background:#fd7e14; }
        .chart-box { height:300px; position:relative; }
        .preset-btn.active { background:#0d6efd; color:#fff; border-color:#0d6efd; }
    </style>
</head>
<body>

<nav class="navbar navbar-dark bg-dark px-4">
    <span class="navbar-brand fw-bold">
        <i class="bi bi-bar-chart-fill me-2"></i>Reports & Analytics
    </span>
    <div class="d-flex gap-2 flex-wrap">
        <a class="btn btn-outline-light btn-sm" href="dashboard.php"><i class="bi bi-speedometer2 me-1"></i>Dashboard</a>
        <a class="btn btn-outline-light btn-sm" href="../simulate.php"><i class="bi bi-phone-fill me-1"></i>Simulator</a>
        <a class="btn btn-danger btn-sm" href="../logout.php"><i class="bi bi-box-arrow-right me-1"></i>Logout</a>
    </div>
</nav>

<div class="container mt-4 mb-5">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
        <div>
            <h4 class="mb-1">Operational Report</h4>
            <div class="text-muted">Period: <strong><?= htmlspecialchars($rangeLabel) ?></strong> · Generated <?= date('d M Y, H:i') ?></div>
        </div>
        <div class="text-muted small">Logged in as <strong><?= htmlspecialchars($user) ?></strong></div>
    </div>

    <div class="row g-3 text-center mb-4">
        <div class="col-md-4 col-lg-2">
            <div class="stat-card c-total shadow-soft">
                <i class="bi bi-collection"></i> Total<br><h3 class="mb-0 mt-1"><?= $total ?></h3>
            </div>
        </div>
        <div class="col-md-4 col-lg-2">
            <div class="stat-card c-new shadow-soft">
                <i class="bi bi-exclamation-circle"></i> New<br><h3 class="mb-0 mt-1"><?= $newCount ?></h3>
            </div>
        </div>
        <div class="col-md-4 col-lg-2">
            <div class="stat-card c-processed shadow-soft">
                <i class="bi bi-arrow-repeat"></i> Processed<br><h3 class="mb-0 mt-1"><?= $processedCount ?></h3>
            </div>
        </div>
        <div class="col-md-4 col-lg-2">
            <div class="stat-card c-resolved shadow-soft">
                <i class="bi bi-check-circle"></i> Resolved<br><h3 class="mb-0 mt-1"><?= $resolvedCount ?></h3>
            </div>
        </div>
        <div class="col-md-4 col-lg-2">
            <div class="stat-card c-rate shadow-soft">
                <i class="bi bi-percent"></i> Resolved<br><h3 class="mb-0 mt-1"><?= $resolutionRate ?>%</h3>
            </div>
        </div>
        <div class="col-md-4 col-lg-2">
            <div class="stat-card c-avg shadow-soft">
                <i class="bi bi-calendar-week"></i> Avg / day<br><h3 class="mb-0 mt-1"><?= $avgPerDay ?></h3>
            </div>
        </div>
    </div>

    <div class="card shadow-soft p-4 mb-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <h5 class="mb-0"><i class="bi bi-funnel me-2"></i>Filters & Export</h5>
            <div class="d-flex gap-2 flex-wrap">
                <a class="btn btn-sm btn-outline-success" href="reports.php?export=raw<?= $exportSuffix ?>"><i class="bi bi-download me-1"></i>Raw CSV</a>
                <a class="btn btn-sm btn-outline-primary" href="reports.php?export=summary<?= $exportSuffix ?>"><i class="bi bi-file-earmark-spreadsheet me-1"></i>Summary CSV</a>
            </div>
        </div>

        <div class="d-flex gap-2 flex-wrap mb-3">
            <a class="btn btn-sm btn-outline-secondary preset-btn<?= ($from === $weekStart && $to === $today && !$channel && !$category) ? ' active' : '' ?>" href="reports.php?from=<?= $weekStart ?>&to=<?= $today ?>">Last 7 days</a>
            <a class="btn btn-sm btn-outline-secondary preset-btn<?= ($from === $monthStart && $to === $today && !$channel && !$category) ? ' active' : '' ?>" href="reports.php?from=<?= $monthStart ?>&to=<?= $today ?>">This month</a>
            <a class="btn btn-sm btn-outline-secondary preset-btn<?= (!$from && !$to && !$channel && !$category) ? ' active' : '' ?>" href="reports.php">All time</a>
        </div>

        <form class="row g-2 align-items-end" method="GET">
            <div class="col-md-2">
                <label class="form-label small mb-1">From</label>
                <input type="date" name="from" class="form-control form-control-sm" value="<?= htmlspecialchars($from) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">To</label>
                <input type="date" name="to" class="form-control form-control-sm" value="<?= htmlspecialchars($to) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">Channel</label>
                <select name="channel" class="form-select form-select-sm">
                    <option value="">All channels</option>
                    <?php foreach ($availableChannels as $row): ?>
                        <option value="<?= htmlspecialchars($row['channel']) ?>" <?= $channel === $row['channel'] ? 'selected' : '' ?>><?= htmlspecialchars(ucfirst($row['channel'])) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">Category</label>
                <select name="category" class="form-select form-select-sm">
                    <option value="">All categories</option>
                    <?php foreach ($availableCategories as $row): ?>
                        <option value="<?= htmlspecialchars($row['category']) ?>" <?= $category === $row['category'] ? 'selected' : '' ?>><?= htmlspecialchars($row['category']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button class="btn btn-sm btn-primary" type="submit"><i class="bi bi-search me-1"></i>Apply</button>
                <a class="btn btn-sm btn-secondary" href="reports.php">Reset</a>
            </div>
        </form>
    </div>

    <?php if ($total === 0): ?>
        <div class="alert alert-info shadow-soft">
            <i class="bi bi-info-circle me-2"></i>No messages match these filters. Try resetting filters or send a test message from the simulator.
        </div>
    <?php endif; ?>

    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="card shadow-soft p-4 h-100">
                <h6 class="mb-3"><i class="bi bi-graph-up me-2"></i>Messages per day</h6>
                <div class="chart-box">
                    <canvas id="dailyChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card shadow-soft p-4 h-100">
                <h6 class="mb-3"><i class="bi bi-pie-chart me-2"></i>Status breakdown</h6>
                <div class="chart-box">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card shadow-soft p-4 h-100">
                <h6 class="mb-3"><i class="bi bi-chat-dots me-2"></i>By channel</h6>
                <div class="chart-box">
                    <canvas id="channelChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card shadow-soft p-4 h-100">
                <h6 class="mb-3"><i class="bi bi-tags me-2"></i>By category</h6>
                <div class="chart-box">
                    <canvas id="categoryChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card shadow-soft p-4 h-100">
                <h6 class="mb-3"><i class="bi bi-people me-2"></i>Technician workload</h6>
                <div class="chart-box">
                    <canvas id="techChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card shadow-soft p-4 h-100">
                <h6 class="mb-3"><i class="bi bi-person-lines-fill me-2"></i>Top senders</h6>
                <?php if (!$topSenders): ?>
                    <p class="text-muted mb-0">No sender data for this period.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped mb-0">
                            <thead><tr><th>Sender</th><th>Name</th><th class="text-end">Messages</th></tr></thead>
                            <tbody>
                            <?php foreach ($topSenders as $row): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['sender']) ?></td>
                                    <td><?= htmlspecialchars($row['full_name'] ?: 'Unknown') ?></td>
                                    <td class="text-end"><span class="badge bg-primary"><?= (int)$row['total'] ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card shadow-soft p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="mb-0"><i class="bi bi-clock-history me-2"></i>Recent activity</h6>
                    <a href="dashboard.php" class="btn btn-sm btn-outline-primary">View all</a>
                </div>
                <?php if (!$recentMessages): ?>
                    <p class="text-muted mb-0">No recent messages.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Ref</th>
                                    <th>Sender</th>
                                    <th>Category</th>
                                    <th>Channel</th>
                                    <th>Status</th>
                                    <th>When</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($recentMessages as $row): ?>
                                <tr>
                                    <td><strong>SR-<?= str_pad((int)$row['id'], 5, '0', STR_PAD_LEFT) ?></strong></td>
                                    <td><?= htmlspecialchars($row['sender']) ?></td>
                                    <td><?= htmlspecialchars($row['category']) ?></td>
                                    <td><?= htmlspecialchars($row['channel'] ?? 'sms') ?></td>
                                    <td><span class="badge <?= statusBadgeClass($row['status']) ?>"><?= htmlspecialchars($row['status']) ?></span></td>
                                    <td><?= date('d M, H:i', strtotime($row['created_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
const palette = ['#0d6efd', '#ffc107', '#0dcaf0', '#198754', '#6f42c1', '#fd7e14', '#dc3545', '#20c997'];

const chartDefaults = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { position: 'bottom' } }
};

function emptyChartMessage(canvasId, message) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return;
    const box = canvas.parentElement;
    box.innerHTML = '<div class="d-flex align-items-center justify-content-center h-100 text-muted">' + message + '</div>';
}

const dailyLabels = <?= json_encode($daily['labels']) ?>;
const dailyCounts = <?= json_encode($daily['counts']) ?>;
const catLabels = <?= json_encode($categories['labels']) ?>;
const catCounts = <?= json_encode($categories['counts']) ?>;
const statusLabels = <?= json_encode($statuses['labels']) ?>;
const statusCounts = <?= json_encode($statuses['counts']) ?>;
const channelLabels = <?= json_encode($channels['labels']) ?>;
const channelCounts = <?= json_encode($channels['counts']) ?>;
const techLabels = <?= json_encode($technicians['labels']) ?>;
const techCounts = <?= json_encode($technicians['counts']) ?>;

if (dailyLabels.length) {
    new Chart(document.getElementById('dailyChart'), {
        type: 'line',
        data: {
            labels: dailyLabels,
            datasets: [{
                label: 'Messages',
                data: dailyCounts,
                borderColor: '#0d6efd',
                backgroundColor: 'rgba(13,110,253,0.12)',
                fill: true,
                tension: 0.35,
                borderWidth: 2
            }]
        },
        options: { ...chartDefaults, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
    });
} else {
    emptyChartMessage('dailyChart', 'No daily trend data for this period.');
}

if (catLabels.length) {
    new Chart(document.getElementById('categoryChart'), {
        type: 'bar',
        data: {
            labels: catLabels,
            datasets: [{ label: 'Messages', data: catCounts, backgroundColor: palette, borderWidth: 0 }]
        },
        options: { ...chartDefaults, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
    });
} else {
    emptyChartMessage('categoryChart', 'No category data.');
}

if (statusLabels.length) {
    new Chart(document.getElementById('statusChart'), {
        type: 'doughnut',
        data: {
            labels: statusLabels,
            datasets: [{ data: statusCounts, backgroundColor: ['#ffc107', '#0dcaf0', '#198754', '#6c757d'] }]
        },
        options: chartDefaults
    });
} else {
    emptyChartMessage('statusChart', 'No status data.');
}

if (channelLabels.length) {
    new Chart(document.getElementById('channelChart'), {
        type: 'polarArea',
        data: {
            labels: channelLabels,
            datasets: [{ data: channelCounts, backgroundColor: palette.map(c => c + '99') }]
        },
        options: chartDefaults
    });
} else {
    emptyChartMessage('channelChart', 'No channel data.');
}

if (techLabels.length) {
    new Chart(document.getElementById('techChart'), {
        type: 'bar',
        data: {
            labels: techLabels,
            datasets: [{ label: 'Assigned tickets', data: techCounts, backgroundColor: '#6f42c1', borderWidth: 0 }]
        },
        options: {
            indexAxis: 'y',
            ...chartDefaults,
            plugins: { legend: { display: false } },
            scales: { x: { beginAtZero: true, ticks: { precision: 0 } } }
        }
    });
} else {
    emptyChartMessage('techChart', 'No assignment data.');
}
</script>
</body>
</html>
