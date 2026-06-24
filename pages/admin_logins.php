<?php
// admin-logins.php - View all login activity

require_once 'config.php';
session_start();

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Get today's stats
$today = date('Y-m-d');
$stats = $conn->query("
    SELECT 
        COUNT(*) as total_logins,
        SUM(CASE WHEN login_type = 'success' THEN 1 ELSE 0 END) as successful_logins,
        SUM(CASE WHEN login_type = 'failed' THEN 1 ELSE 0 END) as failed_logins
    FROM login_history 
    WHERE DATE(created_at) = '$today'
")->fetch_assoc();

// Get recent logins
$recent_logins = $conn->query("
    SELECT 
        lh.*,
        u.full_name,
        u.email,
        u.role
    FROM login_history lh
    LEFT JOIN users u ON lh.user_id = u.id
    ORDER BY lh.created_at DESC
    LIMIT 50
");

// Get active sessions
$active_sessions = $conn->query("
    SELECT 
        us.*,
        u.full_name,
        u.email,
        u.role,
        TIMESTAMPDIFF(MINUTE, us.last_activity, NOW()) as idle_minutes
    FROM user_sessions us
    JOIN users u ON us.user_id = u.id
    WHERE us.is_active = TRUE
    ORDER BY us.last_activity DESC
");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login Monitoring Dashboard</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f4f7fa; padding: 20px; }
        .container { max-width: 1400px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #1e7e34; margin-bottom: 20px; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: #f8fafc; padding: 20px; border-radius: 10px; border-left: 4px solid #1e7e34; }
        .stat-card .number { font-size: 2rem; font-weight: bold; }
        .stat-card .label { color: #64748b; font-size: 0.9rem; }
        .stat-card .sub { font-size: 0.8rem; color: #94a3b8; margin-top: 5px; }
        .section { margin-top: 30px; }
        .section h2 { margin-bottom: 15px; font-size: 1.3rem; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #e9edf2; }
        th { background: #f1f5f9; font-weight: 600; }
        tr:hover { background: #f8fafc; }
        .badge { padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
        .badge-success { background: #dcfce7; color: #166534; }
        .badge-danger { background: #fee2e2; color: #991b1b; }
        .badge-warning { background: #fef9c3; color: #854d0e; }
        .badge-info { background: #dbeafe; color: #1e40af; }
        .role-badge { padding: 3px 8px; border-radius: 4px; font-size: 0.7rem; font-weight: 600; }
        .role-admin { background: #e74c3c; color: white; }
        .role-farmer { background: #3498db; color: white; }
        .role-buyer { background: #2ecc71; color: white; }
        .role-inspector { background: #f39c12; color: white; }
        .role-transporter { background: #9b59b6; color: white; }
        .online { color: #2ecc71; font-weight: bold; }
        .offline { color: #e74c3c; font-weight: bold; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .btn { background: #1e7e34; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn:hover { background: #16632a; }
        .tab { display: flex; gap: 10px; margin-bottom: 20px; }
        .tab a { padding: 10px 20px; background: #f1f5f9; border-radius: 5px; text-decoration: none; color: #1e293b; }
        .tab a.active { background: #1e7e34; color: white; }
        .tab a:hover:not(.active) { background: #e9edf2; }
        .row { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; }
        @media (max-width: 768px) { .row { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔐 Login Monitoring Dashboard</h1>
            <a href="logout.php" class="btn">Logout</a>
        </div>
        
        <div class="tab">
            <a href="admin-logins.php" class="active">📊 Login Activity</a>
            <a href="admin-sessions.php">🟢 Active Sessions</a>
            <a href="admin-users.php">👤 User Management</a>
        </div>
        
        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="number"><?php echo $stats['total_logins'] ?? 0; ?></div>
                <div class="label">Total Logins Today</div>
            </div>
            <div class="stat-card" style="border-left-color: #2ecc71;">
                <div class="number" style="color: #2ecc71;"><?php echo $stats['successful_logins'] ?? 0; ?></div>
                <div class="label">Successful Logins</div>
            </div>
            <div class="stat-card" style="border-left-color: #e74c3c;">
                <div class="number" style="color: #e74c3c;"><?php echo $stats['failed_logins'] ?? 0; ?></div>
                <div class="label">Failed Logins</div>
                <div class="sub">Security monitoring</div>
            </div>
            <div class="stat-card" style="border-left-color: #f39c12;">
                <div class="number"><?php echo $active_sessions->num_rows; ?></div>
                <div class="label">Active Sessions</div>
                <div class="sub">Currently logged in</div>
            </div>
        </div>
        
        <!-- Recent Logins -->
        <div class="section">
            <h2>📋 Recent Login Activity (Last 50)</h2>
            <table>
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>IP Address</th>
                        <th>Location</th>
                        <th>Device</th>
                        <th>Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $recent_logins->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['full_name'] ?? 'Unknown User'); ?></td>
                        <td><?php echo htmlspecialchars($row['email'] ?? 'N/A'); ?></td>
                        <td>
                            <?php if ($row['role']): ?>
                            <span class="role-badge role-<?php echo $row['role']; ?>">
                                <?php echo ucfirst($row['role']); ?>
                            </span>
                            <?php else: ?>
                            <span class="role-badge">Unknown</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($row['login_type'] === 'success'): ?>
                            <span class="badge badge-success">✅ Success</span>
                            <?php else: ?>
                            <span class="badge badge-danger">❌ Failed</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($row['ip_address']); ?></td>
                        <td>
                            <?php 
                            $location = [];
                            if ($row['location_city']) $location[] = $row['location_city'];
                            if ($row['location_country']) $location[] = $row['location_country'];
                            echo htmlspecialchars(implode(', ', $location) ?: 'Unknown');
                            ?>
                        </td>
                        <td>
                            <?php 
                            // Parse user agent for device info
                            $ua = $row['user_agent'] ?? '';
                            if (strpos($ua, 'Mobile') !== false) echo '📱 Mobile';
                            elseif (strpos($ua, 'Tablet') !== false) echo '📱 Tablet';
                            else echo '💻 Desktop';
                            ?>
                        </td>
                        <td><?php echo date('Y-m-d H:i:s', strtotime($row['created_at'])); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Active Sessions -->
        <div class="section">
            <h2>🟢 Active Sessions</h2>
            <table>
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Role</th>
                        <th>IP Address</th>
                        <th>Device</th>
                        <th>Browser</th>
                        <th>Login Time</th>
                        <th>Idle</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $active_sessions->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                        <td><span class="role-badge role-<?php echo $row['role']; ?>"><?php echo ucfirst($row['role']); ?></span></td>
                        <td><?php echo htmlspecialchars($row['ip_address']); ?></td>
                        <td><?php echo htmlspecialchars($row['device_type'] ?? 'Unknown'); ?></td>
                        <td><?php echo htmlspecialchars($row['browser'] ?? 'Unknown'); ?></td>
                        <td><?php echo date('H:i:s', strtotime($row['login_time'])); ?></td>
                        <td><?php echo $row['idle_minutes']; ?> min</td>
                        <td><span class="online">🟢 Online</span></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>