<?php
// Start sessie als deze nog niet gestart is
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'functies.php';

// Alleen redirecten als niet op login/register pagina
$current_page = basename($_SERVER['PHP_SELF']);
$excluded_pages = ['login.php', 'register.php'];

if (!isset($_SESSION['user_id']) && !in_array($current_page, $excluded_pages)) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle : 'Social Media Platform'; ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/style.css">
    
    <!-- CSRF Token voor formulieren -->
    <meta name="csrf-token" content="<?php echo generateCSRFToken(); ?>">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-users me-2"></i>Social Platform
            </a>
            
            <?php if(isset($_SESSION['user_id'])): ?>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="index.php">
                    <i class="fas fa-home"></i> Home
                </a>
                <a class="nav-link" href="profile.php?id=<?php echo $_SESSION['user_id']; ?>">
                    <i class="fas fa-user"></i> Profiel
                </a>
                <a class="nav-link" href="search.php">
                    <i class="fas fa-search"></i> Zoeken
                </a>
                <?php if(isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
                <a class="nav-link" href="admin/dashboard.php">
                    <i class="fas fa-cog"></i> Admin
                </a>
                <?php endif; ?>
                <a class="nav-link" href="logout.php">
                    <i class="fas fa-sign-out-alt"></i> Uitloggen
                </a>
            </div>
            <?php endif; ?>
        </div>
    </nav>