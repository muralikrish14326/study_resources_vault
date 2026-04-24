<?php
session_start();
$conn = new mysqli("localhost", "root", "", "study_vault");

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

if (isset($_SESSION['role'])) {
    session_unset();
    session_destroy();
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $rollno = trim($_POST['rollno']);
    $pass   = trim($_POST['password']);
    $role   = $_POST['role'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE rollno = ? AND role = ?");
    $stmt->bind_param("ss", $rollno, $role);
    $stmt->execute();
    $res  = $stmt->get_result();
    $user = $res->fetch_assoc();

    if ($user && hash('sha256', $pass) === $user['password_hash']) {
        $_SESSION['role']   = $user['role'];
        $_SESSION['rollno'] = $user['rollno'];

        if ($role === 'student') {
            $log = $conn->prepare("INSERT INTO activity_log (rollno, action_type) VALUES (?, 'Login')");
            $log->bind_param("s", $rollno);
            $log->execute();
        }
        header("Location: redirect.php");
        exit();
    } else {
        $error = "Invalid credentials. Please try again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Study Vault | Secure Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        :root { --primary: #3b82f6; --bg: #0b0f1a; --card-bg: rgba(30, 41, 59, 0.5); --border: rgba(255, 255, 255, 0.08); }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background: radial-gradient(at 0% 0%, #1e1b4b 0, transparent 50%), var(--bg); height: 100vh; display: flex; align-items: center; justify-content: center; color: white; }
        .login-card { background: var(--card-bg); backdrop-filter: blur(20px); border: 1px solid var(--border); padding: 40px; border-radius: 28px; width: 100%; max-width: 400px; text-align: center; }
        h1 { font-size: 1.8rem; margin-bottom: 10px; }
        input, select { width: 100%; padding: 12px; background: rgba(15, 23, 42, 0.6); border: 1px solid var(--border); border-radius: 12px; color: white; margin-bottom: 15px; outline: none; transition: 0.3s; }
        input:focus, select:focus { border-color: var(--primary); }
        select option { background: #1e293b; color: white; }
        .pass-container { position: relative; width: 100%; margin-bottom: 15px; }
        .pass-container input { margin-bottom: 0; padding-right: 45px; }
        .toggle-pass { position: absolute; right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #94a3b8; font-size: 1rem; user-select: none; }
        .toggle-pass:hover { color: var(--primary); }
        .btn { width: 100%; padding: 14px; background: var(--primary); color: white; border: none; border-radius: 12px; font-weight: 700; cursor: pointer; transition: 0.3s; margin-top: 10px; }
        .btn:hover { background: #2563eb; transform: translateY(-2px); }
        .error-msg { color: #f87171; background: rgba(248, 113, 113, 0.1); padding: 10px; border-radius: 8px; font-size: 0.8rem; margin-bottom: 15px; border: 1px solid rgba(248, 113, 113, 0.2); }
    </style>
</head>
<body>
    <div class="login-card">
        <h1>Study Vault</h1>
        <p id="login-text" style="margin-bottom: 25px; color: #94a3b8; font-size: 0.9rem;">Login with Roll Number</p>
        <?php if ($error) echo "<div class='error-msg'>".htmlspecialchars($error)."</div>"; ?>
        <form method="POST" autocomplete="off">
            <select name="role" id="roleSelect" onchange="updatePlaceholders()">
                <option value="student">Student</option>
                <option value="staff">Staff</option>
            </select>
            <input type="text" name="rollno" id="rollInput" placeholder="Roll Number (e.g. 24it037)" required>
            <div class="pass-container">
                <input type="password" name="password" id="passInput" placeholder="Password (DD-MM-YYYY)" required>
                <i class="fa-solid fa-eye toggle-pass" id="eyeIcon" onclick="togglePassword()"></i>
            </div>
            <button type="submit" class="btn">Sign In</button>
        </form>
    </div>
    <script>
        window.onload = function() {
            document.querySelector('form').reset();
        }

        function updatePlaceholders() {
            const role = document.getElementById('roleSelect').value;
            const rollInput = document.getElementById('rollInput');
            const passInput = document.getElementById('passInput');
            const loginText = document.getElementById('login-text');
            rollInput.value = "";
            passInput.value = "";
            if (role === 'staff') {
                rollInput.placeholder = "Staff ID (e.g. staff01)";
                passInput.placeholder = "Staff Password";
                loginText.innerText = "Login with Staff ID";
            } else {
                rollInput.placeholder = "Roll Number (e.g. 24it037)";
                passInput.placeholder = "Password (DD-MM-YYYY)";
                loginText.innerText = "Login with Roll Number";
            }
        }

        function togglePassword() {
            const passInput = document.getElementById('passInput');
            const eyeIcon = document.getElementById('eyeIcon');
            if (passInput.type === "password") {
                passInput.type = "text";
                eyeIcon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                passInput.type = "password";
                eyeIcon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }
    </script>
</body>
</html>