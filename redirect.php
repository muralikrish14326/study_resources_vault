<?php
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

if (!isset($_SESSION['role'])) {
    header("Location: index.php");
    exit();
}

if ($_SESSION['role'] === 'student') {
    header("Location: student.php");
} else {
    header("Location: staff.php");
}
exit();
?>