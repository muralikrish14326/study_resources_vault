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

$success = "";

if (isset($_POST['delete_selected']) && !empty($_POST['log_ids'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Invalid request!");
    }
    $ids = $_POST['log_ids'];
    $id_list = implode(',', array_map('intval', $ids));
    $conn->query("DELETE FROM activity_log WHERE id IN ($id_list)");
    header("Location: logs.php?msg=deleted");
    exit();
}

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 50;
$offset = ($page - 1) * $limit;

$total_res = $conn->query("SELECT COUNT(*) as total FROM activity_log");
$total_row = $total_res->fetch_assoc();
$total_pages = ceil($total_row['total'] / $limit);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Activity Logs | Staff Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        :root { --primary: #3b82f6; --bg: #0b0f1a; --card-bg: rgba(30, 41, 59, 0.5); --border: rgba(255, 255, 255, 0.08); --text-dim: #94a3b8; --danger: #ef4444; --success: #10b981; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background: radial-gradient(at 0% 0%, #0f172a 0, transparent 50%), var(--bg); min-height: 100vh; color: white; padding: 40px 20px; }
        .container { max-width: 1100px; margin: 0 auto; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .btn-group { display: flex; gap: 10px; }
        .back-btn { text-decoration: none; color: var(--text-dim); border: 1px solid var(--border); padding: 10px 18px; border-radius: 12px; transition: 0.3s; font-size: 0.9rem; }
        .back-btn:hover { background: rgba(255,255,255,0.05); color: white; }
        .delete-btn { background: var(--danger); color: white; border: none; padding: 10px 20px; border-radius: 12px; font-weight: 700; cursor: pointer; transition: 0.3s; display: none; font-size: 0.9rem; }
        .delete-btn:hover { background: #dc2626; transform: translateY(-2px); }
        .success-msg { background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.3); color: var(--success); padding: 12px 20px; border-radius: 12px; margin-bottom: 20px; font-size: 0.9rem; }
        .log-table { width: 100%; border-collapse: collapse; background: var(--card-bg); border-radius: 20px; overflow: hidden; border: 1px solid var(--border); backdrop-filter: blur(10px); }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid var(--border); }
        th { background: rgba(59, 130, 246, 0.1); color: var(--primary); font-weight: 700; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 1px; }
        .custom-cb { width: 18px; height: 18px; cursor: pointer; accent-color: var(--primary); }
        .badge { padding: 4px 10px; border-radius: 6px; font-size: 0.75rem; font-weight: 600; }
        .login-badge { background: rgba(16, 185, 129, 0.1); color: #10b981; }
        .view-badge { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }
        tr.selected { background: rgba(59, 130, 246, 0.05); }
        .pagination { display: flex; justify-content: center; gap: 8px; margin-top: 25px; flex-wrap: wrap; }
        .page-btn { padding: 8px 14px; border-radius: 10px; border: 1px solid var(--border); color: var(--text-dim); text-decoration: none; font-size: 0.85rem; transition: 0.3s; }
        .page-btn:hover { background: rgba(255,255,255,0.05); color: white; }
        .page-btn.active { background: var(--primary); color: white; border-color: var(--primary); }
    </style>
</head>
<body>
    <div class="container">
        <?php if(isset($_GET['msg']) && $_GET['msg'] == 'deleted'): ?>
            <div class="success-msg"><i class="fa-solid fa-check-circle"></i> Selected logs deleted successfully!</div>
        <?php endif; ?>

        <form method="POST" id="logForm">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <div class="header">
                <div>
                    <h2><i class="fa-solid fa-clock-rotate-left"></i> Activity Logs</h2>
                    <p style="color: var(--text-dim); font-size: 0.85rem; margin-top: 5px;">
                        Page <?= $page ?> of <?= $total_pages ?> — <?= $total_row['total'] ?> total records
                    </p>
                </div>
                <div class="btn-group">
                    <button type="submit" name="delete_selected" class="delete-btn" id="bulkDeleteBtn" onclick="return confirm('Delete selected logs?')">
                        <i class="fa-solid fa-trash"></i> Delete Selected
                    </button>
                    <a href="staff.php" class="back-btn"><i class="fa-solid fa-arrow-left"></i> Dashboard</a>
                </div>
            </div>

            <table class="log-table">
                <thead>
                    <tr>
                        <th style="width: 50px;"><input type="checkbox" id="selectAll" class="custom-cb"></th>
                        <th>Roll Number</th>
                        <th>Action Type</th>
                        <th>Details</th>
                        <th>Time & Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $res = $conn->query("SELECT * FROM activity_log ORDER BY timestamp DESC LIMIT $limit OFFSET $offset");
                    if ($res && $res->num_rows > 0) {
                        while($row = $res->fetch_assoc()) {
                            $badgeClass = ($row['action_type'] == 'Login') ? 'login-badge' : 'view-badge';
                            $detail = $row['subject'] ?: ($row['filename'] ?: 'Portal Access');
                            echo "<tr>";
                            echo "<td><input type='checkbox' name='log_ids[]' value='".$row['id']."' class='custom-cb log-checkbox' onchange='toggleDeleteBtn()'></td>";
                            echo "<td><strong>" . htmlspecialchars($row['rollno']) . "</strong></td>";
                            echo "<td><span class='badge $badgeClass'>" . htmlspecialchars($row['action_type']) . "</span></td>";
                            echo "<td>" . htmlspecialchars($detail) . "</td>";
                            echo "<td>" . date('d M Y | h:i A', strtotime($row['timestamp'])) . "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='5' style='text-align:center; padding:40px; color:var(--text-dim);'>No activity logs found.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </form>

        <?php if($total_pages > 1): ?>
        <div class="pagination">
            <?php if($page > 1): ?>
                <a href="logs.php?page=<?= $page-1 ?>" class="page-btn"><i class="fa-solid fa-chevron-left"></i></a>
            <?php endif; ?>
            <?php for($i = 1; $i <= $total_pages; $i++): ?>
                <a href="logs.php?page=<?= $i ?>" class="page-btn <?= ($i == $page) ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
            <?php if($page < $total_pages): ?>
                <a href="logs.php?page=<?= $page+1 ?>" class="page-btn"><i class="fa-solid fa-chevron-right"></i></a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <script>
        const selectAll = document.getElementById('selectAll');
        const checkboxes = document.getElementsByClassName('log-checkbox');
        const deleteBtn = document.getElementById('bulkDeleteBtn');

        selectAll.onclick = function() {
            for (let checkbox of checkboxes) {
                checkbox.checked = this.checked;
                checkbox.closest('tr').classList.toggle('selected', this.checked);
            }
            toggleDeleteBtn();
        }

        function toggleDeleteBtn() {
            let checkedCount = 0;
            for (let checkbox of checkboxes) {
                if (checkbox.checked) {
                    checkedCount++;
                    checkbox.closest('tr').classList.add('selected');
                } else {
                    checkbox.closest('tr').classList.remove('selected');
                }
            }
            deleteBtn.style.display = (checkedCount > 0) ? "block" : "none";
            selectAll.checked = (checkedCount === checkboxes.length && checkboxes.length > 0);
        }
    </script>
</body>
</html>