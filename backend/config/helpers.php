<?php
// ============================================================
//  helpers.php — Shared Helper Functions
//  These small functions are used by many files to avoid
//  repeating the same code everywhere.
// ============================================================

// Count how many rows are in a table (with an optional condition)
// Example: count_rows($conn, 'users', "role='student'") → returns number
function count_rows($conn, $table, $condition = '') {
    $sql = "SELECT COUNT(*) AS total FROM $table";
    if ($condition != '') {
        $sql .= " WHERE $condition";
    }
    $result = mysqli_query($conn, $sql);
    $row    = mysqli_fetch_assoc($result);
    return (int) $row['total'];
}

// Run a query and get the first row back
// Useful when you expect just one result (e.g. finding a user by ID)
function get_one_row($conn, $sql) {
    $result = mysqli_query($conn, $sql);
    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_assoc($result);
    }
    return null; // Returns nothing if no row found
}

// Check if a user is logged in
// If not logged in, redirect them to the login page
function require_login() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: /activate_academy/frontend/pages/login.php");
        exit();
    }
}

// Check if the logged-in user has one of the allowed roles
// Example: require_role(['admin', 'manager'])
function require_role($allowed_roles) {
    require_login();
    if (!in_array($_SESSION['role'], $allowed_roles)) {
        header("Location: /activate_academy/frontend/pages/login.php");
        exit();
    }
}

// Show a success message box (green)
function success_msg($text) {
    return "<div style='background:#dcfce7; color:#166534; padding:12px; border-radius:8px; margin-bottom:16px;'>✅ $text</div>";
}

// Show an error message box (red)
function error_msg($text) {
    return "<div style='background:#fee2e2; color:#991b1b; padding:12px; border-radius:8px; margin-bottom:16px;'>❌ $text</div>";
}

// Calculate the letter grade for a mark out of 100, using the academy's grading scale.
// Mirrors the client-side aaCalcGrade() JS function used on the result forms.
function calc_grade($marks) {
    $marks = (int) $marks;
    if ($marks >= 85) return 'A+';
    if ($marks >= 70) return 'A';
    if ($marks >= 65) return 'A-';
    if ($marks >= 60) return 'B+';
    if ($marks >= 55) return 'B';
    if ($marks >= 50) return 'B-';
    if ($marks >= 45) return 'C+';
    if ($marks >= 40) return 'C';
    if ($marks >= 35) return 'C-';
    if ($marks >= 30) return 'D+';
    if ($marks >= 25) return 'D';
    return 'E';
}

// Clean and escape user input to prevent SQL injection
// Always use this before putting user data into a query
function clean($conn, $value) {
    return mysqli_real_escape_string($conn, trim($value));
}
