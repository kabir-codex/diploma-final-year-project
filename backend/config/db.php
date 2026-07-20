<?php
// ============================================================
//  db.php — Database Connection
// //  This file connects PHP to the MySQL database.
//  Every other PHP file will include this file first. (using include $conn)
// ============================================================

// Make mysqli return false on error (like PHP < 8.1 did) instead of
// throwing an uncaught mysqli_sql_exception. The whole app is written in
// the "if ($result) { ... } else { ...show a friendly error... }" style
// (see add_user.php's transaction rollback, for example), which depends
// on failed queries returning false rather than throwing. Without this,
// on PHP 8.1+ a single bad insert (e.g. a foreign key violation) crashes
// the entire page with a fatal error instead of the intended graceful
// error message.
mysqli_report(MYSQLI_REPORT_OFF);

// Connect to MySQL
// Parameters: server, username, password, database name
$conn = mysqli_connect("localhost", "root", "", "activate_academy_db");

// If connection fails, stop and show the error
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}
