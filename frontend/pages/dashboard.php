<?php
// ============================================================
//  dashboard.php — Dashboard Router
//  Checks what role the user has and loads the correct dashboard.
//  Also handles any pre-output redirects (deletes that need
//  to fire before HTML is sent to the browser).
// ============================================================

session_start();

// Prevent the browser from caching this page. Without this, navigating back
// to the dashboard (e.g. after adding a user) can show a stale cached copy
// that doesn't include the new record yet, making it look like the database
// wasn't updated when it actually was.
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

require '../../backend/config/db.php';
require '../../backend/config/helpers.php';

// If not logged in, redirect to login page
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id   = $_SESSION['user_id'];
$role      = $_SESSION['role'];
$full_name = $_SESSION['full_name'];

// ============================================================
//  PRE-HTML REDIRECT HANDLING
//  These must run BEFORE include header.php outputs any HTML,
//  otherwise header() will fail ("headers already sent").
//  Lecturer delete-link and delete-announcement are handled here.
// ============================================================

if ($role == 'lecturer' && isset($_GET['delete_link'])) {
    $del_id = (int)$_GET['delete_link'];
    mysqli_query($conn, "DELETE FROM class_links WHERE id=$del_id AND lecturer_id=$user_id");
    header("Location: dashboard.php#classlinks");
    exit();
}

if ($role == 'lecturer' && isset($_GET['delete_ann'])) {
    $del_id = (int)$_GET['delete_ann'];
    mysqli_query($conn, "DELETE FROM announcements WHERE id=$del_id AND posted_by=$user_id");
    header("Location: dashboard.php#announcements");
    exit();
}

// Receptionist: unlink a parent from a student
if ($role == 'receptionist' && isset($_GET['unlink_id'])) {
    $ul_id = (int)$_GET['unlink_id'];
    if ($ul_id > 0) mysqli_query($conn, "DELETE FROM parent_student WHERE id=$ul_id");
    header("Location: dashboard.php#link_parent");
    exit();
}

// Admin: unlink a parent from a student
if ($role == 'admin' && isset($_GET['unlink_id'])) {
    $ul_id = (int)$_GET['unlink_id'];
    if ($ul_id > 0) mysqli_query($conn, "DELETE FROM parent_student WHERE id=$ul_id");
    header("Location: dashboard.php#link_parent");
    exit();
}

// ============================================================
//  OUTPUT HTML
//  Now it is safe to send HTML to the browser.
// ============================================================
$page_title  = "Dashboard";
$css_path    = "../../frontend/assets/css/style.css";
$root_path   = "../../";
$active_page = "";
include '../../frontend/assets/header.php';

// Load the correct dashboard file based on the user's role
$valid_roles = ['admin', 'manager', 'director', 'lecturer', 'receptionist', 'student', 'parent'];

if (in_array($role, $valid_roles)) {
    include "../../frontend/pages/dashboards/{$role}_dash.php";
} else {
    echo '<div class="section"><p>Unknown role. Please contact the admin.</p></div>';
}

mysqli_close($conn);
include '../../frontend/assets/footer.php';
