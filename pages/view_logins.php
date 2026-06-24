<?php
// login.php - Complete login handler with tracking

require_once 'config.php';
session_start();

// Get user details from login form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $ip = $_SERVER['REMOTE_ADDR'];
    $userAgent = $_SERVER['HTTP_USER_AGENT'];
    $timestamp = date('Y-m-d H:i:s');
    
    // Get geolocation from IP (using free ip-api.com)
    $location = getLocationFromIP($ip);
    
    // Check if user exists
    $stmt = $conn->prepare("SELECT id, full_name, role, password_hash FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        // Verify password
        if (password_verify($password, $row['password_hash'])) {
            // ✅ LOGIN SUCCESSFUL - RECORD EVERYTHING
            
            // Record in login_history
            $stmt = $conn->prepare("INSERT INTO login_history 
                (user_id, login_type, ip_address, user_agent, location_city, location_country) 
                VALUES (?, 'success', ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $row['id'], $ip, $userAgent, $location['city'], $location['country']);
            $stmt->execute();
            
            // Create session record
            $token = bin2hex(random_bytes(32));
            $deviceInfo = getDeviceInfo($userAgent);
            
            $stmt = $conn->prepare("INSERT INTO user_sessions 
                (user_id, session_token, ip_address, user_agent, device_type, browser, os) 
                VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssss", $row['id'], $token, $ip, $userAgent, 
                $deviceInfo['device'], $deviceInfo['browser'], $deviceInfo['os']);
            $stmt->execute();
            
            // Log activity
            $stmt = $conn->prepare("INSERT INTO activity_logs 
                (user_id, activity_type, description, ip_address, user_agent) 
                VALUES (?, 'login_success', ?, ?, ?)");
            $desc = "User {$row['full_name']} logged in from {$location['city']}, {$location['country']}";
            $stmt->bind_param("ssss", $row['id'], $desc, $ip, $userAgent);
            $stmt->execute();
            
            // Set session
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['full_name'] = $row['full_name'];
            $_SESSION['role'] = $row['role'];
            $_SESSION['login_time'] = $timestamp;
            $_SESSION['ip'] = $ip;
            
            // Return success with user details
            $response = [
                'success' => true,
                'message' => 'Login successful',
                'user' => [
                    'id' => $row['id'],
                    'name' => $row['full_name'],
                    'role' => $row['role'],
                    'email' => $email
                ],
                'login_details' => [
                    'ip' => $ip,
                    'location' => $location,
                    'device' => $deviceInfo,
                    'time' => $timestamp
                ]
            ];
            
            echo json_encode($response);
            
        } else {
            // ❌ WRONG PASSWORD - Record failed attempt
            $stmt = $conn->prepare("INSERT INTO login_history 
                (user_id, login_type, ip_address, user_agent, location_city, location_country, failure_reason) 
                VALUES (?, 'failed', ?, ?, ?, ?, ?)");
            $reason = "Incorrect password for user {$row['full_name']}";
            $stmt->bind_param("ssssss", $row['id'], $ip, $userAgent, 
                $location['city'], $location['country'], $reason);
            $stmt->execute();
            
            // Log failed activity
            $stmt = $conn->prepare("INSERT INTO activity_logs 
                (user_id, activity_type, description, ip_address, user_agent) 
                VALUES (?, 'login_failed', ?, ?, ?)");
            $desc = "Failed login attempt for {$row['full_name']} from {$location['city']}";
            $stmt->bind_param("ssss", $row['id'], $desc, $ip, $userAgent);
            $stmt->execute();
            
            echo json_encode(['success' => false, 'message' => 'Invalid password']);
        }
    } else {
        // ❌ USER NOT FOUND - Record failed attempt
        $stmt = $conn->prepare("INSERT INTO login_history 
            (user_id, login_type, ip_address, user_agent, location_city, location_country, failure_reason) 
            VALUES (NULL, 'failed', ?, ?, ?, ?, ?)");
        $reason = "Email not found: $email";
        $stmt->bind_param("sssss", $ip, $userAgent, $location['city'], $location['country'], $reason);
        $stmt->execute();
        
        echo json_encode(['success' => false, 'message' => 'User not found']);
    }
}

// Helper Functions
function getLocationFromIP($ip) {
    // Use free IP geolocation API
    $url = "http://ip-api.com/json/$ip";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    
    $data = json_decode($response, true);
    
    return [
        'city' => $data['city'] ?? 'Unknown',
        'country' => $data['country'] ?? 'Unknown',
        'region' => $data['regionName'] ?? 'Unknown',
        'lat' => $data['lat'] ?? 0,
        'lon' => $data['lon'] ?? 0,
        'isp' => $data['isp'] ?? 'Unknown'
    ];
}

function getDeviceInfo($userAgent) {
    $device = 'Desktop';
    $browser = 'Unknown';
    $os = 'Unknown';
    
    // Detect device
    if (strpos($userAgent, 'Mobile') !== false) $device = 'Mobile';
    if (strpos($userAgent, 'Tablet') !== false) $device = 'Tablet';
    
    // Detect browser
    if (strpos($userAgent, 'Chrome') !== false) $browser = 'Chrome';
    elseif (strpos($userAgent, 'Firefox') !== false) $browser = 'Firefox';
    elseif (strpos($userAgent, 'Safari') !== false) $browser = 'Safari';
    elseif (strpos($userAgent, 'Edge') !== false) $browser = 'Edge';
    elseif (strpos($userAgent, 'Opera') !== false) $browser = 'Opera';
    
    // Detect OS
    if (strpos($userAgent, 'Windows') !== false) $os = 'Windows';
    elseif (strpos($userAgent, 'Mac') !== false) $os = 'macOS';
    elseif (strpos($userAgent, 'Linux') !== false) $os = 'Linux';
    elseif (strpos($userAgent, 'Android') !== false) $os = 'Android';
    elseif (strpos($userAgent, 'iOS') !== false) $os = 'iOS';
    
    return ['device' => $device, 'browser' => $browser, 'os' => $os];
}
?>