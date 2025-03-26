<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'db_connection.php';

// Debug: Check if we have any consumable materials at all
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM consumable_materials");
    $total_items = $stmt->fetch()['total'];
    echo "<!-- Debug: Total items = $total_items -->";

    // Debug: Check if we have any items below reorder point
    $stmt = $pdo->query("SELECT COUNT(*) as low_stock FROM consumable_materials WHERE whole_quantity < reorder_threshold");
    $low_stock = $stmt->fetch()['low_stock'];
    echo "<!-- Debug: Low stock items = $low_stock -->";

    // Get all consumable materials that need reordering
    $stmt = $pdo->query("
        SELECT 
            cm.*,
            COALESCE(oh.status_id, 1) as order_status_id,
            os.status_name,
            os.description as status_description
        FROM consumable_materials cm
        LEFT JOIN order_history oh ON cm.id = oh.consumable_id 
            AND oh.id = (
                SELECT MAX(id) 
                FROM order_history 
                WHERE consumable_id = cm.id
            )
        LEFT JOIN order_status os ON COALESCE(oh.status_id, 1) = os.id
        WHERE cm.whole_quantity < cm.reorder_threshold
        ORDER BY cm.whole_quantity ASC
    ");
    $items = $stmt->fetchAll();
    echo "<!-- Debug: Items in result set = " . count($items) . " -->";

    // Get all order statuses for the dropdown
    $stmt = $pdo->query("SELECT * FROM order_status ORDER BY id");
    $order_statuses = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reorder List - Inventory Management System</title>
    <!-- jQuery (required for DataTables) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="custom.css" rel="stylesheet">
    <style>
        /* Custom styles for sticky header */
        .dataTables_wrapper .dataTables_scrollHead {
            position: sticky !important;
            z-index: 10 !important;
            background-color: #fff !important;
            margin: 0 !important;
            padding: 0 !important;
        }
        
        /* Fix for header text being cut off */
        .dataTables_scrollHead table {
            margin: 0 !important;
        }
        
        .dataTables_scrollHead table thead th {
            padding: 8px !important;
            vertical-align: middle !important;
            line-height: 1.2 !important;
            height: auto !important;
            min-height: 40px !important;
            background-color: #fff !important;
        }
        
        /* Additional styling for DataTables */
        #reorderTable_wrapper {
            margin: 0 !important;
            padding: 0 !important;
            width: 100% !important;
        }
        
        /* Fix for first row being covered */
        .dataTables_wrapper .dataTables_scrollBody {
            padding-top: 0 !important;
            margin-top: 0 !important;
        }
        
        /* Action row adjustments */
        .action-row {
            display: flex !important;
            flex-direction: row !important;
            flex-wrap: nowrap !important;
            gap: 8px !important;
            margin-bottom: 10px !important;
            align-items: center !important;
            width: 100% !important;
            justify-content: space-between !important;
            padding: 0 5px !important;
        }
        
        .button-group {
            display: flex !important;
            gap: 8px !important;
            flex-shrink: 0 !important;
            align-items: center !important;
        }
        
        .left-buttons {
            margin-right: 8px !important;
        }
        
        .right-buttons {
            display: flex !important;
            gap: 8px !important;
            margin-left: 8px !important;
        }
        
        .search-container {
            flex: 1 !important;
            min-width: 150px !important;
            max-width: none !important;
            margin: 0 8px !important;
        }
        
        /* Ensure buttons don't wrap or shrink */
        .btn {
            flex-shrink: 0 !important;
            white-space: nowrap !important;
        }
        
        /* Status badge styles */
        .status-badge {
            font-size: 0.875rem;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
        }
        .status-1 { background-color: #ffc107; color: #000; } /* Not Ordered - Yellow */
        .status-2 { background-color: #17a2b8; color: #fff; } /* Ordered & Waiting - Blue */
        .status-3 { background-color: #dc3545; color: #fff; } /* Backordered - Red */
        .status-4 { background-color: #28a745; color: #fff; } /* Complete - Green */
        .quantity-warning { color: #dc3545; }
        .quantity-critical { color: #dc3545; font-weight: bold; }
        
        /* Smaller button styles */
        .btn-sm {
            padding: 0.15rem 0.3rem !important;
            font-size: 0.65rem !important;
        }
        
        /* Action buttons cell */
        .action-buttons-cell {
            display: flex;
            gap: 4px;
        }
        
        /* Mobile-specific styles */
        @media (max-width: 767px) {
            .footer-wrapper {
                padding: 5px 5px !important;
            }
            
            .dataTables_info {
                padding: 5px 10px !important;
                font-size: 0.9em !important;
            }
            
            .content-container {
                padding: 5px !important;
            }
            
            .action-row {
                flex-direction: row !important;
                flex-wrap: nowrap !important;
                gap: 5px !important;
                margin-bottom: 5px !important;
                justify-content: space-between !important;
                width: 100% !important;
            }
            
            .search-container {
                flex: 1 !important;
                min-width: 0 !important;
                margin: 0 5px !important;
                order: 0 !important;
            }
            
            .search-container input {
                padding: 4px 8px !important;
                font-size: 14px !important;
                height: 32px !important;
            }
            
            /* Group non-search elements */
            .button-group {
                display: flex !important;
                gap: 5px !important;
                flex-shrink: 0 !important;
            }
            
            /* Make buttons more compact on mobile */
            .btn-sm {
                padding: 0.1rem 0.2rem !important;
                font-size: 0.6rem !important;
            }
            
            /* Hide button text on mobile */
            .btn-mobile-icon .btn-text {
                display: none !important;
            }
            
            /* Make buttons square and compact */
            .btn-mobile-icon {
                width: 28px !important;
                height: 28px !important;
                padding: 2px !important;
            }
            
            /* Adjust icon size */
            .btn-mobile-icon i {
                font-size: 0.9rem !important;
            }
        }
        
        /* Ensure content doesn't get hidden under the navigation bar */
        .content-container {
            width: 100% !important;
            overflow-x: visible !important;
            padding-top: 60px !important; /* Match navbar height */
            padding-bottom: 60px !important;
        }
        
        /* Footer wrapper styles */
        .footer-wrapper {
            position: fixed !important;
            bottom: 0 !important;
            left: 0 !important;
            right: 0 !important;
            background-color: #f8f9fa !important;
            border-top: 1px solid #dee2e6 !important;
            z-index: 1000 !important;
            padding: 0px 5px !important;
        }
    </style>
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
                            <a class="nav-link active" href="reorder_list.php">Reorder List</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="inventory.php">Inventory</a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
    </div>

    <div class="container content-container">
        <?php if (empty($items)): ?>
            <div class="alert alert-info">
                No items currently need reordering. All items are above their reorder points.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table id="reorderTable" class="table table-striped">
                    <thead>
                        <tr>
                            <th>Item Name</th>
                            <th>Current Quantity</th>
                            <th>Reorder Point</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                        <tr>
                            <td>
                                <a href="consumable_entry.php?id=<?php echo $item['id']; ?>" class="text-decoration-none">
                                    <?php echo htmlspecialchars($item['item_name'] ?? ''); ?>
                                </a>
                            </td>
                            <td class="<?php echo $item['whole_quantity'] <= $item['reorder_threshold'] ? 'quantity-warning' : ''; ?>">
                                <?php echo $item['whole_quantity']; ?>
                            </td>
                            <td><?php echo $item['reorder_threshold']; ?></td>
                            <td>
                                <span class="status-badge status-<?php echo $item['order_status_id']; ?>">
                                    <?php echo htmlspecialchars($item['status_name'] ?? ''); ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons-cell">
                                    <button type="button" class="btn btn-sm btn-outline-primary btn-mobile-icon" 
                                            onclick="showOrderHistory(<?php echo $item['id']; ?>)">
                                        <i class="bi bi-clock-history"></i><span class="btn-text">History</span>
                                    </button>
                                    <a href="inventory_entry.php?consumable_id=<?php echo $item['id']; ?>" 
                                       class="btn btn-sm btn-outline-secondary btn-mobile-icon">
                                        <i class="bi bi-box-arrow-in-down"></i><span class="btn-text">Stock</span>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- DataTables Core JS -->
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize DataTables
        var table = $('#reorderTable').DataTable({
            scrollCollapse: true,
            scrollX: true,
            paging: false,
            ordering: true,
            info: true,
            responsive: true,
            autoWidth: false,
            dom: 'rt<"d-flex justify-content-between"ip>',
            order: [[0, 'asc']], // Sort by Item Name by default
            columnDefs: [
                { width: "200px", targets: 0 }, // Item Name
                { width: "100px", targets: 1 }, // Current Quantity
                { width: "100px", targets: 2 }, // Reorder Point
                { width: "120px", targets: 3 }, // Status
                { width: "140px", targets: 4 }  // Actions
            ]
        });
        
        // Create a container for our action buttons
        var actionContainer = $('<div class="action-row"></div>');
        
        // Create left button group
        var leftButtons = $('<div class="button-group left-buttons"></div>');
        leftButtons.append($('<a href="consumable_entry.php" class="btn btn-primary btn-mobile-icon"><i class="bi bi-plus-lg"></i><span class="btn-text"> Add New Material</span></a>'));
        
        // Create search group
        var searchGroup = $('<div class="search-container"></div>');
        var searchInput = $('<input type="search" class="form-control" placeholder="Search...">');
        searchGroup.append(searchInput);
        
        // Bind search input to DataTables search
        searchInput.on('keyup', function() {
            table.search(this.value).draw();
        });
        
        // Create right button group
        var rightButtons = $('<div class="button-group right-buttons"></div>');
        rightButtons.append($('<button class="btn btn-info btn-mobile-icon" id="printButton"><i class="bi bi-printer"></i><span class="btn-text"> Print</span></button>'));
        rightButtons.append($('<button class="btn btn-success btn-mobile-icon" id="excelButton"><i class="bi bi-file-excel"></i><span class="btn-text"> Excel</span></button>'));
        
        // Combine all groups
        actionContainer.append(leftButtons);
        actionContainer.append(searchGroup);
        actionContainer.append(rightButtons);
        
        // Insert the action container before the table
        actionContainer.insertBefore('#reorderTable');

        // Add print functionality
        $('#printButton').on('click', function() {
            // Create a new window for printing
            var printWindow = window.open('', '_blank');
            
            // Get the table HTML
            var tableHtml = $('#reorderTable').clone();
            
            // Remove the action buttons column
            tableHtml.find('th:last-child, td:last-child').remove();
            
            // Create the print content
            var printContent = `
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Reorder List - Print</title>
                    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
                    <style>
                        body { padding: 20px; }
                        .table { width: 100%; }
                        .table th { background-color: #f8f9fa; }
                        .status-badge { font-size: 0.875rem; padding: 0.25rem 0.5rem; border-radius: 0.25rem; }
                        .status-1 { background-color: #ffc107; color: #000; }
                        .status-2 { background-color: #17a2b8; color: #fff; }
                        .status-3 { background-color: #dc3545; color: #fff; }
                        .status-4 { background-color: #28a745; color: #fff; }
                    </style>
                </head>
                <body>
                    <h2 class="mb-4">Reorder List</h2>
                    ${tableHtml.prop('outerHTML')}
                </body>
                </html>
            `;
            
            // Write the content to the new window
            printWindow.document.write(printContent);
            printWindow.document.close();
            
            // Wait for the content to load before printing
            printWindow.onload = function() {
                printWindow.print();
                printWindow.close();
            };
        });

        // Add Excel export functionality
        $('#excelButton').on('click', function() {
            // Get the table data
            var data = table.data().toArray();
            
            // Create CSV content
            var csvContent = "Item Name,Current Quantity,Reorder Point,Status\n";
            
            data.forEach(function(row) {
                var itemName = $(row[0]).text().trim();
                var currentQuantity = row[1];
                var reorderPoint = row[2];
                var status = $(row[3]).text().trim();
                
                // Escape commas and quotes in the data
                itemName = itemName.replace(/"/g, '""');
                if (itemName.includes(',')) {
                    itemName = '"' + itemName + '"';
                }
                
                csvContent += `${itemName},${currentQuantity},${reorderPoint},${status}\n`;
            });
            
            // Create a blob and download it
            var blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            var link = document.createElement('a');
            var url = URL.createObjectURL(blob);
            
            link.setAttribute('href', url);
            link.setAttribute('download', 'reorder_list.csv');
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        });
        
        // Initialize table height
        function initializeTableHeight() {
            const windowHeight = window.innerHeight;
            const navbarHeight = 60;
            const actionRowHeight = $('.action-row').outerHeight();
            const footerHeight = (window.innerWidth < 768) ? 40 : 50;
            const padding = (window.innerWidth < 768) ? 10 : 20;
            
            let availableHeight = windowHeight - navbarHeight - actionRowHeight - footerHeight - padding;
            
            if (window.innerWidth < 768) {
                const minRowsVisible = 5;
                const approxRowHeight = 45;
                const minHeight = minRowsVisible * approxRowHeight;
                availableHeight = Math.max(availableHeight, minHeight);
            } else {
                availableHeight = Math.max(availableHeight, 300);
            }
            
            $('.dataTables_scrollBody').css('height', availableHeight + 'px');
            
            if (!$('.footer-wrapper').length) {
                $('.dataTables_info').wrap('<div class="footer-wrapper"></div>');
            }
            
            table.columns.adjust().draw();
        }
        
        // Initialize height after a short delay
        setTimeout(initializeTableHeight, 100);
        
        // Add resize handler
        $(window).on('resize', function() {
            initializeTableHeight();
        });
    });

    function showOrderHistory(consumableId) {
        // Load the order history modal
        fetch(`order_history_modal.php?id=${consumableId}`)
            .then(response => response.text())
            .then(html => {
                console.log('Loaded modal HTML');
                // Create a temporary div to hold the modal
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = html;
                
                // Add all modals to the document
                const modals = tempDiv.querySelectorAll('.modal');
                console.log('Found modals:', modals.length);
                modals.forEach(modal => {
                    document.body.appendChild(modal);
                });
                
                // Wait for Bootstrap to be ready
                setTimeout(() => {
                    // Initialize modals
                    const statusUpdateModalEl = document.getElementById('statusUpdateModal');
                    const newOrderModalEl = document.getElementById('newOrderModal');
                    const orderHistoryModalEl = document.getElementById('orderHistoryModal');
                    
                    console.log('Modal elements found:', {
                        statusUpdate: !!statusUpdateModalEl,
                        newOrder: !!newOrderModalEl,
                        orderHistory: !!orderHistoryModalEl
                    });
                    
                    if (statusUpdateModalEl) {
                        statusUpdateModal = new bootstrap.Modal(statusUpdateModalEl);
                    }
                    
                    if (newOrderModalEl) {
                        newOrderModal = new bootstrap.Modal(newOrderModalEl);
                        console.log('New order modal initialized');
                    }
                    
                    if (orderHistoryModalEl) {
                        const modal = new bootstrap.Modal(orderHistoryModalEl);
                        modal.show();
                        
                        // Remove all modals when the order history modal is hidden
                        orderHistoryModalEl.addEventListener('hidden.bs.modal', function () {
                            modals.forEach(modal => {
                                modal.remove();
                            });
                        });
                    }

                    // Bind event handlers for the new order button
                    const newOrderBtn = document.querySelector('#orderHistoryModal .btn-success');
                    console.log('New order button found:', !!newOrderBtn);
                    if (newOrderBtn) {
                        newOrderBtn.addEventListener('click', function() {
                            console.log('New order button clicked');
                            if (newOrderModal) {
                                console.log('Showing new order modal');
                                newOrderModal.show();
                            } else {
                                console.error('New order modal not initialized');
                            }
                        });
                    }
                }, 100);
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error loading order history. Please try again.');
            });
    }

    // Add modal functionality
    let statusUpdateModal;
    let newOrderModal;

    function showStatusUpdateModal(orderId) {
        if (statusUpdateModal) {
            document.getElementById('order_id').value = orderId;
            statusUpdateModal.show();
        }
    }

    function showNewOrderModal() {
        if (newOrderModal) {
            newOrderModal.show();
        }
    }

    function submitStatusUpdate() {
        const form = document.getElementById('statusUpdateForm');
        const formData = new FormData(form);

        fetch('update_order_status.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                statusUpdateModal.hide();
                location.reload();
            } else {
                alert('Error updating status: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error updating status. Please try again.');
        });
    }

    function submitNewOrder() {
        const form = document.getElementById('newOrderForm');
        const formData = new FormData(form);

        fetch('create_order.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                newOrderModal.hide();
                location.reload();
            } else {
                alert('Error creating order: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error creating order. Please try again.');
        });
    }
    </script>
</body>
</html> 