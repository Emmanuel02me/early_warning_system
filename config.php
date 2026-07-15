<?php
// Database configuration
define('DB_HOST', 'sql111.infinityfree.com');
define('DB_NAME', 'if0_42416548_school_early_warning');
define('DB_USER', 'if0_42416548');
define('DB_PASS', 'BgFksLXT0Y4igX0');

// Python API configuration
define('ML_API_URL', 'https://early-warning-system-mpxj.onrender.com/api/predict');
define('ML_TRAIN_URL', 'http://127.0.0.1:5000/api/train');
// System settings
define('SCHOOL_NAME', 'Mbeya Secondary School');
define('ATTENDANCE_THRESHOLD', 75); // percentage
define('SCORE_THRESHOLD', 50); // percentage

// FIX: Added current academic year as a central constant.
// Previously, 'initial_training.py' used hardcoded '2025', while
// 'seed_historical_data.py' inserted records as '2026', and 'api_server.py'
// used YEAR(CURDATE()). This mismatch caused the model to train on data it
// could never find at prediction time. All files now reference this constant.
define('ACADEMIC_YEAR', date('Y'));

// Database connection
function getDBConnection() {
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
            DB_USER,
            DB_PASS,
            array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8")
        );
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch(PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }
}
?>
