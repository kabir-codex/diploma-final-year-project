<?php
// ============================================================
//  helpers.php — Shared Helper Functions
//  These small functions are used by many files to avoid
//  repeating the same code everywhere.
// ============================================================

// Count how many rows are in a table (with an optional condition)
// Example: count_rows($conn, 'users', "role='student'") → returns number

function count_rows($conn, $table, $condition = '') {
    $sql = "SELECT COUNT(*) AS total FROM $table";          // Base query: count all rows in the given table
    
    if ($condition != '') {                     // If a condition was passed in, add a WHERE clause
        $sql .= " WHERE $condition";
    }
    $result = mysqli_query($conn, $sql);               // Run the final SQL query using the shared connection $conn
    $row    = mysqli_fetch_assoc($result);            // Pull the single result row out (it only ever has one row: the count) returns e.g. ['total' => 42
    return (int) $row['total'];                          // Convert to a real PHP integer (it comes back from MySQL as a string) and return it
}

// Run a query and get the first row back
// Useful when you expect just one result (e.g. finding a user by ID)
function get_one_row($conn, $sql) {
    $result = mysqli_query($conn, $sql);              // Run whatever SQL string was passed in
    if ($result && mysqli_num_rows($result) > 0) {       // Check: did the query succeed AND did it return at least 1 row?
        return mysqli_fetch_assoc($result);           // Return that first row as an associative array, e.g. ['id'=>3,'name'=>'Sam']
    }
        // Otherwise return null so calling code can check "if user not found"
    return null; // Returns nothing if no row found
}

// Check if a user is logged in
// If not logged in, redirect them to the login page
function require_login() {
    if (!isset($_SESSION['user_id'])) {                                     // isset() checks if $_SESSION['user_id'] exists at all
        header("Location: /activate_academy/frontend/pages/login.php");      // header("Location: ...") tells the BROWSER to redirect to the login page
        exit();                                                             // exit() stops the script immediately
    }
}

// Check if the logged-in user has one of the allowed roles
// Example: require_role(['admin', 'manager'])
function require_role($allowed_roles) {
    require_login();                                             // Step 1: must be logged in at all (reuses Function 3 above)
    if (!in_array($_SESSION['role'], $allowed_roles)) {              // Step 2: in_array() checks if the current user's role
                                                                      // is inside the list of roles that are allowed to see this page (login page)
                                                       
        header("Location: /activate_academy/frontend/pages/login.php");
        exit();
    }
    // If we get here, user is logged in AND has permission
}

// Show a success message box (green)
function success_msg($text) {
    return "<div style='background:#dcfce7; color:#166534; padding:12px; border-radius:8px; margin-bottom:16px;'>✅ $text</div>";      // Just returns a ready-made HTML <div> string with inline CSS styling
                                                                                                                                       // $text gets inserted directly into the string
}


// Show an error message box (red)
function error_msg($text) {
    return "<div style='background:#fee2e2; color:#991b1b; padding:12px; border-radius:8px; margin-bottom:16px;'>❌ $text</div>";
} 
//echo success_msg("User added successfully!"); after a form submits



// Calculate the letter grade for a mark out of 100
// Mirrors the client-side JS version used on result forms
function calc_grade($marks) {
    $marks = (int) $marks;              // force it to a whole number, just in case

    // Checked top-down: first condition that matches "wins" and returns immediately
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
    return 'E';                     // anything below 25 falls through to here
}

// Clean and escape user input to prevent SQL injection
//This is the security wrapper — you'll see clean($conn, $_POST['username']) etc. all over the CRUD files.
function clean($conn, $value) {
    // trim() removes extra spaces from the start/end (e.g. "  John " → "John")
    // mysqli_real_escape_string() escapes dangerous characters like quotes ( ' )
    // so they can't break out of the SQL query and run malicious commands
    return mysqli_real_escape_string($conn, trim($value));
}

// ============================================================
//  SHARED VALIDATION HELPERS
//  Used by every form that collects an email/phone (Admin's Add/Edit
//  User, and the Receptionist's Register Student form) so the rule is
//  defined once and can't drift between the two portals.
// ============================================================

// A phone number must be EXACTLY 10 digits — no letters, no symbols,
// no spaces, and not fewer/more than 10 digits.
function is_valid_phone($phone) {
    return (bool) preg_match('/^[0-9]{10}$/', trim($phone));
}

// Basic, reliable email format check using PHP's built-in filter.
function is_valid_email($email) {
    return filter_var(trim($email), FILTER_VALIDATE_EMAIL) !== false;
}

// ============================================================
//  ROLE -> SUBTYPE TABLE MAP
//  Every role has its own "subtype" table (student, lecturer, parent,
//  receptionist, manager, admin, director) holding extra fields for
//  that role. Each subtype table's own primary key is also a FOREIGN
//  KEY back to users.userID (same value, strict 1:1).
//
//  Both add_user.php (creating a user) and edit_user.php (changing an
//  existing user's role) need this same map, so it lives here once.
// ============================================================
function subtype_table_map() {
    return [
        'student'      => ['table' => 'student',      'pk' => 'studentID'],
        'lecturer'     => ['table' => 'lecturer',      'pk' => 'lecturerID'],
        'parent'       => ['table' => 'parent',        'pk' => 'parentID'],
        'receptionist' => ['table' => 'receptionist',  'pk' => 'receptionistID'],
        'manager'      => ['table' => 'manager',       'pk' => 'managerID'],
        'admin'        => ['table' => 'admin',         'pk' => 'adminID'],
        'director'     => ['table' => 'director',      'pk' => 'directorID'],
    ];
}

// Make sure a user has a matching row in their role's subtype table.
// Safe to call even if the row already exists (uses INSERT IGNORE).
// This is the fix for a real bug: a user whose role is changed via
// Edit User previously never got a subtype row created for the new
// role, which later caused foreign key errors when that user was
// referenced as a student in enrollments/payments, or as a parent in
// parent_student links.
function ensure_subtype_row($conn, $user_id, $role) {
    $map = subtype_table_map();
    if (!isset($map[$role])) {
        return true; // Unknown/no-subtype role — nothing to do.
    }
    $table = $map[$role]['table'];
    $pk    = $map[$role]['pk'];
    return mysqli_query($conn, "INSERT IGNORE INTO $table ($pk) VALUES (" . (int)$user_id . ")");
}
