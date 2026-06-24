<?php
session_start();

if(isset($_SESSION['user_id'])){
    header('Location: ' . ($_SESSION['role']==='admin' ? 'admin_dashboard.php' : 'view_room.php'));
    exit;
}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Login | Tawi</title>
<style>
body{font-family:Poppins, sans-serif;background:linear-gradient(#F5EFEB,#FFFFFF);display:flex;align-items:center;justify-content:center;height:100vh;margin:0;color:#fff}
.box{background:#fff;color:#174871;padding:28px;border-radius:12px;width:380px;box-shadow:0 6px 20px rgba(0,0,0,0.15)}
input{width:100%;padding:10px;margin:8px 0;border:1px solid #d3e7ff;border-radius:8px}
button{width:100%;padding:10px;background:#263751;border:none;color:#fff;border-radius:8px;cursor:pointer}
.toggle{color:#0d47a1;text-align:center;margin-top:10px;cursor:pointer}
.note{font-size:15px;color:#666;margin-top:6px}
a{color:#0d47a1}
</style>
</head>
<body>
<div class="box">
  <h2 id="title" style="text-align: center;">Login</h2>
  <form id="mainForm" method="POST" action="verify_login.php">
    <input type="hidden" name="action" id="action" value="login">
    <input type="text" name="name" id="name" placeholder="Full Name" style="display:none">
    <input type="email" name="email" placeholder="Email" required>
    <input type="password" name="password" placeholder="Password" required>
    <button type="submit" id="submitBtn">Login</button>
  </form>
  <div class="toggle" onclick="toggle()">Don't have an account? Register</div>
  <div class="note" style="text-align: center;"><a href="reset_password.php">Forgot password?</a></div>
</div>

<script>
function toggle(){
  const action = document.getElementById('action');
  const name = document.getElementById('name');
  const title = document.getElementById('title');
  const btn = document.getElementById('submitBtn');
  if(action.value === 'login'){
    action.value = 'register';
    name.style.display = 'block';
    title.innerText = 'Register';
    btn.innerText = 'Register';
  } else {
    action.value = 'login';
    name.style.display = 'none';
    title.innerText = 'Login';
    btn.innerText = 'Login';
  }
}
</script>
</body>
</html>