<?php
require_once 'includes/functies.php';

// Uitloggen: sessie vernietigen en terug naar de inlogpagina
// Zorg dat alle sessiegegevens verwijderd worden
session_destroy();

// Redirect naar login
header("Location: login.php");
exit();
?>