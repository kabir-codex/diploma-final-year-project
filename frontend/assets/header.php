<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Page title is set by each individual page before including this file -->
    <title><?php echo $page_title; ?> - Activate Academy</title>
    <!-- CSS path is set by each page (different depth = different path) -->
    <?php
    // Cache-bust the stylesheet using its last-modified time, so the browser
    // always fetches the latest CSS right after we deploy a change, instead of
    // serving a stale cached copy that's missing new classes/rules.
    $css_file_path = __DIR__ . '/../../' . ltrim(str_replace($root_path, '', $css_path), '/');
    $css_version   = file_exists($css_file_path) ? filemtime($css_file_path) : time();
    ?>
    <link rel="stylesheet" href="<?php echo $css_path; ?>?v=<?php echo $css_version; ?>">
</head>
<body>

<!-- Navigation Bar -->
<nav>
    <!-- Logo -->
    <a href="<?php echo $root_path; ?>index.php" class="nav-logo">Activate<span> Academy</span></a>

    <!-- Nav Links -->
    <ul class="nav-links">
        <li><a href="<?php echo $root_path; ?>index.php"                <?php if ($active_page == 'home')     echo 'class="active"'; ?>>Home</a></li>
        <li><a href="<?php echo $root_path; ?>frontend/pages/about.php"    <?php if ($active_page == 'about')    echo 'class="active"'; ?>>About</a></li>
        <li><a href="<?php echo $root_path; ?>frontend/pages/courses.php"  <?php if ($active_page == 'courses')  echo 'class="active"'; ?>>Courses</a></li>
        <li><a href="<?php echo $root_path; ?>frontend/pages/feedback.php" <?php if ($active_page == 'feedback') echo 'class="active"'; ?>>Feedback</a></li>

        <?php if (isset($_SESSION['user_id'])): ?>
            <!-- If logged in: show name and logout -->
            <li><a href="<?php echo $root_path; ?>frontend/pages/dashboard.php">👤 <?php echo $_SESSION['full_name']; ?></a></li>
            <li><a href="<?php echo $root_path; ?>frontend/pages/logout.php" class="btn-nav-login">Logout</a></li>
        <?php else: ?>
            <!-- If not logged in: show login button -->
            <li><a href="<?php echo $root_path; ?>frontend/pages/login.php" class="btn-nav-login">Login</a></li>
        <?php endif; ?>
    </ul>
</nav>
