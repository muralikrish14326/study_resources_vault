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

$rollno  = $_SESSION['rollno'];
$subject = isset($_GET['subject']) ? trim($_GET['subject']) : '';

if ($subject != '') {
    $stmt = $conn->prepare("INSERT INTO activity_log (rollno, subject, action_type) VALUES (?, ?, 'View Subject')");
    $stmt->bind_param("ss", $rollno, $subject);
    $stmt->execute();
}

if (isset($_GET['log_file'])) {
    $file_name = basename($_GET['log_file']);
    $safe_path = "uploads/" . $file_name;

    if (!file_exists($safe_path)) {
        die("File not found!");
    }

    $stmt = $conn->prepare("INSERT INTO activity_log (rollno, subject, filename, action_type) VALUES (?, ?, ?, 'View File')");
    $stmt->bind_param("sss", $rollno, $subject, $file_name);
    $stmt->execute();

    header("Location: " . $safe_path);
    exit();
}

$stmt = $conn->prepare("SELECT * FROM resources WHERE subject = ? ORDER BY id DESC");
$stmt->bind_param("s", $subject);
$stmt->execute();
$res = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($subject); ?> | IT Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #3b82f6; --success: #10b981; --bg: #0b0f1a; --card-bg: rgba(30, 41, 59, 0.5); --border: rgba(255, 255, 255, 0.08); --text-main: #f8fafc; --text-dim: #94a3b8; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background: radial-gradient(at 0% 0%, #0f172a 0, transparent 50%), var(--bg); min-height: 100vh; color: var(--text-main); padding: 40px 20px; }
        .container { max-width: 800px; margin: 0 auto; }
        .header-section { margin-bottom: 30px; }
        .back-link { text-decoration: none; color: var(--text-dim); font-size: 0.9rem; margin-bottom: 15px; display: inline-block; }
        .back-link:hover { color: white; }
        h1 { font-size: 1.8rem; border-left: 4px solid var(--primary); padding-left: 15px; margin-bottom: 25px; }
        .search-box { width: 100%; padding: 15px 20px; background: rgba(15, 23, 42, 0.8); border: 1px solid var(--border); border-radius: 15px; color: white; font-size: 1rem; margin-bottom: 30px; transition: 0.3s; outline: none; }
        .search-box:focus { border-color: var(--primary); box-shadow: 0 0 15px rgba(59, 130, 246, 0.2); }
        .file-card { background: var(--card-bg); border: 1px solid var(--border); padding: 20px; border-radius: 20px; backdrop-filter: blur(10px); display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; transition: 0.3s; }
        .file-card:hover { border-color: rgba(255,255,255,0.2); transform: scale(1.01); }
        .file-info h3 { font-size: 1rem; margin-bottom: 5px; }
        .file-info span { font-size: 0.75rem; color: var(--text-dim); }
        .btn-group { display: flex; gap: 10px; }
        .btn { padding: 8px 16px; text-decoration: none; border-radius: 10px; font-weight: 700; font-size: 0.8rem; transition: 0.3s; }
        .view-btn { border: 1px solid var(--primary); color: var(--primary); }
        .view-btn:hover { background: var(--primary); color: white; }
        .download-btn { background: var(--success); color: #0b0f1a; }
        .download-btn:hover { opacity: 0.85; }
        .no-results { text-align: center; padding: 40px; color: var(--text-dim); display: none; }
        @media (max-width: 600px) { .file-card { flex-direction: column; align-items: flex-start; gap: 15px; } body { padding: 20px 15px; } }
    </style>
</head>
<body>
    <div class="container">
        <div class="header-section">
            <a href="student.php" class="back-link">← Back to Subjects</a>
            <h1><?php echo htmlspecialchars($subject ?: 'Resources'); ?></h1>
            <input type="text" id="searchInput" class="search-box" placeholder="Search materials (e.g. Unit 1, Assignment)..." onkeyup="filterFiles()">
        </div>

        <div id="resourceContainer">
            <?php
            if ($res && $res->num_rows > 0) {
                while($row = $res->fetch_assoc()) {
                    $filename = $row['filename'];
                    $title    = $row['title'];
                    echo "<div class='file-card' data-title='".strtolower(htmlspecialchars($title))."'>";
                    echo "  <div class='file-info'>";
                    echo "      <h3>" . htmlspecialchars($title) . "</h3>";
                    echo "      <span>📄 " . htmlspecialchars($filename) . "</span>";
                    echo "  </div>";
                    echo "  <div class='btn-group'>";
                    echo "      <a href='view.php?subject=".urlencode($subject)."&log_file=".urlencode($filename)."' target='_blank' class='btn view-btn'>View</a>";
                    echo "      <a href='download.php?file=".urlencode($filename)."' class='btn download-btn'>Download</a>";
                    echo "  </div>";
                    echo "</div>";
                }
            } else {
                echo "<div style='text-align:center; padding:50px; color:var(--text-dim); border:1px dashed var(--border); border-radius:20px;'><p>No resources found for this subject.</p></div>";
            }
            ?>
            <div id="noResults" class="no-results">No matching materials found.</div>
        </div>
    </div>

    <script>
    function filterFiles() {
        let input = document.getElementById('searchInput').value.toLowerCase();
        let cards = document.getElementsByClassName('file-card');
        let noResults = document.getElementById('noResults');
        let visibleCount = 0;
        for (let i = 0; i < cards.length; i++) {
            let title = cards[i].getAttribute('data-title');
            if (title.indexOf(input) > -1) {
                cards[i].style.display = "flex";
                visibleCount++;
            } else {
                cards[i].style.display = "none";
            }
        }
        if (cards.length > 0) {
            noResults.style.display = (visibleCount === 0) ? "block" : "none";
        }
    }
    </script>
</body>
</html>