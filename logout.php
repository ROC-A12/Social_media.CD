<?php
require_once 'includes/config.php';

// Sessie vernietigen
session_destroy();

// Redirect naar login
header("Location: login.php");
exit();
?>