<?php
// logout.php

// Ensure session is started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Destroy the session
session_unset();
session_destroy();

// Redirect to login page
header("Location: login.php");
exit();
?>