
<?php
session_start();

if (!isset($_SESSION['user'])) {
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

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['update_status'])) {
    $id = (int)($_POST['id'] ?? 0);
    $status = strtolower(trim($_POST['status'] ?? ''));

    $allowed = ['new', 'processed', 'resolved'];

    if ($id > 0 && in_array($status, $allowed, true)) {
        $stmt = $pdo->prepare("UPDATE inbound_messages SET status = ? WHERE id = ?");
        $stmt->execute([$status, $id]);
        $_SESSION['flash'] = "Message #{$id} updated to '{$status}'.";
    } else {
        $_SESSION['flash'] = "Invalid status update request.";
    }

    header("Location: dashboard.php");
    exit();
}

// ✅ Stats
$total = (int)$pdo->query("SELECT COUNT(*) FROM inbound_messages")->fetchColumn();
$new = (int)$pdo->query("SELECT COUNT(*) FROM inbound_messages WHERE status='new'")->fetchColumn();
$processed = (int)$pdo->query("SELECT COUNT(*) FROM inbound_messages WHERE status='processed'")->fetchColumn();
$resolved = (int)$pdo->query("SELECT COUNT(*) FROM inbound_messages WHERE status='resolved'")->fetchColumn();

// ✅ FILTER LOGIC (UNCHANGED)
$ticket     = trim($_GET['ticket'] ?? '');
$sender_f   = trim($_GET['sender'] ?? '');
$category_f = trim($_GET['category'] ?? '');
$from_date  = trim($_GET['from_date'] ?? '');
$to_date    = trim($_GET['to_date'] ?? '');

$hasFilters = ($ticket !== '' || $sender_f !== '' || $category_f !== '' || $from_date !== '' || $to_date !== '');

$sql = "SELECT id, sender, full_name, message, category, status, created_at
        FROM inbound_messages
        WHERE 1=1";

$params = [];

if ($ticket !== '') {
    $digits = preg_replace('/\D+/', '', $ticket);
    $ticketId = (int)$digits;

    if ($ticketId > 0) {
        $sql .= " AND id = ?";
        $params[] = $ticketId;
    } else {
        $sql .= " AND 1=0";
    }
}

if ($sender_f !== '') {
    $sql .= " AND sender LIKE ?";
    $params[] = "%" . $sender_f . "%";
}

if ($category_f !== '') {
    $sql .= " AND category = ?";
    $params[] = $category_f;
}

if ($from_date !== '') {
    $sql .= " AND DATE(created_at) >= ?";
    $params[] = $from_date;
}
if ($to_date !== '') {
    $sql .= " AND DATE(created_at) <= ?";
    $params[] = $to_date;
}

$sql .= " ORDER BY created_at DESC";

if (!$hasFilters) {
    $sql .= " LIMIT 20";
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);

