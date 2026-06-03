<?php
/* config.php will get referenced on most pages to apply these settings in those files */

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
ini_set('display_errors', 0);
error_reporting(0);

define('SITE_NAME', 'MyMarket-ZA');
define('SITE_URL', 'http://localhost/MyMarket-ZA'); /* have to change this to my actual link, this is just for localhost testing */
define('DB_HOST', 'localhost'); /* might have to remove this... but not sure, we'll see I guess */
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'mymarket_za');
?>