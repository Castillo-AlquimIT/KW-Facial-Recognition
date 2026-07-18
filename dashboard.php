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

// --- Pagination setup ---
$perPage = 10;
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$offset = ($page - 1) * $perPage;

// --- This user's total attendance record count (for pagination) ---
$totalRecords = $myCount; // already counted above

$totalPages = max(1, (int) ceil($totalRecords / $perPage));
if ($page > $totalPages) {
    $page = $totalPages;
    $offset = ($page - 1) * $perPage;
}

// --- This user's attendance history (current page) ---
$history = [];
$stmt = $con->prepare("SELECT timestamp FROM attendance WHERE name = ? ORDER BY timestamp DESC LIMIT ? OFFSET ?");
$stmt->bind_param("sii", $name, $perPage, $offset);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $history[] = $row['timestamp'];
}
$stmt->close();

// --- Latest single check-in (always page 1, most recent overall) ---
$latest = null;
$stmt = $con->prepare("SELECT timestamp FROM attendance WHERE name = ? ORDER BY timestamp DESC LIMIT 1");
$stmt->bind_param("s", $name);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $latest = $row['timestamp'];
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

    <?php if ($latest !== null): ?>
      <div class="dash-latest-banner">
        <span class="dash-latest-label">Latest Check-in</span>
        <span class="dash-latest-value"><?php echo htmlspecialchars($latest, ENT_QUOTES, 'UTF-8'); ?></span>
      </div>
    <?php endif; ?>

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
              <tr class="<?php echo ($ts === $latest) ? 'dash-row-latest' : ''; ?>">
                <td><?php echo $offset + $i + 1; ?></td>
                <td><?php echo htmlspecialchars($ts, ENT_QUOTES, 'UTF-8'); ?></td>
                <td>
                  <span class="dash-badge">Verified</span>
                  <?php if ($ts === $latest): ?>
                    <span class="dash-badge dash-badge-latest">Latest</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>

        <?php if ($totalPages > 1): ?>
          <div class="dash-pagination">
            <?php if ($page > 1): ?>
              <a class="dash-page-link" href="?name=<?php echo urlencode($name); ?>&page=<?php echo $page - 1; ?>">&laquo; Prev</a>
            <?php endif; ?>

            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
              <a class="dash-page-link<?php echo ($p === $page) ? ' active' : ''; ?>"
                 href="?name=<?php echo urlencode($name); ?>&page=<?php echo $p; ?>"><?php echo $p; ?></a>
            <?php endfor; ?>

            <?php if ($page < $totalPages): ?>
              <a class="dash-page-link" href="?name=<?php echo urlencode($name); ?>&page=<?php echo $page + 1; ?>">Next &raquo;</a>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      <?php endif; ?>
    </div>

  </div>
</div>
</body>
</html>