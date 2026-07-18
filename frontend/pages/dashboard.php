<?php
// ============================================================
//  dashboard.php — Dashboard Router
//  Checks what role the user has and loads the correct dashboard.
//  Also handles any pre-output redirects (deletes that need
//  to fire before HTML is sent to the browser).
// ============================================================


// Resume the session started back in login.php — this is how we
// still know who's logged in on a brand new page load
session_start();

// Tell the browser NOT to cache this page.
// Why this matters: if you add a user then click "back" to the dashboard,
// a cached page would show the OLD data even though the database already
// has the new record — this forces a fresh reload every time.

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

// Bring in the DB connection and the helper functions toolbox
require '../../backend/config/db.php';
require '../../backend/config/helpers.php';

// Gatekeeper: if there's no logged-in session, kick them back to login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Pull the logged-in user's info out of the session into simple variables
// (just for shorter/cleaner code below, instead of typing $_SESSION[...] repeatedly)
$user_id   = $_SESSION['user_id'];
$role      = $_SESSION['role'];
$full_name = $_SESSION['full_name'];

// ============================================================
//  PRE-HTML REDIRECT HANDLING
//  These must run BEFORE include header.php outputs any HTML,
//  otherwise header() will fail ("headers already sent").
//  Lecturer delete-link and delete-announcement are handled here.
// ============================================================

// Lecturer clicked "delete" on one of their class links
if ($role == 'lecturer' && isset($_GET['delete_link'])) {
    $del_id = (int)$_GET['delete_link'];          // Cast to (int) — security measure so nothing except a number can be injected

    
    // Delete ONLY if it belongs to this lecturer — lecturer_id=$user_id stops
    // one lecturer from deleting another lecturer's link by guessing an ID in the URL
    mysqli_query($conn, "DELETE FROM class_sessions WHERE classSessionID=$del_id AND lecturer_id=$user_id");
    header("Location: dashboard.php#classlinks");
    exit();
}

// Lecturer clicked "delete" on one of their own announcements — same pattern
if ($role == 'lecturer' && isset($_GET['delete_ann'])) {
    $del_id = (int)$_GET['delete_ann'];
    mysqli_query($conn, "DELETE FROM announcement WHERE announcementID=$del_id AND posted_by=$user_id");
    header("Location: dashboard.php#announcements");
    exit();
}

// Receptionist: unlink a parent from a student
if ($role == 'receptionist' && isset($_GET['unlink_id'])) {
    $ul_id = (int)$_GET['unlink_id'];
    if ($ul_id > 0) mysqli_query($conn, "DELETE FROM parent_student WHERE parentStudentID=$ul_id");
    header("Location: dashboard.php#link_parent");
    exit();
}

// Admin: unlink a parent from a student
if ($role == 'admin' && isset($_GET['unlink_id'])) {
    $ul_id = (int)$_GET['unlink_id'];
    if ($ul_id > 0) mysqli_query($conn, "DELETE FROM parent_student WHERE parentStudentID=$ul_id");
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
$active_page = "";             //no top-nav link should be highlighted while on the dashboard
include '../../frontend/assets/header.php';

// Load the correct dashboard file based on the user's role
$valid_roles = ['admin', 'manager', 'director', 'lecturer', 'receptionist', 'student', 'parent'];

if (in_array($role, $valid_roles)) {

        // Dynamically build a filename using the role's name, e.g. "admin_dash.php"
    // and include whichever one matches this user's role
    include "../../frontend/pages/dashboards/{$role}_dash.php";
} else {
     // Safety fallback in case $role somehow isn't one of the 7 valid roles
    echo '<div class="section"><p>Unknown role. Please contact the admin.</p></div>';
}

mysqli_close($conn);
include '../../frontend/assets/footer.php';
