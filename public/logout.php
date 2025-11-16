<?php
/**
 * Logout Page
 * Destroys session and redirects to login
 */
session_start();
session_destroy();
header('Location: login.php');
exit;
