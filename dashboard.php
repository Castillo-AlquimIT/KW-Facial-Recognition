<?php
require_once 'db.php'; // provides $con

$name = isset($_GET['name']) ? trim($_GET['name']) : '';

if ($name === '') {
    header('Location: index.html');
    exit;
}

// --- Stats: total registered users ---
$totalUsers = 0;
$res = $con->query("SELECT COUNT(*) AS total FROM users");
if ($res) {
    $row = $res->fetch_assoc();
    $totalUsers = (int) $row['total'];
}

// --- Stats: today's attendance count (all users) ---
$todayCount = 0;
$res = $con->query("SELECT COUNT(*) AS total FROM attendance WHERE DATE(timestamp) = CURDATE()");
if ($res) {
    $row = $res->fetch_assoc();
    $todayCount = (int) $row['total'];
}

// --- Stats: this user's total check-ins ---
$myCount = 0;
$stmt = $con->prepare("SELECT COUNT(*) AS total FROM attendance WHERE name = ?");
$stmt->bind_param("s", $name);
$stmt->execute();
$result = $stmt->get_result();
if ($result) {
    $row = $result->fetch_assoc();
    $myCount = (int) $row['total'];
}
$stmt->close();

// --- This user's recent attendance history ---
$history = [];
$stmt = $con->prepare("SELECT timestamp FROM attendance WHERE name = ? ORDER BY timestamp DESC LIMIT 10");
$stmt->bind_param("s", $name);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $history[] = $row['timestamp'];
}
$stmt->close();

$con->close();

$safeName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Dashboard — KIFFY WORPSHIPPER</title>
<link rel="stylesheet" href="dashboard.css">
</head>
<body>
<div class="dash-page">

  <div class="dashboard-header">
    <div class="dash-header-left">
      <div class="dash-crest">KW</div>
      <div>
        <div class="dash-header-wordmark">KIFFY WORPSHIPPER</div>
        <div class="dash-header-sub">Face Registration Module</div>
      </div>
    </div>
    <button class="dash-logout-btn" onclick="window.location.href='index.html'">Log Out</button>
  </div>

  <div class="dash-body">

    <div class="dash-welcome">
      <h1>Welcome, <?php echo $safeName; ?></h1>
      <p>Here's your recognition and attendance summary.</p>
    </div>

    <div class="dash-stats-row">
      <div class="dash-stat-card">
        <div class="dash-stat-label">Registered Users</div>
        <div class="dash-stat-value"><?php echo $totalUsers; ?></div>
      </div>
      <div class="dash-stat-card">
        <div class="dash-stat-label">Check-ins Today (All)</div>
        <div class="dash-stat-value"><?php echo $todayCount; ?></div>
      </div>
      <div class="dash-stat-card">
        <div class="dash-stat-label">Your Total Check-ins</div>
        <div class="dash-stat-value"><?php echo $myCount; ?></div>
      </div>
    </div>

    <div class="dash-section">
      <h2>Your Recent Attendance</h2>
      <?php if (count($history) === 0): ?>
        <p class="dash-empty">No attendance records yet.</p>
      <?php else: ?>
        <table class="dash-table">
          <thead>
            <tr>
              <th>#</th>
              <th>Date &amp; Time</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($history as $i => $ts): ?>
              <tr>
                <td><?php echo $i + 1; ?></td>
                <td><?php echo htmlspecialchars($ts, ENT_QUOTES, 'UTF-8'); ?></td>
                <td><span class="dash-badge">Verified</span></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>

  </div>
</div>
</body>
</html>
