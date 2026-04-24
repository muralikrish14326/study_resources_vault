<?php
session_start();
$conn = new mysqli("localhost", "root", "", "study_vault");

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'staff') {
    header("Location: index.php");
    exit();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_id'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Invalid request!");
    }
    $id = intval($_POST['delete_id']);
    $res = $conn->query("SELECT filename FROM resources WHERE id=$id");
    if ($row = $res->fetch_assoc()) {
        $filePath = "uploads/" . basename($row['filename']);
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }
    $conn->query("DELETE FROM resources WHERE id=$id");
    $_SESSION['flash'] = "File deleted successfully!";
    header("Location: staff.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES['file'])) {
    $subject  = trim($_POST['subject']);
    $title    = trim($_POST['title']);
    $origName = $_FILES['file']['name'];
    $tmpPath  = $_FILES['file']['tmp_name'];
    $fileSize = $_FILES['file']['size'];

    $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
    $maxSize = 10 * 1024 * 1024;

    if ($ext !== 'pdf') {
        $message = "<div class='msg error-msg'>❌ Only PDF files allowed!</div>";
    } elseif ($fileSize > $maxSize) {
        $message = "<div class='msg error-msg'>❌ File too large! Max 10MB allowed.</div>";
    } else {
        $safeFilename = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $origName);
        if (!is_dir('uploads')) { mkdir('uploads', 0755, true); }
        if (move_uploaded_file($tmpPath, "uploads/" . $safeFilename)) {
            $stmt = $conn->prepare("INSERT INTO resources (subject, title, filename) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $subject, $title, $safeFilename);
            $stmt->execute();
            $_SESSION['flash'] = "✅ File Uploaded Successfully!";
            header("Location: staff.php");
            exit();
        }
    }
}

if (isset($_SESSION['flash'])) {
    $message = "<div class='msg success-msg'>" . $_SESSION['flash'] . "</div>";
    unset($_SESSION['flash']);
}

