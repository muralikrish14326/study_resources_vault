<?php
session_start();
$conn = new mysqli("localhost", "root", "", "study_vault");

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: index.php");
    exit();
}

function getFileCount($conn, $subjectName) {
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM resources WHERE subject = ?");
    $stmt->bind_param("s", $subjectName);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['total'];
}

$subs = [
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
    <title>Student Portal | IT Digital Library</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #3b82f6; --bg: #0b0f1a; --card-bg: rgba(30, 41, 59, 0.5); --border: rgba(255, 255, 255, 0.08); --text-dim: #94a3b8; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background: radial-gradient(at 0% 0%, #0f172a 0, transparent 50%), var(--bg); min-height: 100vh; color: white; padding: 40px 20px; }
        .nav { display: flex; justify-content: space-between; max-width: 1200px; margin: 0 auto 40px; align-items: center; flex-wrap: wrap; gap: 10px; }
        .welcome { font-size: 0.9rem; color: var(--text-dim); margin-top: 4px; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 25px; max-width: 1200px; margin: 0 auto; }
        .card { background: var(--card-bg); padding: 40px 20px; border-radius: 24px; text-align: center; border: 1px solid var(--border); text-decoration: none; color: white; transition: 0.3s; position: relative; overflow: hidden; backdrop-filter: blur(10px); }
        .card:hover { transform: translateY(-10px); border-color: var(--primary); background: rgba(59, 130, 246, 0.1); }
        .icon { font-size: 2.5rem; margin-bottom: 15px; display: block; }
        .count-badge { background: var(--primary); color: white; padding: 4px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; position: absolute; top: 15px; right: 15px; }
        .no-file-badge { background: #475569; color: white; padding: 4px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; position: absolute; top: 15px; right: 15px; }
        .subject-name { font-size: 1.1rem; font-weight: 600; }
        .signout-btn { color: var(--text-dim); text-decoration: none; border: 1px solid var(--border); padding: 8px 18px; border-radius: 12px; font-size: 0.9rem; transition: 0.3s; }
        .signout-btn:hover { background: rgba(255,255,255,0.05); color: white; }
        @media (max-width: 600px) { body { padding: 20px 15px; } }
    </style>
</head>
<body>
    <div class="nav">
        <div>
            <h1>IT Digital Library</h1>
            <p class="welcome">Welcome, <?php echo strtoupper(htmlspecialchars($_SESSION['rollno'])); ?> 👋</p>
        </div>
        <a href="logout.php" class="signout-btn">Sign Out</a>
    </div>
    <div class="grid">
        <?php
        foreach ($subs as $s) {
            $count = getFileCount($conn, $s['name']);
            echo "<a href='view.php?subject=".urlencode($s['name'])."' class='card'>";
            if ($count > 0) {
                echo "<span class='count-badge'>$count Files</span>";
            } else {
                echo "<span class='no-file-badge'>No Files</span>";
            }
            echo "<span class='icon'>".$s['icon']."</span>";
            echo "<h3 class='subject-name'>".htmlspecialchars($s['name'])."</h3></a>";
        }
        ?>
    </div>
</body>
</html>