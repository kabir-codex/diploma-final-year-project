<?php
// ============================================================
//  db.php — Database Connection
// //  This file connects PHP to the MySQL database.
//  Every other PHP file will include this file first. (using include $conn)
// ============================================================

// Connect to MySQL
// Parameters: server, username, password, database name
$conn = mysqli_connect("localhost", "root", "", "activate_academy_db");

// If connection fails, stop and show the error
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}
