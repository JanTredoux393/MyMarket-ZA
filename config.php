<?php
// Site-wide configuration
// Include this on any page that needs these settings

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
ini_set('display_errors', 0);
error_reporting(0);

define('SITE_NAME', 'MyMarket-ZA');
define('SITE_URL', 'http://localhost/MyMarket-ZA'); // Change this to your actual URL
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'mymarket_za');
?>