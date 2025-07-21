<?php
require_once 'db_connection.php';
require_once 'nav_template.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ordering - Inventory Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
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
    </style>
</head>
<body>
    <div class="container content-container">
        <h1 class="mb-4">Ordering System</h1>
        <div class="card-grid">
            <a href="reorder_list.php" class="text-decoration-none">
                <div class="menu-card">
                    <div class="card-body">
                        <i class="bi bi-cart-check card-icon"></i>
                        <h2 class="card-title">Reorder List</h2>
                        <p class="card-text">View and manage items that need reordering</p>
                    </div>
                </div>
            </a>
            
            <a href="order_history.php" class="text-decoration-none">
                <div class="menu-card">
                    <div class="card-body">
                        <i class="bi bi-clock-history card-icon"></i>
                        <h2 class="card-title">Order History</h2>
                        <p class="card-text">View complete history of all orders</p>
                    </div>
                </div>
            </a>

            <a href="receiving.php" class="text-decoration-none">
                <div class="menu-card">
                    <div class="card-body">
                        <i class="bi bi-box-seam card-icon"></i>
                        <h2 class="card-title">Receiving Orders</h2>
                        <p class="card-text">Process and receive incoming shipments</p>
                    </div>
                </div>
            </a>

            <a href="po_numbers.php" class="text-decoration-none">
                <div class="menu-card">
                    <div class="card-body">
                        <i class="bi bi-journal-plus card-icon"></i>
                        <h2 class="card-title">Create PO</h2>
                        <p class="card-text">Create a new Purchase Order</p>
                    </div>
                </div>
            </a>

            <a href="po_history.php" class="text-decoration-none">
                <div class="menu-card">
                    <div class="card-body">
                        <i class="bi bi-list-ol card-icon"></i>
                        <h2 class="card-title">PO History</h2>
                        <p class="card-text">View history of all Purchase Orders</p>
                    </div>
                </div>
            </a>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 