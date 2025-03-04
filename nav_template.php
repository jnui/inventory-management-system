<?php
// This file contains the navigation bar template to be included in all pages
// It assumes that auth_check.php has already been included and session is started
?>
<!-- Navigation Bar -->
<div class="navigation-bar">
    <a href="index.php" class="back-button">
        <i class="bi bi-chevron-left"></i>
    </a>
    <h1 class="page-title"><?php echo $page_title ?? 'Inventory Management'; ?></h1>
    <div class="d-flex align-items-center">
        <span class="me-4"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
        <a href="index.php" class="home-button me-4">
            <i class="bi bi-house-fill"></i>
        </a>
        <a href="manual_index.html" class="help-button me-4" title="Help & Manuals">
            <i class="bi bi-question-circle-fill"></i>
        </a>
        <a href="logout.php" class="logout-button">
            <i class="bi bi-box-arrow-right"></i>
        </a>
    </div>
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