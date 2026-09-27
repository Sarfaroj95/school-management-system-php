<?php
/**
 * Live Database SQL Export Utility
 * School Management System
 * 
 * Generates and downloads a complete SQL dump of the live database on demand.
 */
include "../connection.php";

$root_path = '../';
include "../includes/auth.php";
require_role(['Super Admin', 'Admin']);

$db_name = DB_NAME;
$date_stamp = date('Y-m-d_H-i-s');
$filename = "school_db_backup_" . $date_stamp . ".sql";

// Set download headers
header('Content-Type: application/sql');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

echo "-- ==========================================================\n";
echo "-- School Management System (SMS) Database Backup Export\n";
echo "-- Database: `{$db_name}`\n";
echo "-- Generation Date: " . date('Y-m-d H:i:s') . "\n";
echo "-- Server: " . htmlspecialchars(DB_HOST) . "\n";
echo "-- Exported By: " . htmlspecialchars($_SESSION['full_name'] ?? 'Admin') . " (" . htmlspecialchars($_SESSION['username'] ?? 'admin') . ")\n";
echo "-- ==========================================================\n\n";

echo "CREATE DATABASE IF NOT EXISTS `{$db_name}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\n";
echo "USE `{$db_name}`;\n\n";
echo "SET FOREIGN_KEY_CHECKS = 0;\n";
echo "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n";
echo "SET time_zone = '+00:00';\n\n";

// Get list of all tables
$tables = [];
$result = mysqli_query($conn, "SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'");
while ($row = mysqli_fetch_row($result)) {
    $tables[] = $row[0];
}

foreach ($tables as $table) {
    echo "-- --------------------------------------------------------\n";
    echo "-- Table structure for table `{$table}`\n";
    echo "-- --------------------------------------------------------\n";
    echo "DROP TABLE IF EXISTS `{$table}`;\n";

    $create_res = mysqli_query($conn, "SHOW CREATE TABLE `{$table}`");
    $create_row = mysqli_fetch_row($create_res);
    echo $create_row[1] . ";\n\n";

    // Dump Table Data
    $data_res = mysqli_query($conn, "SELECT * FROM `{$table}`");
    $num_rows = mysqli_num_rows($data_res);

    if ($num_rows > 0) {
        echo "-- Dumping data for table `{$table}`\n";
        
        $fields_cnt = mysqli_num_fields($data_res);
        $fields_info = mysqli_fetch_fields($data_res);
        
        $field_names = [];
        foreach ($fields_info as $f) {
            $field_names[] = "`" . $f->name . "`";
        }
        
        echo "INSERT INTO `{$table}` (" . implode(', ', $field_names) . ") VALUES\n";

        $rows = [];
        while ($row = mysqli_fetch_row($data_res)) {
            $values = [];
            for ($j = 0; $j < $fields_cnt; $j++) {
                if (is_null($row[$j])) {
                    $values[] = "NULL";
                } elseif (is_numeric($row[$j]) && $fields_info[$j]->type !== MYSQLI_TYPE_STRING && $fields_info[$j]->type !== MYSQLI_TYPE_VAR_STRING) {
                    $values[] = $row[$j];
                } else {
                    $values[] = "'" . mysqli_real_escape_string($conn, $row[$j]) . "'";
                }
            }
            $rows[] = "(" . implode(', ', $values) . ")";
        }

        echo implode(",\n", $rows) . ";\n\n";
    }
}

echo "SET FOREIGN_KEY_CHECKS = 1;\n";
echo "-- ==========================================================\n";
echo "-- End of SQL Backup Dump\n";
echo "-- ==========================================================\n";
exit();
?>
