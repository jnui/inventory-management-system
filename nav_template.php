<?php
// This file contains the navigation bar template to be included in all pages
// It assumes that auth_check.php has already been included and session is started
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="assets/img/favicon.svg">
    <link rel="alternate icon" type="image/x-icon" href="assets/img/favicon.ico">
    <link rel="mask-icon" href="assets/img/favicon.svg" color="#0d6efd">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) . ' - ' : ''; ?>Inventory Management</title>
</head>
<body>
<!-- Navigation Bar -->
<div class="navigation-bar">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-box me-2"></i>Inventory Management
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="consumable_list.php">Consumables</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="inventory.php">Inventory</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
</div>

<style>
    /* Navigation bar styles */
    .navigation-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        width: 100%;
        padding: 10px 15px;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        z-index: 1000;
        background-color: #f8f9fa;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        height: 60px;
    }
    
    /* Ensure content doesn't get hidden under the navigation bar */
    .content-container {
        padding-top: 80px;
    }
    
    /* Navigation buttons */
    .back-button, .home-button, .help-button, .logout-button {
        color: #212529;
        text-decoration: none;
        font-size: 1.5rem;
    }
    
    .help-button {
        color: #3498db;
    }
    
    .logout-button {
        color: #dc3545;
    }
    
    .page-title {
        font-size: 1.5rem;
        margin: 0;
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 