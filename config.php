<?php
if ($_SERVER['HTTP_HOST'] === 'localhost' || $_SERVER['HTTP_HOST'] === 'localhost:8080') {
    define('DB_HOST', 'localhost');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_NAME', 'study_vault');
} else {
    define('DB_HOST', 'sql100.infinityfree.com');
    define('DB_USER', 'if0_41742202');
    define('DB_PASS', 'murali1326');
    define('DB_NAME', 'if0_41742202_study_vault');
}
?>