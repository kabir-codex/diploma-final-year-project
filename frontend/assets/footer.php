<!-- Footer shown at the bottom of every page -->
<footer>
    <strong>Activate Academy</strong>
    <p>123 Education Lane, Colombo, Sri Lanka &nbsp;|&nbsp; Tel: +94 11 234 5678 &nbsp;|&nbsp; info@activateacademy.lk</p>
    <p style="margin-top:10px; font-size:0.8rem;">&copy; <?php echo date('Y'); ?> Activate Academy. PHP &amp; MySQL Project.</p>
</footer>

<?php
// $root_path is set by each page (same variable used for nav links and CSS).
// Fall back to an empty string if a page forgot to set it, so the include never breaks.
$root_path = $root_path ?? '';
?>
