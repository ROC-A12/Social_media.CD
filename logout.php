<?php
require_once 'includes/functies.php';

// Sessie vernietigen
session_destroy();

// Redirect naar login
header("Location: login.php");
exit();
?>