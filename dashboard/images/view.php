
<?php
// TODO: Update with your database credentials
$db_host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "sms_shortcode";

$conn = new PDO("mysql:host={$db_host};dbname={$db_name}", $db_user, $db_pass);
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// ✅ RESOLVE ACTION
if (isset($_GET['resolve']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];

    $stmt = $conn->prepare("UPDATE inbound_messages SET status='RESOLVED' WHERE id=?");
    $stmt->execute([$id]);

    header("Location: view.php");
    exit();
}

// ✅ CATEGORY FILTER
$categoryFilter = $_GET['category'] ?? 'ALL';

if ($categoryFilter === 'ICT') {
    $query = "SELECT * FROM inbound_messages WHERE category='ICT' ORDER BY received_at DESC";
} elseif ($categoryFilter === 'ICT-CONSUMABLE') {
    $query = "SELECT * FROM inbound_messages WHERE category='ICT-CONSUMABLE' ORDER BY received_at DESC";
} elseif ($categoryFilter === 'NETWORK') {
    $query = "SELECT * FROM inbound_messages WHERE category='NETWORK' ORDER BY received_at DESC";
} elseif ($categoryFilter === 'SYSTEMS') {
    $query = "SELECT * FROM inbound_messages WHERE category='SYSTEMS' ORDER BY received_at DESC";
} else {
    $query = "SELECT * FROM inbound_messages ORDER BY received_at DESC";
}

$rows = $conn->query($query);

// ✅ STATS
$total = $conn->query("SELECT COUNT(*) FROM inbound_messages")->fetchColumn();
$open = $conn->query("SELECT COUNT(*) FROM inbound_messages WHERE status='OPEN'")->fetchColumn();
$resolved = $conn->query("SELECT COUNT(*) FROM inbound_messages WHERE status='RESOLVED'")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Message Dashboard</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body { background: #f4f6f9; }
.card { border-radius: 12px; }
.table tbody tr:hover { background: #eef5ff; }
</style>
</head>

<body>

<!-- ✅ NAVBAR -->
<nav class="navbar navbar-expand-lg p-3 mb-4" style="background:#1f2d3d;">
  <div class="container">
    
    <span class="navbar-brand text-white fw-bold">📩 SMS Management System</span>

    <div>
      <a href="/sms/dashboard/simulate.php" class="text-white me-3">📱 Simulator</a>
      <a href="/sms/dashboard/dashboard.php" class="text-white me-3">🏠 Home</a>
      <a href="/sms/dashboard/view.php" class="text-white me-3">📊 Dashboard</a>
      <a href="/sms/dashboard/reports.php" class="text-white">📈 Reports</a>
    </div>

  </div>
</nav>
``

<div class="container mt-4">

<h1 class="mb-4">📊 Message Dashboard</h1>

<!-- ✅ STATS -->
<div class="row text-center mb-4">

  <div class="col-md-4">
    <div class="card shadow-sm p-3">
      <h6>Total Messages</h6>
      <h2><?= $total ?></h2>
    </div>
  </div>

  <div class="col-md-4">
    <div class="card shadow-sm bg-warning p-3">
      <h6>Open Tickets</h6>
      <h2><?= $open ?></h2>
    </div>
  </div>

  <div class="col-md-4">
    <div class="card shadow-sm bg-success text-white p-3">
      <h6>Resolved</h6>
      <h2><?= $resolved ?></h2>
    </div>
  </div>

</div>

<!-- ✅ FILTER BUTTONS -->
<div class="mb-3 d-flex gap-2">

<a href="view.php" class="btn btn-dark">All</a>

<a href="view.php?category=ICT" class="btn btn-outline-primary">ICT</a>

<a href="view.php?category=ICT-CONSUMABLE" class="btn btn-outline-warning">Consumables</a>

<a href="view.php?category=NETWORK" class="btn btn-outline-info">Network</a>

<a href="view.php?category=SYSTEMS" class="btn btn-outline-success">Systems</a>

</div>

<!-- ✅ TABLE -->
<div class="card shadow-sm">
<div class="card-body">

<table class="table table-hover">
<thead class="table-dark">
<tr>
  <th>ID</th>
  <th>Sender</th>
  <th>Message</th>
  <th>Category</th>
  <th>Status</th>
  <th>Action</th>
</tr>
</thead>

<tbody>

<?php while ($row = $rows->fetch(PDO::FETCH_ASSOC)): ?>

<tr>

<td><?= $row['id'] ?></td>

<td><?= htmlspecialchars($row['sender_msisdn'] ?? '') ?></td>

<td><?= htmlspecialchars($row['message_text'] ?? '') ?></td>

<td>
<?php
switch ($row['category']) {
    case 'ICT':
        echo '<span class="badge bg-primary">ICT</span>';
        break;
    case 'ICT-CONSUMABLE':
        echo '<span class="badge bg-warning text-dark">Consumable</span>';
        break;
    case 'NETWORK':
        echo '<span class="badge bg-info text-dark">Network</span>';
        break;
    case 'SYSTEMS':
        echo '<span class="badge bg-success">Systems</span>';
        break;
    default:
        echo $row['category'];
}
?>
</td>

<td>
<?php if ($row['status'] === 'RESOLVED'): ?>
    <span class="badge bg-success">Resolved</span>
<?php else: ?>
    <span class="badge bg-danger">Open</span>
<?php endif; ?>
</td>

<td>
<?php if ($row['status'] === 'OPEN'): ?>
    <a href="view.php?resolve=1&id=<?= $row['id'] ?>" class="btn btn-sm btn-success">
        Resolve
    </a>
<?php else: ?>
    ✔ Done
<?php endif; ?>
</td>

</tr>

<?php endwhile; ?>

</tbody>
</table>

</div>
</div>

</div>

</body>
</html>
