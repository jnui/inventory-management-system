<?php
// Include authentication check
require_once 'auth_check.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
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
        .content-container {
            padding-top: 80px;
        }
        .card-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .menu-card {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            height: 100%;
        }
        .menu-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
        }
        .card-body {
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            height: 100%;
        }
        .card-icon {
            font-size: 3rem;
            margin-bottom: 15px;
            color: #0d6efd;
        }
        .card-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 10px;
        }
        .card-text {
            color: #6c757d;
        }
        .user-info {
            display: flex;
            align-items: center;
        }
        .logout-button {
            color: #dc3545;
            text-decoration: none;
            margin-left: 15px;
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <div class="navigation-bar">
        <h1 class="page-title">Inventory Management</h1>
        <div class="user-info">
            <span><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
            <a href="logout.php" class="logout-button">
                <i class="bi bi-box-arrow-right"></i>
            </a>
        </div>
    </div>

    <div class="container content-container">
        <div class="card-grid">
            <a href="inventory.php" class="text-decoration-none">
                <div class="menu-card">
                    <div class="card-body">
                        <i class="bi bi-arrow-down-up card-icon"></i>
                        <h2 class="card-title">Update Stock</h2>
                        <p class="card-text">Add or remove stock from inventory</p>
                    </div>
                </div>
            </a>
            
            <a href="consumable_list.php" class="text-decoration-none">
                <div class="menu-card">
                    <div class="card-body">
                        <i class="bi bi-box-seam card-icon"></i>
                        <h2 class="card-title">Consumable Materials</h2>
                        <p class="card-text">View and manage consumable materials inventory</p>
                    </div>
                </div>
            </a>
            
            <a href="location_list.php" class="text-decoration-none">
                <div class="menu-card">
                    <div class="card-body">
                        <i class="bi bi-geo-alt card-icon"></i>
                        <h2 class="card-title">Locations</h2>
                        <p class="card-text">Manage inventory storage locations</p>
                    </div>
                </div>
            </a>
            
            <a href="employee_list.php" class="text-decoration-none">
                <div class="menu-card">
                    <div class="card-body">
                        <i class="bi bi-person-badge card-icon"></i>
                        <h2 class="card-title">Employees</h2>
                        <p class="card-text">Manage employee information</p>
                    </div>
                </div>
            </a>
            
            <?php if (is_admin()): ?>
            <a href="user_list.php" class="text-decoration-none">
                <div class="menu-card">
                    <div class="card-body">
                        <i class="bi bi-people card-icon"></i>
                        <h2 class="card-title">User Management</h2>
                        <p class="card-text">Add, edit, and manage system users</p>
                    </div>
                </div>
            </a>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>