$subjects = [
    ["name" => "Web Programming",                 "icon" => "🌐"],
    ["name" => "Artificial Intelligence",          "icon" => "🧠"],
    ["name" => "Database Management Systems",      "icon" => "📂"],
    ["name" => "Computer Architecture",            "icon" => "💻"],
    ["name" => "Probability and Queuing Theory",   "icon" => "📊"],
    ["name" => "Design and Analysis of Algorithms","icon" => "⚙️"]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Staff Portal | Study Vault</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        :root { --primary: #3b82f6; --bg: #0b0f1a; --card-bg: rgba(30, 41, 59, 0.5); --border: rgba(255, 255, 255, 0.08); --text-dim: #94a3b8; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background: radial-gradient(at 0% 0%, #0f172a 0, transparent 50%), var(--bg); min-height: 100vh; color: white; padding: 20px; }
        .nav { display: flex; justify-content: space-between; padding: 20px; border-bottom: 1px solid var(--border); margin-bottom: 30px; align-items: center; max-width: 1200px; margin-left: auto; margin-right: auto; }
        .nav-links { display: flex; gap: 15px; align-items: center; }
        .log-link { color: var(--primary); text-decoration: none; border: 1px solid var(--primary); padding: 8px 18px; border-radius: 12px; font-size: 0.9rem; transition: 0.3s; font-weight: 600; }
        .log-link:hover { background: var(--primary); color: white; }
        .signout-btn { color: var(--text-dim); text-decoration: none; border: 1px solid var(--border); padding: 8px 18px; border-radius: 12px; font-size: 0.9rem; transition: 0.3s; }
        .signout-btn:hover { background: rgba(255,255,255,0.05); color: white; }
        .msg { text-align: center; margin-bottom: 20px; padding: 12px; border-radius: 10px; font-size: 0.9rem; }
        .success-msg { color: #10b981; background: rgba(16,185,129,0.1); border: 1px solid rgba(16,185,129,0.2); }
        .error-msg { color: #f87171; background: rgba(248,113,113,0.1); border: 1px solid rgba(248,113,113,0.2); }
        .container { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 20px; max-width: 1200px; margin: 0 auto; }
        .card { background: var(--card-bg); padding: 25px; border-radius: 24px; border: 1px solid var(--border); backdrop-filter: blur(10px); }
        .card h3 { margin-bottom: 20px; font-size: 1.1rem; color: #f8fafc; }
        input[type="text"] { width: 100%; margin-bottom: 12px; padding: 12px; background: rgba(15, 23, 42, 0.6); border: 1px solid var(--border); color: white; border-radius: 12px; outline: none; }
        input[type="text"]:focus { border-color: var(--primary); }
        input[type="file"] { width: 100%; margin-bottom: 12px; padding: 10px; background: rgba(15, 23, 42, 0.6); border: 1px solid var(--border); color: var(--text-dim); border-radius: 12px; }
        .upload-btn { background: var(--primary); color: white; border: none; padding: 12px; width: 100%; border-radius: 12px; font-weight: 700; cursor: pointer; transition: 0.3s; }
        .upload-btn:hover { background: #2563eb; transform: translateY(-2px); }
        .file-hint { font-size: 0.75rem; color: var(--text-dim); margin-bottom: 12px; }
        .section-title { margin: 50px auto 20px; max-width: 1200px; border-left: 4px solid var(--primary); padding-left: 15px; }
        .manage-table { width: 100%; border-collapse: collapse; background: var(--card-bg); border-radius: 20px; overflow: hidden; margin-top: 20px; border: 1px solid var(--border); }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid var(--border); }
        th { background: rgba(59, 130, 246, 0.1); color: var(--primary); font-size: 0.85rem; text-transform: uppercase; }
        .del-btn { color: #ef4444; border: 1px solid #ef4444; padding: 6px 12px; border-radius: 8px; font-size: 0.8rem; transition: 0.3s; background: none; cursor: pointer; font-weight: 600; }
        .del-btn:hover { background: #ef4444; color: white; }
    </style>
</head>
<body>
    <div class="nav">
        <h2>STUDY_VAULT STAFF</h2>
        <div class="nav-links">
            <a href="logs.php" class="log-link"><i class="fa-solid fa-clock-rotate-left"></i> View Activity Logs</a>
            <a href="index.php" class="signout-btn"><i class="fa-solid fa-power-off"></i> Sign Out</a>
        </div>
    </div>

    <div style="max-width: 1200px; margin: 0 auto;">
        <?php echo $message; ?>
    </div>

    <div class="container">
        <?php foreach ($subjects as $s): ?>
            <div class="card">
                <h3><?php echo $s['icon']." ".htmlspecialchars($s['name']); ?></h3>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="subject" value="<?php echo htmlspecialchars($s['name']); ?>">
                    <input type="text" name="title" placeholder="Enter Material Title" required>
                    <input type="file" name="file" accept=".pdf" required>
                    <p class="file-hint"><i class="fa-solid fa-circle-info"></i> PDF only, max 10MB</p>
                    <button type="submit" class="upload-btn"><i class="fa-solid fa-cloud-arrow-up"></i> Upload Now</button>
                </form>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="section-title"><h2>Manage Uploaded Documents</h2></div>
    <div style="max-width:1200px; margin: 0 auto 50px; overflow-x: auto;">
        <table class="manage-table">
            <thead>
                <tr>
                    <th>Subject</th>
                    <th>Material Title</th>
                    <th>File Name</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $res = $conn->query("SELECT * FROM resources ORDER BY id DESC");
                if ($res->num_rows > 0) {
                    while($row = $res->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($row['subject']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['title']) . "</td>";
                        echo "<td><i class='fa-regular fa-file-pdf' style='color:#94a3b8; margin-right:5px;'></i>" . htmlspecialchars($row['filename']) . "</td>";
                        echo "<td>
                            <form method='POST' style='display:inline;' onsubmit='return confirm(\"Delete this file?\")'>
                                <input type='hidden' name='csrf_token' value='".$_SESSION['csrf_token']."'>
                                <input type='hidden' name='delete_id' value='".$row['id']."'>
                                <button type='submit' class='del-btn'><i class='fa-solid fa-trash-can'></i> Delete</button>
                            </form>
                        </td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='4' style='text-align:center; padding:30px; color:var(--text-dim);'>No files uploaded yet.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</body>
</html>