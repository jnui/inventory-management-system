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
            oh.PO AS po,
            oh.quantity_ordered AS qty_ordered,
            os.status_name,
            os.description as status_description,
            CASE 
                WHEN cm.optimum_quantity > 0 AND cm.whole_quantity < (cm.optimum_quantity * 0.5) THEN 'critical'
                WHEN cm.optimum_quantity > 0 AND cm.whole_quantity < cm.optimum_quantity THEN 'warning'
                ELSE 'normal'
            END as stock_level
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

    // Calculate totals for each status
    $totals = [
        'notOrdered' => 0,
        'backordered' => 0,
        'orderedWaiting' => 0
    ];

    foreach ($items as $item) {
        switch ($item['status_name']) {
            case 'Not Ordered':
                $totals['notOrdered']++;
                break;
            case 'Backordered':
                $totals['backordered']++;
                break;
            case 'Ordered & Waiting':
                $totals['orderedWaiting']++;
                break;
        }
    }

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
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css" rel="stylesheet">
    <!-- YADCF CSS -->
    <link href="assets/css/jquery.dataTables.yadcf.css" rel="stylesheet">
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
            white-space: normal !important;
        }
        
        /* Column width adjustments */
        #reorderTable th:nth-child(2), /* Item Name column */
        #reorderTable td:nth-child(2) {
            min-width: 300px !important;
            width: 300px !important;
        }

        #reorderTable th:nth-child(3), /* Current Stock column */
        #reorderTable td:nth-child(3),
        #reorderTable th:nth-child(4), /* Reorder Point column */
        #reorderTable td:nth-child(4),
        #reorderTable th:nth-child(5), /* Diameter column */
        #reorderTable td:nth-child(5) {
            min-width: 80px !important;
            width: 80px !important;
        }

        #reorderTable th:nth-child(6), /* Type column */
        #reorderTable td:nth-child(6),
        #reorderTable th:nth-child(7), /* Vendor column */
        #reorderTable td:nth-child(7),
        #reorderTable th:nth-child(8), /* Amt Ordrd column */
        #reorderTable td:nth-child(8),
        #reorderTable th:nth-child(9), /* PO column */
        #reorderTable td:nth-child(9) {
            min-width: 120px !important;
            width: 120px !important;
        }
        
        #reorderTable th:nth-child(10), /* Status column */
        #reorderTable td:nth-child(10) {
            min-width: 120px !important;
            width: 120px !important;
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
            justify-content: flex-start !important;
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
            margin-left: auto !important;
        }
        
        .search-container {
            flex: 0 1 300px !important;
            min-width: 150px !important;
            max-width: 300px !important;
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
        
        /* Ordered row highlighting - yellow background for rows with qty_ordered > 0 */
        .ordered-row td {
            background-color: #fff3cd !important; /* Light yellow background */
        }
        
        /* Smaller button styles */
        .btn-sm {
            padding: 0.15rem 0.3rem !important;
            font-size: 0.65rem !important;
        }
        
        /* Action buttons cell */
        .action-buttons-cell {
            display: flex;
            gap: 2px;
            justify-content: flex-start;
            align-items: center;
        }
        
        /* Adjust button padding in actions column */
        .action-buttons-cell .btn {
            padding: 0.15rem 0.3rem !important;
        }
        
        /* Mobile-specific styles */
        @media (max-width: 767px) {
            .action-row {
                flex-wrap: wrap !important;
                gap: 5px !important;
                margin-bottom: 5px !important;
            }
            
            .search-container {
                flex: 1 1 100% !important;
                max-width: 100% !important;
                margin: 5px 0 !important;
                order: -1 !important;
            }
            
            .search-container input {
                width: 100% !important;
                padding: 4px 8px !important;
                font-size: 14px !important;
                height: 32px !important;
            }
            
            .button-group {
                flex-wrap: wrap !important;
                gap: 5px !important;
            }
            
            .right-buttons {
                margin-left: 0 !important;
                width: 100% !important;
                justify-content: flex-start !important;
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
            margin-top: 10px !important;
        }
        
        /* Add to existing styles */
        .item-name {
            color: #0d6efd;
            text-decoration: underline;
        }
        .item-name:hover {
            color: #0a58ca;
            cursor: pointer;
        }
        
        /* DataTables control layout */
        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter {
            float: none !important;
            text-align: left !important;
            margin-bottom: 0 !important;
        }
        
        .dataTables_wrapper .dataTables_length {
            display: inline-block !important;
            margin-right: 1rem !important;
        }
        
        .dataTables_wrapper .dataTables_filter {
            display: inline-block !important;
            margin-left: 1rem !important;
        }
        
        .dataTables_wrapper .dataTables_info {
            clear: both !important;
            float: none !important;
            padding-top: 1rem !important;
        }
        
        /* Toggle control styles */
        .toggle-complete-container {
            display: inline-block !important;
            margin-left: 1rem !important;
            vertical-align: middle !important;
        }
        
        .toggle-complete-container label {
            margin-bottom: 0 !important;
            cursor: pointer !important;
            display: flex !important;
            align-items: center !important;
            font-size: 0.875rem !important;
        }
        
        .toggle-complete-container input[type="checkbox"] {
            margin-right: 0.5rem !important;
        }
        
        /* Mobile responsiveness */
        @media (max-width: 767px) {
            .dataTables_wrapper .dataTables_length,
            .dataTables_wrapper .dataTables_filter,
            .toggle-complete-container {
                display: block !important;
                margin: 0.5rem 0 !important;
            }
            
            .dataTables_wrapper .dataTables_filter input {
                width: 100% !important;
            }
        }
        
        /* Adjust DataTables length select box */
        .dataTables_length select {
            padding-right: 20px; /* Add padding to the right */
            min-width: 60px; /* Ensure enough width for the number and chevron */
        }
        
        /* Totals display styles */
        .totals-container {
            display: inline-block !important;
            margin-left: 1rem !important;
            vertical-align: middle !important;
            font-size: 0.875rem !important;
        }
        
        .totals-container span {
            margin-right: 1rem !important;
        }

        /* Filter checkboxes */
        .nopofilter,
        .hidecompletefilter {
            display: inline-block !important;
            margin-left: 1rem !important;
            vertical-align: middle !important;
            font-size: 0.875rem !important;
        }

        /* Compact YADCF dropdown filters */
        .yadcf-filter-wrapper select.yadcf-filter {
            font-size: 0.8rem;
            padding: 2px 5px;
            height: auto;
            line-height: 1.2;
        }

        .yadcf-filter-wrapper {
            margin: 0 4px 4px 0;
            display: inline-flex;
            align-items: center;
        }

        .yadcf-filter-reset-button {
            order: -1;
            margin-right: 4px;
            margin-left: 0;
            font-size: 0.8rem;
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
                            <th>ID</th>
                            <th>Name</th>
                            <th>In Stock</th>
                            <th>Re-up</th>
                            <th>Size</th>
                            <th>Material</th>
                            <th>Vendor</th>
                            <th>Ordered</th>
                            <th>PO</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): 
                            $stockClass = '';
                            if ($item['stock_level'] === 'critical') {
                                $stockClass = 'quantity-critical';
                            } elseif ($item['stock_level'] === 'warning') {
                                $stockClass = 'quantity-warning';
                            }
                            
                            // Determine row class based on ordered quantity
                            $rowClass = '';
                            if (!empty($item['qty_ordered']) && $item['qty_ordered'] > 0) {
                                $rowClass = 'ordered-row';
                            }
                        ?>
                            <tr class="<?= $rowClass ?>">
                                <td><?= htmlspecialchars($item['id']) ?></td>
                                <td class="item-name" style="cursor: pointer;" 
                                    onclick="showOrderHistory(<?= $item['id'] ?>)"
                                    title="Click to view/create order">
                                    <?= htmlspecialchars($item['item_name']) ?>
                                </td>
                                <td class="<?= $stockClass ?>"><?= htmlspecialchars($item['whole_quantity']) ?></td>
                                <td><?= htmlspecialchars($item['reorder_threshold']) ?></td>
                                <td>
                                    <?php
                                        $diam = $item['diameter'];
                                        if ($diam !== null && $diam !== '') {
                                            $formatted = rtrim(rtrim(number_format((float)$diam, 2, '.', ''), '0'), '.');
                                            echo htmlspecialchars($formatted);
                                        }
                                    ?>
                                </td>
                                <td><?= htmlspecialchars($item['composition_description'] ?? '') ?></td>
                                <td><?= htmlspecialchars($item['vendor'] ?? '') ?></td>
                                <td><?= htmlspecialchars($item['qty_ordered'] ?? '') ?></td>
                                <td><?= htmlspecialchars($item['po'] ?? '') ?></td>
                                <td>
                                    <span class="status-badge status-<?= htmlspecialchars($item['order_status_id']) ?>" 
                                          title="<?= htmlspecialchars($item['status_description']) ?>">
                                        <?= htmlspecialchars($item['status_name']) ?>
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
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <!-- YADCF JS -->
    <script src="js/jquery.dataTables.yadcf.js"></script>
    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- DataTables Bootstrap 5 JS -->
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
    $(document).ready(function() {
        // Initialize DataTable
        const table = $('#reorderTable').DataTable({
            scrollCollapse: true,
            scrollX: true,
            paging: true,
            ordering: true,
            info: true,
            responsive: true,
            autoWidth: false,
            language: {
            lengthMenu: "Show _MENU_" // This removes the word "entries"
            },
            order: [[0, 'asc']], // Sort by ID by default
            pageLength: 500,
            lengthMenu: [[ 50,100,500 -1], [50, 100, 500, "All"]],
            dom: '<"top"lf>rt<"bottom"ip>',
            columnDefs: [
                { targets: 9, visible: false }, // hide Status column
                { targets: 0, width: '40px' }
            ],
            initComplete: function() {
                initializeTableHeight();
                
                const api = this.api();
                const topRow = $(api.table().container()).find('.dataTables_length').parent();
                
                // Create totals container
                const totalsContainer = $('<div class="totals-container">');
                topRow.append(totalsContainer);
                
                // Use existing data to display totals
                const notOrdered = <?= $totals['notOrdered'] ?>;
                const backordered = <?= $totals['backordered'] ?>;
                const orderedWaiting = <?= $totals['orderedWaiting'] ?>;
                
                // Update totals display
                totalsContainer.html(
                    `<span>Not Ordered: ${notOrdered}</span>` +
                    `<span>Backordered: ${backordered}</span>` +
                    `<span>Ordered & Waiting: ${orderedWaiting}</span>`
                );

                // Insert No PO checkbox filter
                const noPoHtml = `
                    <div class="nopofilter">
                        <input type="checkbox" id="noPoCheckbox">
                        <label for="noPoCheckbox" class="ms-1">No PO</label>
                    </div>`;
                topRow.append($(noPoHtml));

                // Insert Hide Completed checkbox filter (checked by default)
                const hideCompleteHtml = `
                    <div class="hidecompletefilter">
                        <input type="checkbox" id="hideCompleteCheckbox" >
                        <label for="hideCompleteCheckbox" class="ms-1">Hide Completed</label>
                    </div>`;
                topRow.append($(hideCompleteHtml));

                // Add Excel export button next to search box
                const $filter = $(this.api().table().container()).find('div.dataTables_filter');
                if (!$filter.find('#excelButton').length) {
                    const $btn = $('<button id="excelButton" class="btn btn-success btn-sm ms-2 btn-mobile-icon"><i class="bi bi-file-excel"></i><span class="btn-text"> Excel</span></button>');
                    $filter.append($btn);
                }
            }
        });
        
        // Function to initialize table height
        function initializeTableHeight() {
            const windowHeight = $(window).height();
            const tableTop = $('#reorderTable').offset().top;
            const footerHeight = 100; // Adjust based on your footer height
            const availableHeight = windowHeight - tableTop - footerHeight;
            
            // Set the container height
            $('.dataTables_scrollBody').css({
                'max-height': availableHeight + 'px',
                'height': availableHeight + 'px'
            });
        }
        
        // Initialize height after a short delay
        setTimeout(initializeTableHeight, 100);
        
        // Add resize handler
        $(window).on('resize', function() {
            initializeTableHeight();
        });

        // DataTables custom filter for No PO checkbox
        $.fn.dataTable.ext.search.push(function(settings, data){
            if(settings.nTable.id !== 'reorderTable') return true;
            const checked = $('#noPoCheckbox').prop('checked');
            if(!checked) return true;
            const po = (data[8] || '').trim(); // PO column index
            return po === '';
        });

        // DataTables custom filter for Hide Completed checkbox
        $.fn.dataTable.ext.search.push(function(settings, data){
            if(settings.nTable.id !== 'reorderTable') return true;
            const checked = $('#hideCompleteCheckbox').prop('checked');
            if(!checked) return true;
            const status = (data[9] || '').trim(); // Status column index
            return status !== 'Complete';
        });

        // Apply filters immediately on initial load
        table.draw();

        // Trigger table redraw when checkbox toggled
        $(document).on('change', '#noPoCheckbox', function(){
            table.draw();
        });

        // Trigger table redraw when Hide Completed checkbox toggled
        $(document).on('change', '#hideCompleteCheckbox', function(){
            table.draw();
        });

        // CSV helpers
        function escapeCsv(val){
            if(val==null) return '';
            val = String(val).replace(/"/g,'""');
            return (val.indexOf(',')>-1||val.indexOf('"')>-1) ? '"'+val+'"' : val;
        }

        function downloadCsv(content, filename){
            const blob = new Blob([content], {type:'text/csv;charset=utf-8;'});
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.setAttribute('href', url);
            link.setAttribute('download', filename);
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(url);
        }

        // Delegate click handler for export
        $(document).on('click', '#excelButton', function () {
            const rows = table.rows({ search: 'applied' }).data().toArray();

            const stripHtml = (html) => $('<div>').html(html).text().trim();

            let csv = 'ID,Name,Current Stock,Reorder Point,Size,Material,Vendor,Amt Ordrd,PO\n';

            rows.forEach(row => {
                csv += [
                    escapeCsv(stripHtml(row[0])), // ID
                    escapeCsv(stripHtml(row[1])), // Name
                    escapeCsv(stripHtml(row[2])), // Current Stock
                    escapeCsv(stripHtml(row[3])), // Reorder Point
                    escapeCsv(stripHtml(row[4])), // Diameter
                    escapeCsv(stripHtml(row[5])), // Type
                    escapeCsv(stripHtml(row[6])), // Vendor
                    escapeCsv(stripHtml(row[7])), // Amt Ordrd
                    escapeCsv(stripHtml(row[8]))  // PO
                ].join(',') + '\n';
            });

            downloadCsv(csv.trimEnd(), 'reorder_list.csv'); // trim any trailing newline
        });

        /* ------------------ YADCF FILTERS ------------------ */
        if (typeof yadcf !== 'undefined') {
            yadcf.init(table, [
                {
                    column_number: 4, // Diameter column (Size)
                    filter_type: 'select',
                    filter_default_label: 'Size...',
                    sort_as: 'custom',
                    sort_as_custom_func: function(a, b) {
                        return parseFloat(a) - parseFloat(b);
                    }
                },
                {
                    column_number: 5, // Material column
                    filter_type: 'select',
                    filter_default_label: 'Material...'
                }
            ]);

            // Ensure diameter filter options are numerically sorted
            function sortDiameterFilter() {
                const $sel = $('#yadcf-filter--reorderTable-4');
                if (!$sel.length) return;
                const $opts = $sel.find('option').not(':first'); // exclude placeholder
                $opts.sort(function(a, b) {
                    return parseFloat(a.value) - parseFloat(b.value);
                });
                $sel.append($opts);
            }

            // Trim whitespace from material options
            function trimMaterialOptions() {
                $('#yadcf-filter--reorderTable-5 option').each(function() {
                    this.text = this.text.trim();
                    this.value = this.value.trim();
                });
            }

            setTimeout(function() {
                sortDiameterFilter();
                trimMaterialOptions();
            }, 0);

            table.on('draw', function() {
                sortDiameterFilter();
                trimMaterialOptions();
            });
        } else {
            console.error('yadcf failed to load');
        }
    });

    // Global modal variables
    let statusUpdateModal = null;
    let newOrderModal = null;

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
                    // Remove any existing modal with the same ID
                    const existingModal = document.getElementById(modal.id);
                    if (existingModal) {
                        existingModal.remove();
                    }
                    document.body.appendChild(modal);
                });
                
                // Initialize modals
                const statusUpdateModalEl = document.getElementById('statusUpdateModal');
                const newOrderModalEl = document.getElementById('newOrderModal');
                const orderHistoryModalEl = document.getElementById('orderHistoryModal');
                
                if (statusUpdateModalEl) {
                    statusUpdateModal = new bootstrap.Modal(statusUpdateModalEl);
                }
                
                if (newOrderModalEl) {
                    newOrderModal = new bootstrap.Modal(newOrderModalEl);
                }
                
                if (orderHistoryModalEl) {
                    const modal = new bootstrap.Modal(orderHistoryModalEl);
                    modal.show();
                    
                    // Remove all modals when the order history modal is hidden
                    orderHistoryModalEl.addEventListener('hidden.bs.modal', function () {
                        modals.forEach(modal => {
                            modal.remove();
                        });
                        // Reset modal variables
                        statusUpdateModal = null;
                        newOrderModal = null;
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error loading order history. Please try again.');
            });
    }

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
                // Hide the status update modal
                if (statusUpdateModal) {
                    statusUpdateModal.hide();
                }
                // Reload the page to show updated status
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
                // Hide the new order modal
                if (newOrderModal) {
                    newOrderModal.hide();
                }
                // Reload the page to show updated status
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