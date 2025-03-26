<?php
require_once 'db_connection.php';
require_once 'nav_template.php';

// Get all order history with item details
$stmt = $pdo->query("
    SELECT 
        oh.*,
        c.item_name,
        os.status_name,
        os.description as status_description
    FROM order_history oh
    JOIN consumable_materials c ON oh.consumable_id = c.id
    JOIN order_status os ON oh.status_id = os.id
    ORDER BY oh.ordered_at DESC
");
$orders = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order History - Inventory Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <style>
        .status-badge {
            font-size: 0.875rem;
            padding: 0.25rem 0.5rem;
        }
        .status-not-ordered {
            background-color: #dc3545;
            color: white;
        }
        .status-ordered {
            background-color: #ffc107;
            color: black;
        }
        .status-backordered {
            background-color: #6c757d;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Order History</h1>
            <a href="ordering.php" class="btn btn-outline-primary">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-cart-check" viewBox="0 0 16 16">
                    <path d="M.5 1a.5.5 0 0 0 0 1h1.11l.401 1.607 1.498 5.985A.5.5 0 0 0 4 9h1a2 2 0 1 0 0 4 2 2 0 0 0 0-4h7a2 2 0 1 0 0 4 2 2 0 0 0 0-4h1a.5.5 0 0 0 .491-.408l1.5-6A.5.5 0 0 0 14.5 3H2.89l-.405-1.621A.5.5 0 0 0 2 1H.5zM6 14a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm7 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0zM9 5.5V7h1.5a.5.5 0 0 1 0 1H9v1.5a.5.5 0 0 1-1 0V8H6.5a.5.5 0 0 1 0-1H8V5.5a.5.5 0 0 1 1 0z"/>
                </svg>
                Back to Ordering
            </a>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="orderHistoryTable" class="table table-striped">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Item Name</th>
                                <th>Status</th>
                                <th>Quantity Ordered</th>
                                <th>Notes</th>
                                <th>Ordered By</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><?= date('Y-m-d H:i', strtotime($order['ordered_at'])) ?></td>
                                <td><?= htmlspecialchars($order['item_name']) ?></td>
                                <td>
                                    <?php
                                    $statusClass = match($order['status_name']) {
                                        'Not Ordered' => 'status-not-ordered',
                                        'Ordered & Waiting' => 'status-ordered',
                                        'Backordered' => 'status-backordered',
                                        default => ''
                                    };
                                    ?>
                                    <span class="badge status-badge <?= $statusClass ?>">
                                        <?= htmlspecialchars($order['status_name']) ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($order['quantity_ordered']) ?></td>
                                <td><?= htmlspecialchars($order['notes']) ?></td>
                                <td><?= htmlspecialchars($order['ordered_by']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize DataTable
            $('#orderHistoryTable').DataTable({
                scrollCollapse: true,
                scrollX: true,
                paging: true,
                ordering: true,
                info: true,
                responsive: true,
                autoWidth: false,
                order: [[0, 'desc']], // Sort by date descending
                pageLength: 25
            });
        });
    </script>
</body>
</html> 