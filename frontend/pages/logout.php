<?php
// ============================================================
//  logout.php — Logs the user out
//  Clears the session and sends the user back to the home page
// ============================================================

session_start();
session_unset();    // Remove all session variables
session_destroy();  // Destroy the session completely
header("Location: ../../index.php");
exit();
?>
