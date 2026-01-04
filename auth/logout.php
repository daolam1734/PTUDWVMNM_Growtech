<?php
if (session_status() == PHP_SESSION_NONE) session_start();
// Clear session and remember cookie
session_unset();
session_destroy();
setcookie('weblaptop_remember', '', time() - 3600, '/', '', false, true);
header('Location: /weblaptop');
exit;