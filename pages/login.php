<?php
// login.php - Complete login/registration page for AgriConnect
require_once 'config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    if (isAdmin()) {
        header('Location: admin_dashboard.php');
    } else {
        header('Location: farmer_dashboard.php'); // or buyer_dashboard.php based on role
    }
    exit;
}

$error = '';
$success = '';

// Handle registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'register') {
    $fullName = sanitizeInput($_POST['name']);
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];
    $role = 'farmer'; // Default role
    $phone = sanitizeInput($_POST['phone'] ?? '');
    
    // Validate inputs
    if (empty($fullName) || empty($email) || empty($password)) {
        $error = 'All fields are required';
    } elseif (!isValidEmail($email)) {
        $error = 'Please enter a valid email address';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters';
    } elseif (!empty($phone) && !isValidPhone($phone)) {
        $error = 'Please enter a valid phone number (e.g., 0712345678)';
    } else {
        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = 'Email already registered';
        } else {
            // Hash password and insert user
            $hashedPassword = hashPassword($password);
            $formattedPhone = !empty($phone) ? formatPhoneNumber($phone) : null;
            
            $stmt = $conn->prepare("INSERT INTO users (full_name, phone_number, email, role, password_hash, is_verified) VALUES (?, ?, ?, ?, ?, ?)");
            $isVerified = 1; // Auto-verify for now (would send email in production)
            $stmt->bind_param("sssssi", $fullName, $formattedPhone, $email, $role, $hashedPassword, $isVerified);
            
            if ($stmt->execute()) {
                $userId = $conn->insert_id;
                
                // Log registration
                logActivity($userId, 'registration', 'User registered successfully');
                
                $success = 'Registration successful! Please login.';
                
                // Auto-login after registration
                createUserSession($userId, $fullName, $role);
                
                // Redirect based on role
                if ($role === 'admin') {
                    header('Location: admin_dashboard.php');
                } else {
                    header('Location: farmer_dashboard.php');
                }
                exit;
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];
    $ip = $_SERVER['REMOTE_ADDR'];
    $userAgent = $_SERVER['HTTP_USER_AGENT'];
    $location = getLocationFromIP($ip);
    
    if (empty($email) || empty($password)) {
        $error = 'Email and password are required';
    } else {
        // Check rate limit
        if (!checkRateLimit($ip, 5, 300)) { // 5 attempts in 5 minutes
            $error = 'Too many login attempts. Please try again later.';
            logLoginAttempt(null, 'failed', $ip, $userAgent, $location, 'Rate limit exceeded');
        } else {
            // Query user
            $stmt = $conn->prepare("SELECT id, full_name, role, password_hash, is_verified FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($row = $result->fetch_assoc()) {
                // Check if account is verified
                if ($row['is_verified'] == 0) {
                    $error = 'Please verify your email before logging in.';
                    logLoginAttempt($row['id'], 'failed', $ip, $userAgent, $location, 'Account not verified');
                } elseif (verifyPassword($password, $row['password_hash'])) {
                    // ✅ Login successful
                    createUserSession($row['id'], $row['full_name'], $row['role']);
                    
                    // Log successful login
                    logLoginAttempt($row['id'], 'success', $ip, $userAgent, $location);
                    logActivity($row['id'], 'login_success', "User logged in from {$location['city']}, {$location['country']}");
                    
                    // Update last login
                    $stmt = $conn->prepare("UPDATE users SET updated_at = NOW() WHERE id = ?");
                    $stmt->bind_param("s", $row['id']);
                    $stmt->execute();
                    
                    // Redirect based on role
                    if ($row['role'] === 'admin') {
                        header('Location: admin_dashboard.php');
                    } elseif ($row['role'] === 'buyer') {
                        header('Location: buyer_dashboard.php');
                    } elseif ($row['role'] === 'transporter') {
                        header('Location: transporter_dashboard.php');
                    } elseif ($row['role'] === 'inspector') {
                        header('Location: inspector_dashboard.php');
                    } else {
                        header('Location: farmer_dashboard.php');
                    }
                    exit;
                } else {
                    // ❌ Wrong password
                    $error = 'Invalid email or password';
                    logLoginAttempt($row['id'], 'failed', $ip, $userAgent, $location, 'Incorrect password');
                }
            } else {
                // ❌ User not found
                $error = 'Invalid email or password';
                logLoginAttempt(null, 'failed', $ip, $userAgent, $location, 'User not found');
            }
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | AgriConnect</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1e7e34, #0d4a1e);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }
        .container {
            background: #fff;
            border-radius: 20px;
            padding: 40px 35px;
            width: 420px;
            max-width: 100%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        .logo {
            text-align: center;
            margin-bottom: 25px;
        }
        .logo h1 {
            color: #1e7e34;
            font-size: 2rem;
            font-weight: 700;
        }
        .logo span {
            color: #1e293b;
        }
        .logo p {
            color: #64748b;
            font-size: 0.9rem;
        }
        h2 {
            color: #1e293b;
            font-size: 1.5rem;
            margin-bottom: 20px;
            text-align: center;
        }
        .form-group {
            margin-bottom: 16px;
        }
        label {
            display: block;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 5px;
            font-size: 0.9rem;
        }
        input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e9edf2;
            border-radius: 10px;
            font-size: 0.95rem;
            transition: border-color 0.3s;
            font-family: inherit;
        }
        input:focus {
            outline: none;
            border-color: #1e7e34;
        }
        .error-msg {
            background: #fee2e2;
            color: #991b1b;
            padding: 12px 15px;
            border-radius: 10px;
            margin-bottom: 15px;
            font-size: 0.9rem;
        }
        .success-msg {
            background: #dcfce7;
            color: #166534;
            padding: 12px 15px;
            border-radius: 10px;
            margin-bottom: 15px;
            font-size: 0.9rem;
        }
        button {
            width: 100%;
            padding: 14px;
            background: #1e7e34;
            border: none;
            color: #fff;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
            font-family: inherit;
        }
        button:hover {
            background: #16632a;
        }
        button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        .toggle {
            text-align: center;
            margin-top: 15px;
            color: #64748b;
            cursor: pointer;
            transition: color 0.3s;
            font-size: 0.9rem;
        }
        .toggle:hover {
            color: #1e7e34;
        }
        .note {
            text-align: center;
            margin-top: 10px;
            font-size: 0.85rem;
        }
        .note a {
            color: #1e7e34;
            text-decoration: none;
        }
        .note a:hover {
            text-decoration: underline;
        }
        .hidden {
            display: none !important;
        }
        .role-select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e9edf2;
            border-radius: 10px;
            font-size: 0.95rem;
            font-family: inherit;
            background: white;
        }
        .role-select:focus {
            outline: none;
            border-color: #1e7e34;
        }
        .input-icon {
            position: relative;
        }
        .input-icon i {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
        }
        @media (max-width: 500px) {
            .container { padding: 25px 20px; }
            .logo h1 { font-size: 1.5rem; }
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="container">
        <div class="logo">
            <h1>🌱 Agri<span>Connect</span></h1>
            <p>Farm to Table Marketplace</p>
        </div>
        
        <h2 id="formTitle">Welcome Back</h2>
        
        <?php if ($error): ?>
            <div class="error-msg">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success-msg">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        
        <form id="mainForm" method="POST" action="login.php" novalidate>
            <input type="hidden" name="action" id="action" value="login">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            
            <div id="nameField" class="hidden">
                <div class="form-group">
                    <label for="name"><i class="fas fa-user"></i> Full Name</label>
                    <input type="text" name="name" id="name" placeholder="John Doe" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label for="email"><i class="fas fa-envelope"></i> Email Address</label>
                <input type="email" name="email" id="email" placeholder="you@example.com" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
            </div>
            
            <div id="phoneField" class="hidden">
                <div class="form-group">
                    <label for="phone"><i class="fas fa-phone"></i> Phone Number</label>
                    <input type="tel" name="phone" id="phone" placeholder="0712345678" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                    <small style="color:#64748b; font-size:0.75rem;">Format: 0712345678</small>
                </div>
            </div>
            
            <div id="roleField" class="hidden">
                <div class="form-group">
                    <label for="role"><i class="fas fa-user-tag"></i> I am a</label>
                    <select name="role" id="role" class="role-select">
                        <option value="farmer">🚜 Farmer</option>
                        <option value="buyer">🛒 Buyer</option>
                        <option value="transporter">🚛 Transporter</option>
                        <option value="inspector">✅ Inspector</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label for="password"><i class="fas fa-lock"></i> Password</label>
                <input type="password" name="password" id="password" placeholder="Enter your password" required minlength="8">
                <small style="color:#64748b; font-size:0.75rem;">Minimum 8 characters</small>
            </div>
            
            <button type="submit" id="submitBtn">
                <i class="fas fa-sign-in-alt"></i> Login
            </button>
        </form>
        
        <div class="toggle" onclick="toggleForm()">
            <span id="toggleText">New user? Create an account</span>
        </div>
        
        <div class="note">
            <a href="forgot_password.php"><i class="fas fa-key"></i> Forgot password?</a>
        </div>
        
        <div style="text-align:center; margin-top:15px; font-size:0.8rem; color:#94a3b8;">
            <a href="index.html" style="color:#94a3b8; text-decoration:none;">← Back to Home</a>
        </div>
    </div>

    <script>
        function toggleForm() {
            const action = document.getElementById('action');
            const nameField = document.getElementById('nameField');
            const phoneField = document.getElementById('phoneField');
            const roleField = document.getElementById('roleField');
            const title = document.getElementById('formTitle');
            const btn = document.getElementById('submitBtn');
            const toggleText = document.getElementById('toggleText');
            
            if (action.value === 'login') {
                // Switch to Register
                action.value = 'register';
                nameField.classList.remove('hidden');
                phoneField.classList.remove('hidden');
                roleField.classList.remove('hidden');
                title.innerText = 'Create Your Account';
                btn.innerHTML = '<i class="fas fa-user-plus"></i> Register';
                toggleText.innerText = 'Already have an account? Login';
            } else {
                // Switch to Login
                action.value = 'login';
                nameField.classList.add('hidden');
                phoneField.classList.add('hidden');
                roleField.classList.add('hidden');
                title.innerText = 'Welcome Back';
                btn.innerHTML = '<i class="fas fa-sign-in-alt"></i> Login';
                toggleText.innerText = 'New user? Create an account';
            }
        }
        
        // Toggle password visibility (optional)
        document.querySelector('input[type="password"]')?.addEventListener('input', function() {
            // Optional: Add password strength indicator
        });
        
        // Form validation
        document.getElementById('mainForm').addEventListener('submit', function(e) {
            const action = document.getElementById('action').value;
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            
            if (action === 'register') {
                const name = document.getElementById('name').value;
                if (!name.trim()) {
                    e.preventDefault();
                    alert('Please enter your full name');
                    document.getElementById('name').focus();
                    return false;
                }
                if (name.trim().length < 2) {
                    e.preventDefault();
                    alert('Name must be at least 2 characters');
                    document.getElementById('name').focus();
                    return false;
                }
            }
            
            if (!email.trim()) {
                e.preventDefault();
                alert('Please enter your email address');
                document.getElementById('email').focus();
                return false;
            }
            
            if (!password.trim()) {
                e.preventDefault();
                alert('Please enter your password');
                document.getElementById('password').focus();
                return false;
            }
            
            if (password.length < 8) {
                e.preventDefault();
                alert('Password must be at least 8 characters');
                document.getElementById('password').focus();
                return false;
            }
            
            return true;
        });
    </script>
</body>
</html>