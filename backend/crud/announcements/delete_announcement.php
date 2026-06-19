<?php
// delete_announcement.php
session_start();
require '../../config/db.php';
if (!isset($_SESSION['user_id'])) { header("Location: ../../../frontend/pages/login.php"); exit(); }
$id = (int)($_GET['id'] ?? 0);
if ($id > 0) mysqli_query($conn, "DELETE FROM announcements WHERE id=$id");
header("Location: ../../../frontend/pages/dashboard.php?msg=Announcement+deleted");
exit();