function statusBadge($status) {
    $s = strtolower($status);
    if ($s === 'new') return 'bg-warning text-dark';
    if ($s === 'processed') return 'bg-info text-dark';
    if ($s === 'resolved') return 'bg-success';
    return 'bg-secondary';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>SMS Dashboard</title>

<meta name="viewport" content="width=device-width, initial-scale=1">

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

<style>
body { background:#f4f6f9; }
.stat-card { border-radius:14px;color:#fff;padding:18px; }
.c-total { background:#0d6efd; }
.c-new { background:#ffc107;color:#111; }
.c-processed { background:#0dcaf0;color:#111; }
.c-resolved { background:#198754; }
.shadow-soft { box-shadow:0 8px 18px rgba(0,0,0,.08); }
.table td { vertical-align:middle; }

/* ✅ Row color */
.table-warning { background:#fff8d6 !important; }
.table-info { background:#e7f8ff !important; }
.table-success { background:#e9f9f0 !important; }
</style>
</head>

<body>

<!-- ✅ NAVBAR RESTORED -->
<nav class="navbar navbar-dark bg-dark px-4">
    <span class="navbar-brand fw-bold">
        <i class="bi bi-inboxes-fill me-2"></i>SMS Management System
    </span>

    <div class="d-flex gap-2">
        <a class="btn btn-outline-light btn-sm" href="../simulate.php">
            <i class="bi bi-phone-fill me-1"></i>Simulator
        </a>

        <a class="btn btn-outline-light btn-sm" href="../index.php">
            <i class="bi bi-house-door-fill me-1"></i>Home
        </a>

        <a class="btn btn-outline-light btn-sm" href="reports.php">
            <i class="bi bi-bar-chart-fill me-1"></i>Reports
        </a>

        <a class="btn btn-danger btn-sm" href="../logout.php">
            <i class="bi bi-box-arrow-right me-1"></i>Logout
        </a>
    </div>
</nav>

<div class="container mt-4">

<div class="mb-2">
Logged in as: <strong><?= htmlspecialchars($_SESSION['user']) ?></strong>
</div>

<?php if (!empty($flash)): ?>
<div class="alert alert-success shadow-soft"><?= htmlspecialchars($flash) ?></div>
<?php endif; ?>

<!-- ✅ STATS -->
<div class="row g-3 text-center mt-2">
<div class="col-md-3"><div class="stat-card c-total shadow-soft"><i class="bi bi-collection"></i> Total<br><h2><?= $total ?></h2></div></div>
<div class="col-md-3"><div class="stat-card c-new shadow-soft"><i class="bi bi-exclamation-circle"></i> New<br><h2><?= $new ?></h2></div></div>
<div class="col-md-3"><div class="stat-card c-processed shadow-soft"><i class="bi bi-arrow-repeat"></i> Processed<br><h2><?= $processed ?></h2></div></div>
<div class="col-md-3"><div class="stat-card c-resolved shadow-soft"><i class="bi bi-check-circle"></i> Resolved<br><h2><?= $resolved ?></h2></div></div>
</div>

<!-- ✅ MAIN TABLE -->
<div class="card shadow-soft mt-4">
<div class="card-body">

<div class="d-flex justify-content-between align-items-center">
<h5><i class="bi bi-card-list me-2"></i>Recent Messages</h5>

<a href="../simulate.php" class="btn btn-primary btn-sm">
<i class="bi bi-send-fill me-1"></i>Send Test SMS
</a>
</div>

<hr>

<!-- ✅ FILTER UI RESTORED EXACTLY -->
<form method="GET" class="row g-2 mb-3">
<div class="col-md-3">
<input type="text" name="ticket" class="form-control" placeholder="Ticket (SR-00001)" value="<?= htmlspecialchars($ticket) ?>">
</div>

<div class="col-md-2">
<input type="text" name="sender" class="form-control" placeholder="Sender" value="<?= htmlspecialchars($sender_f) ?>">
</div>

<div class="col-md-2">
<select name="category" class="form-select">
<option value="">All Categories</option>
<?php
$cats=["ICT","ICT-CONSUMABLE","NETWORK","SYSTEMS","General"];
foreach($cats as $c){
$sel=($category_f===$c)?"selected":"";
echo "<option value='$c' $sel>$c</option>";
}
?>
</select>
</div>

<div class="col-md-2">
<input type="date" name="from_date" class="form-control" value="<?= htmlspecialchars($from_date) ?>">
</div>

<div class="col-md-2">
<input type="date" name="to_date" class="form-control" value="<?= htmlspecialchars($to_date) ?>">
</div>

<div class="col-md-1 d-grid">
<button class="btn btn-dark"><i class="bi bi-search"></i></button>
</div>

<div class="col-12">
<a href="dashboard.php" class="small text-muted">Reset filters</a>
</div>
</form>

<table class="table table-bordered table-striped">
<thead class="table-dark">
<tr>
<th>Ref #</th>
<th>Name & Surname</th>
<th>Sender</th>
<th>Message</th>
<th>Category</th>
<th>Status</th>
<th>Time</th>
<th>Actions</th>
</tr>
</thead>

<tbody>
<?php foreach($messages as $row): ?>

<?php
$status=strtolower($row['status']);
$rowClass='';
if($status=='new')$rowClass='table-warning';
if($status=='processed')$rowClass='table-info';
if($status=='resolved')$rowClass='table-success';
?>

<tr class="<?= $rowClass ?>">

<td>
<strong id="t<?= $row['id'] ?>">SR-<?= str_pad($row['id'],5,"0",STR_PAD_LEFT) ?></strong>
<button class="btn btn-sm btn-light" onclick="copyTicket('t<?= $row['id'] ?>')">
<i class="bi bi-clipboard"></i>
</button>
</td>

<td><?= htmlspecialchars($row['full_name'] ?: 'Unknown') ?></td>
<td><?= htmlspecialchars($row['sender']) ?></td>
<td><?= htmlspecialchars($row['message']) ?></td>
<td><?= htmlspecialchars($row['category']) ?></td>

<td><span class="badge <?= statusBadge($status) ?>"><?= $status ?></span></td>

<td><?= date("d M Y, H:i", strtotime($row['created_at'])) ?></td>

<td>

<form method="POST" style="display:inline;">
<input type="hidden" name="id" value="<?= $row['id'] ?>">
<input type="hidden" name="status" value="processed">
<button class="btn btn-info btn-sm" name="update_status">Processed</button>
</form>

<form method="POST" style="display:inline;">
<input type="hidden" name="id" value="<?= $row['id'] ?>">
<input type="hidden" name="status" value="resolved">
<button onclick="return confirm('Mark this ticket as resolved?')" class="btn btn-success btn-sm" name="update_status">Resolve</button>
</form>

</td>

</tr>

<?php endforeach; ?>
</tbody>
</table>

</div>
</div>
</div>

<script>
function copyTicket(id){
let t=document.getElementById(id).innerText;
navigator.clipboard.writeText(t);
alert("Copied: "+t);
}
</script>

</body>
</html>

