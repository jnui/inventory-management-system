<?php
// consumable_list.php
// Include authentication check
require_once 'auth_check.php';

require_once 'db_connection.php';

try {
    // Retrieve consumable materials along with their assigned normal location and most recent change date
    $stmt = $pdo->query("
        SELECT cm.id, cm.item_type, cm.item_name,
               loc.location_name AS normal_location,
               cm.item_units_whole, cm.item_units_part,
               cm.qty_parts_per_whole, cm.composition_description,
               cm.whole_quantity, cm.reorder_threshold,
               (SELECT MAX(ice.change_date) 
                FROM inventory_change_entries ice 
                WHERE ice.consumable_material_id = cm.id) AS last_updated
        FROM consumable_materials cm
        LEFT JOIN item_locations loc ON cm.normal_item_location = loc.id
        ORDER BY cm.item_name ASC
    ");
    $consumables = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Consumable Materials</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Opera compatibility -->
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <!-- jQuery (required for DataTables) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap CSS for responsiveness -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css" rel="stylesheet">
    
    <!-- DataTables Core JS -->
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    
    <!-- JSZip for Excel export -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    
    <!-- Custom CSS for iPad optimization -->
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
        #consumablesTable_wrapper {
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
        
        /* Style for the search container */
        .search-container {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-grow: 1;
            min-width: 200px;
        }
        
        /* Ensure search input doesn't grow too large */
        .search-container input {
            max-width: 300px;
        }
        
        /* Style for the buttons container */
        .buttons-container {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-shrink: 0;
        }
        
        /* Ensure content doesn't get hidden under the navigation bar */
        .content-container {
            width: 100% !important;
            overflow-x: visible !important;
            padding-top: 60px !important; /* Match navbar height */
            padding-bottom: 60px !important;
        }
        
        /* Style for the column visibility button */
        .dt-buttons {
            margin-left: 10px;
        }
        
        .dt-button {
            background-color: #6c757d;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .dt-button:hover {
            background-color: #5a6268;
        }
        
        /* Style for action buttons in table */
        .action-buttons-cell {
            display: flex;
            gap: 5px;
        }
        
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
        }
        
        /* Style for rows that need reordering */
        .reorder-needed {
            height: auto !important;
            min-height: 45px !important;
        }
        
        .reorder-needed td {
            background-color: #fff3cd !important; /* Light yellow background */
            font-weight: bold !important;
            height: auto !important;
            min-height: 45px !important;
            line-height: 1.5 !important;
            padding: 0.5rem !important;
        }
        
        /* Ensure highlighting works with DataTables striping */
        .table-striped > tbody > tr.reorder-needed:nth-of-type(odd) td,
        .table-striped > tbody > tr.reorder-needed:nth-of-type(even) td {
            background-color: #fff3cd !important;
        }
        
        /* Ensure all rows have consistent height */
        #consumablesTable tbody tr {
            height: auto !important;
            min-height: 45px !important;
        }
        
        #consumablesTable tbody tr td {
            height: auto !important;
            min-height: 45px !important;
            line-height: 1.5 !important;
            padding: 0.5rem !important;
        }
        
        /* Style for the dropdown menu */
        .dropdown-menu {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .dropdown-item {
            padding: 0.5rem 1rem;
            cursor: pointer;
        }
        
        .dropdown-item.active {
            background-color: #007bff;
            color: white;
        }
        
        .dropdown-item:hover {
            background-color: #f8f9fa;
        }
        
        .dropdown-item.active:hover {
            background-color: #0069d9;
        }
        
        /* Additional spacing for the table */
        #consumablesTable {
            margin: 0 !important;
            width: 100% !important;
            border-collapse: separate !important;
            border-spacing: 0 !important;
        }
        
        /* Ensure header has enough height */
        #consumablesTable thead th {
            height: auto !important;
            min-height: 40px !important;
            white-space: nowrap !important;
            vertical-align: middle !important;
        }
        
        /* Ensure the scrollable area has enough space */
        .dataTables_scrollBody {
            min-height: 200px;
        }
        
        /* Ensure the header doesn't overlap with content */
        .dataTables_scrollHead {
            margin-bottom: 5px !important;
        }
        
        /* Fix for the last column being cut off */
        .dataTables_wrapper {
            width: 100% !important;
            overflow-x: auto !important;
            -webkit-overflow-scrolling: touch !important; /* Smooth scrolling on iOS */
            padding-right: 0 !important; /* Remove right padding to prevent cutoff */
            margin-right: 0 !important;
        }
        
        /* Style for the footer */
        .dataTables_info {
            background-color: #f8f9fa !important;
            padding: 0px 15px 10px 15px!important;
            /* border-top: 1px solid #dee2e6 !important;
            border-bottom: 1px solid #dee2e6 !important; */
            margin-top: 0px !important;
        }
        
        /* Footer wrapper styles */
        .footer-wrapper {
            position: fixed !important;
            bottom: 0 !important;
            left: 0 !important;
            right: 0 !important;
            background-color: #f8f9fa !important;
            border-top: 1px solidrgb(139, 151, 163) !important;
            border-bottom: 1px solid #dee2e6 !important;
            z-index: 1000 !important;
            padding: 0px 5px !important;
        }
        
        /* Adjust table wrapper to account for fixed footer */
        .dataTables_wrapper {
            padding-bottom: 0px !important; /* Height of footer + some padding */
        }
        
        /* Make sure the table takes full width and allows scrolling on iPad */
        #consumablesTable {
            width: 100% !important;
            max-width: none !important;
            table-layout: fixed !important; /* Force fixed table layout */
        }
        
        /* Ensure the Actions column has enough width on iPad */
        #consumablesTable th:last-child,
        #consumablesTable td:last-child {
            min-width: 140px !important; /* Increase width for the action buttons */
            width: auto !important;
            white-space: nowrap !important;
        }
        
        /* Specific fix for the first row */
        .dataTables_scrollBody tbody tr:first-child {
            margin-top: 0px !important;
            border-top: 0px solid transparent !important;
            position: relative;
            top: 0px !important;
        }
        
        /* Ensure all rows have consistent height */
        .dataTables_scrollBody tbody tr {
            height: auto !important;
            min-height: 45px !important;
            position: relative !important;
            top: 0px !important;
        }
        
        /* Ensure the table container has enough width for iPad */
        .container.content-container {
            max-width: 100% !important;
            padding-right: 5px !important;
            padding-left: 5px !important;
            width: 100% !important;
            overflow-x: visible !important;
        }
        
        /* Move search closer to buttons */
        .search-container {
            margin-left: 10px !important;
            margin-right: auto !important;
        }
        
        /* iPad-specific adjustments */
        @media only screen and (min-device-width: 768px) and (max-device-width: 1024px) {
            .dataTables_wrapper {
                overflow-x: scroll !important;
                width: 100% !important;
                max-width: 100% !important;
            }
            
            #consumablesTable {
                width: 100% !important;
                max-width: none !important;
                table-layout: fixed !important;
            }
            
            .container.content-container {
                padding-right: 0 !important;
                padding-left: 0 !important;
                max-width: 100% !important;
                width: 100% !important;
                overflow-x: hidden !important;
            }
            
            /* Force the table to be properly sized */
            .dataTables_scrollHeadInner,
            .dataTables_scrollHeadInner table {
                width: 100% !important;
            }
            
            /* Ensure header cells align with data cells */
            .dataTables_scrollHeadInner table,
            .dataTables_scrollBody table {
                table-layout: fixed !important;
                width: 100% !important;
            }
            
            /* Make sure columns have consistent widths */
            .dataTables_scrollHeadInner th,
            .dataTables_scrollBody td {
                width: auto !important;
                min-width: auto !important;
                box-sizing: border-box !important;
            }
            
            /* Ensure the last column is visible */
            #consumablesTable th:last-child,
            #consumablesTable td:last-child {
                min-width: 140px !important;
                width: 140px !important;
            }
        }
        
        /* Ensure the table is not cut off on the right */
        .table-responsive {
            overflow-x: auto !important;
            -webkit-overflow-scrolling: touch !important;
        }
        
        /* Wrap the table in a responsive container */
        #consumablesTable_wrapper {
            overflow-x: auto !important;
            width: 100% !important;
            padding-right: 0 !important;
        }
        
        /* Styles for rows that require reordering */
        .reorder-row {
            background-color: #fffbeb !important; /* Light yellow */
            border-left: 5px solid #fbbf24 !important; /* Yellow left border */
        }
        
        /* Highlight class for flashing effect */
        .highlight-row td {
            background-color: #ffb6c1 !important; /* Pink background */
        }
        
        /* Ensure proper highlighting with DataTables striping */
        .reorder-row.odd {
            background-color: #fffbeb !important;
        }
        
        .reorder-row.even {
            background-color: #fffbeb !important;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .action-row {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .search-container {
                margin-top: 10px;
                width: 100%;
            }
        }
        
        /* iPad specific adjustments */
        @media only screen and (min-width: 768px) and (max-width: 1024px) {
            /* Button layout fixes */
            .action-row {
                display: flex !important;
                flex-direction: row !important;
                flex-wrap: nowrap !important;
                gap: 8px !important;
                margin-bottom: 10px !important;
                align-items: center !important;
                width: 100% !important;
                justify-content: space-between !important;
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

            /* Fix footer spacing */
            .dataTables_wrapper {
                margin-bottom: 50px !important;
            }

            .footer-wrapper {
                margin-top: 0 !important;
                height: 50px !important;
                display: flex !important;
                align-items: center !important;
                border-top: 1px solid #dee2e6 !important;
            }

            /* Table responsiveness */
            .table-responsive {
                overflow-x: auto !important;
                -webkit-overflow-scrolling: touch !important;
            }

            .container.content-container {
                max-width: 100% !important;
                padding-right: 5px !important;
                padding-left: 5px !important;
                width: 100% !important;
                overflow-x: hidden !important;
            }
        }
        
        /* General table responsiveness */
        .table-responsive {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            margin-bottom: 1rem;
        }
        
        /* Ensure the action row has proper spacing */
        .action-row {
            margin-bottom: 5px;
            gap: 10px;
        }
        
        /* Ensure the table has proper spacing */
        #consumablesTable {
            margin-top: 5px;
            width: 100% !important;
        }
        
        /* Ensure the Actions column has enough width */
        #consumablesTable th:last-child,
        #consumablesTable td:last-child {
            min-width: 140px;
            white-space: nowrap;
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
            
            /* Make utility buttons part of main row */
            .utility-buttons {
                display: flex !important;
                gap: 5px !important;
                margin: 0 !important;
            }
            
            #consumablesTable tbody tr td {
                padding: 8px 5px !important;
            }
            
            .dataTables_wrapper {
                margin-bottom: 40px !important;
            }
            
            /* Optimize table height for mobile */
            .dataTables_scrollBody {
                height: calc(100vh - 200px) !important;
                min-height: 225px !important; /* 5 rows minimum */
            }
            
            /* Reduce action row spacing on mobile */
            .action-row {
                gap: 5px !important;
                margin-bottom: 5px !important;
            }
            
            /* Make buttons more compact on mobile */
            .btn-sm {
                padding: 0.2rem 0.4rem !important;
                font-size: 0.7rem !important;
            }
            
            /* Hide button text on mobile */
            .btn-mobile-icon .btn-text {
                display: none !important;
            }
            
            /* Make buttons square and compact */
            .btn-mobile-icon {
                width: 32px !important;
                height: 32px !important;
                padding: 4px !important;
                display: inline-flex !important;
                align-items: center !important;
                justify-content: center !important;
                font-size: 1rem !important;
            }
            
            /* Adjust icon size */
            .btn-mobile-icon i {
                font-size: 1.1rem !important;
            }
            
            /* Make action buttons more compact */
            .action-buttons-cell .btn {
                width: 32px !important;
                height: 32px !important;
                padding: 4px !important;
                display: inline-flex !important;
                align-items: center !important;
                justify-content: center !important;
            }
            
            .action-buttons-cell .btn-text {
                display: none !important;
            }
            
            /* Adjust dropdown button */
            #columnToggleButton {
                width: 32px !important;
                height: 32px !important;
                padding: 4px !important;
            }
            
            #columnToggleButton::after {
                display: none !important;
            }
        }
        
        /* iPad landscape mode specific adjustments */
        @media only screen 
        and (min-device-width: 768px) 
        and (max-device-width: 1024px) 
        and (orientation: landscape) {
            /* Button layout fixes */
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
                flex-direction: row !important;
                align-items: center !important;
                margin: 0 !important;
            }

            .search-container {
                flex: 0 1 300px !important;
                min-width: 150px !important;
                margin: 0 8px !important;
            }

            /* Ensure buttons don't wrap */
            .btn {
                padding: 6px 12px !important;
                font-size: 14px !important;
                flex-shrink: 0 !important;
                white-space: nowrap !important;
                margin: 0 !important;
            }

            /* Fix footer spacing */
            .dataTables_wrapper {
                margin-bottom: 50px !important;
                padding-bottom: 0 !important;
            }

            .footer-wrapper {
                position: fixed !important;
                bottom: 0 !important;
                left: 0 !important;
                right: 0 !important;
                height: 50px !important;
                margin: 0 !important;
                padding: 0 !important;
                background: #f8f9fa !important;
                border-top: 1px solid #dee2e6 !important;
                z-index: 1000 !important;
                display: flex !important;
                align-items: center !important;
            }

            .justify-content-between {
                margin: 0 !important;
                padding: 0 !important;
                height: 50px !important;
                display: flex !important;
                align-items: center !important;
                width: 100% !important;
            }

            /* Table responsiveness */
            .table-responsive {
                overflow-x: auto !important;
                -webkit-overflow-scrolling: touch !important;
                margin-bottom: 0 !important;
            }

            .container.content-container {
                max-width: 100% !important;
                padding-right: 5px !important;
                padding-left: 5px !important;
                width: 100% !important;
                overflow-x: hidden !important;
                margin-bottom: 50px !important;
            }

            /* Ensure DataTables info and pagination align properly */
            .dataTables_info {
                padding: 15px !important;
                margin: 0 !important;
                line-height: 20px !important;
                background-color: #f8f9fa !important;
            }

            /* Force right buttons to stay in line */
            .right-buttons {
                display: flex !important;
                flex-direction: row !important;
                gap: 8px !important;
                margin: 0 !important;
                flex-wrap: nowrap !important;
                flex-shrink: 0 !important;
            }

            /* Ensure Smart Entry button stays with other buttons */
            .right-buttons .btn {
                margin: 0 !important;
                flex-shrink: 0 !important;
            }

            /* Override any conflicting styles */
            .utility-buttons {
                display: flex !important;
                flex-direction: row !important;
                gap: 8px !important;
                margin: 0 !important;
                flex-wrap: nowrap !important;
            }
        }
        
        /* Navigation bar should be above all */
        .navigation-bar {
            z-index: 1000 !important;
        }
    </style>
    <script>
        $(document).ready(function() {
            // Function to calculate available height for the table
            function calculateTableHeight() {
                const windowHeight = window.innerHeight;
                const navbarHeight = 60; // Height of the navigation bar
                const actionRowHeight = $('.action-row').outerHeight() || 0;
                const padding = 20; // Some padding at the bottom
                
                // Calculate available height
                const availableHeight = windowHeight - navbarHeight - actionRowHeight - padding;
                
                // Ensure minimum height of 300px
                return Math.max(availableHeight, 300);
            }

            // Define applyHighlighting function first
            function applyHighlighting() {
                $('#consumablesTable tbody tr').each(function() {
                    var row = $(this);
                    if (row.attr('data-needs-reorder') === 'true') {
                        row.addClass('reorder-needed');
                        // Apply styles directly to ensure they take effect
                        row.css({
                            'height': 'auto !important',
                            'min-height': '45px !important'
                        });
                        row.find('td').css({
                            'height': 'auto !important',
                            'min-height': '45px !important',
                            'line-height': '1.5 !important',
                            'padding': '0.5rem !important',
                            'background-color': '#fff3cd !important',
                            'font-weight': 'bold !important'
                        });
                    } else {
                        row.removeClass('reorder-needed');
                        // Reset styles for non-reorder rows
                        row.css({
                            'height': 'auto !important',
                            'min-height': '45px !important'
                        });
                        row.find('td').css({
                            'height': 'auto !important',
                            'min-height': '45px !important',
                            'line-height': '1.5 !important',
                            'padding': '0.5rem !important'
                        });
                    }
                });
            }

            // Initialize DataTables
            var table = $('#consumablesTable').DataTable({
                scrollCollapse: true,
                scrollX: true,
                paging: false,
                ordering: true,
                info: true,
                responsive: true,
                autoWidth: false,
                dom: 'rt<"d-flex justify-content-between"ip>',
                order: [[2, 'asc']], // Set default sort to column 2 (Item Name) in ascending order
                columnDefs: [
                    { visible: false, targets: [ 0, 1, 6, 7, 8, 10, 11] },
                    { width: "140px", targets: -1 },
                    { width: "50px", targets: 0 },
                    { width: "100px", targets: 1 },
                    { width: "200px", targets: 2 },
                    { width: "150px", targets: 3 },
                    { width: "100px", targets: 4 },
                    { width: "100px", targets: 5 },
                    { width: "100px", targets: 9 },
                    { width: "200px", targets: 10 },
                    { width: "120px", targets: 11 }
                ],
                drawCallback: function() {
                    // Check for scroll_to parameter in URL
                    const urlParams = new URLSearchParams(window.location.search);
                    const scrollToId = urlParams.get('scroll_to');
                    
                    if (scrollToId) {
                        console.log("Found scroll_to parameter:", scrollToId);
                        
                        // Find the row by ID using jQuery
                        const $rows = $('#consumablesTable tbody tr');
                        const $targetRow = $rows.filter(function() {
                            return $(this).find('td:first').text().trim() === scrollToId;
                        });
                        
                        if ($targetRow.length) {
                            console.log("Found matching row");
                            
                            // Get container
                            const $container = $('.dataTables_scrollBody');
                            
                            // Calculate position
                            const offset = $targetRow.position().top;
                            console.log("Row offset:", offset);
                            
                            // Scroll to row first, then start flashing
                            $container.animate({
                                scrollTop: offset - 100
                            }, 500, function() {
                                // After scrolling completes, start the flashing sequence
                                console.log("Starting flash sequence");
                                let flashCount = 0;
                                const maxFlashes = 6; // 3 complete cycles (pink-default-pink-default-pink-default)
                                
                                function flashRow() {
                                    if (flashCount < maxFlashes) {
                                        if (flashCount % 2 === 0) {
                                            console.log("Flash ON:", flashCount);
                                            $targetRow.addClass('highlight-row');
                                        } else {
                                            console.log("Flash OFF:", flashCount);
                                            $targetRow.removeClass('highlight-row');
                                        }
                                        flashCount++;
                                        // Schedule next flash with 250ms interval for faster flashing
                                        setTimeout(flashRow, 250);
                                    }
                                }
                                
                                // Start the flashing sequence
                                flashRow();
                            });
                        } else {
                            console.error("Could not find row with ID:", scrollToId);
                        }
                    }
                    
                    // Rest of your existing drawCallback code
                    $('.dataTables_scrollHead').css('margin-bottom', '5px');
                    $('.dataTables_scrollBody').css('padding-top', '0px');
                    $(this).css('width', '100%');
                    
                    // Apply highlighting
                    applyHighlighting();
                }
            });
            
            try {
                // Create a container for our action buttons
                var actionContainer = $('<div class="action-row"></div>');
                
                // Create left button group
                var leftButtons = $('<div class="button-group left-buttons"></div>');
                leftButtons.append($('<a href="consumable_entry.php" class="btn btn-primary btn-mobile-icon"><i class="bi bi-plus-lg"></i><span class="btn-text"> Add New Material</span></a>'));
                
                // Create column visibility dropdown with proper Bootstrap structure
                var columnToggleHtml = `
                    <div class="dropdown d-inline-block">
                        <button class="btn btn-secondary dropdown-toggle btn-mobile-icon" type="button" id="columnToggleButton" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-layout-three-columns"></i><span class="btn-text"> Show/Hide Cols</span>
                        </button>
                        <ul class="dropdown-menu p-2" aria-labelledby="columnToggleButton" style="min-width: 200px;">
                            <li><a class="dropdown-item" data-column="0">ID</a></li>
                            <li><a class="dropdown-item" data-column="1">Item Type</a></li>
                            <li><a class="dropdown-item" data-column="2">Item Name</a></li>
                            <li><a class="dropdown-item" data-column="3">Normal Location</a></li>
                            <li><a class="dropdown-item" data-column="4">Whole Quantity</a></li>
                            <li><a class="dropdown-item" data-column="5">Reorder Threshold</a></li>
                            <li><a class="dropdown-item" data-column="6">Units (Whole)</a></li>
                            <li><a class="dropdown-item" data-column="7">Units (Part)</a></li>
                            <li><a class="dropdown-item" data-column="8">Qty Parts Per Whole</a></li>
                            <li><a class="dropdown-item" data-column="9">Total Part Units</a></li>
                            <li><a class="dropdown-item" data-column="10">Composition Description</a></li>
                            <li><a class="dropdown-item" data-column="11">Last Updated</a></li>
                        </ul>
                    </div>
                `;
                leftButtons.append($(columnToggleHtml));
                
                // Initialize column visibility toggle
                $(document).on('click', '.dropdown-item', function(e) {
                    e.preventDefault();
                    var column = table.column($(this).data('column'));
                    column.visible(!column.visible());
                    $(this).toggleClass('active');
                });

                // Set initial active state for visible columns
                setTimeout(function() {
                    table.columns().every(function(index) {
                        if (this.visible()) {
                            $('.dropdown-item[data-column="' + index + '"]').addClass('active');
                        }
                    });
                }, 100);
                
                // Create search group
                var searchGroup = $('<div class="search-container"></div>');
                var searchInput = $('<input type="search" class="form-control" placeholder="Search...">');
                searchGroup.append(searchInput);
                
                // Create right button group with all buttons in one container
                var rightButtons = $('<div class="button-group right-buttons"></div>');
                // Add Smart Entry button directly to the right buttons group
                rightButtons.append($('<a href="natural_language_inventory.php" class="btn btn-success btn-mobile-icon"><i class="bi bi-magic"></i><span class="btn-text"> Smart Entry</span></a>'));
                rightButtons.append($('<button class="btn btn-info btn-mobile-icon"><i class="bi bi-printer"></i><span class="btn-text"> Print</span></button>'));
                rightButtons.append($('<button class="btn btn-success btn-mobile-icon"><i class="bi bi-file-excel"></i><span class="btn-text"> Excel</span></button>'));
                
                // Combine all groups
                actionContainer.append(leftButtons);
                actionContainer.append(searchGroup);
                actionContainer.append(rightButtons);
                
                // Insert the action container before the table
                actionContainer.insertBefore('#consumablesTable');
                
                // Now that all elements are rendered, set the table height
                function initializeTableHeight() {
                    const windowHeight = window.innerHeight;
                    const navbarHeight = 60;
                    const actionRowHeight = $('.action-row').outerHeight();
                    const footerHeight = (window.innerWidth < 768) ? 40 : 50; // Smaller footer on phones
                    const padding = (window.innerWidth < 768) ? 10 : 20; // Less padding on phones
                    
                    // Calculate available height
                    let availableHeight = windowHeight - navbarHeight - actionRowHeight - footerHeight - padding;
                    
                    // For phones in portrait mode (width < 768px), ensure minimum 5 rows visible
                    if (window.innerWidth < 768) {
                        const minRowsVisible = 5;
                        const approxRowHeight = 45; // Approximate height of one row
                        const minHeight = minRowsVisible * approxRowHeight;
                        availableHeight = Math.max(availableHeight, minHeight);
                    } else {
                        // For larger screens, keep original minimum
                        availableHeight = Math.max(availableHeight, 300);
                    }
                    
                    // Apply the height using the correct DataTables API
                    $('.dataTables_scrollBody').css('height', availableHeight + 'px');
                    
                    // Wrap footer in fixed container if not already wrapped
                    if (!$('.footer-wrapper').length) {
                        $('.dataTables_info').wrap('<div class="footer-wrapper"></div>');
                    }
                    
                    // Adjust table columns and redraw
                    table.columns.adjust().draw();
                }
                
                // Initialize height after a short delay to ensure all elements are rendered
                setTimeout(initializeTableHeight, 100);
                
                // Add resize handler
                $(window).on('resize', function() {
                    initializeTableHeight();
                });
                
                // Apply highlighting after initialization and on each draw
                applyHighlighting();
                table.on('draw', function() {
                    applyHighlighting();
                    
                    // Re-apply specific fix for reorder rows after table is redrawn
                    setTimeout(function() {
                        // Fix for the first row - remove any extra spacing
                        $('.dataTables_scrollBody tbody tr:first-child').css({
                            'margin-top': '0px !important',
                            'border-top': '0px solid transparent !important',
                            'position': 'relative !important',
                            'top': '0px !important'
                        });
                        
                        $('.reorder-needed').css({
                            'height': 'auto !important',
                            'min-height': '45px !important'
                        });
                        
                        $('.reorder-needed td').css({
                            'height': 'auto !important',
                            'min-height': '45px !important',
                            'line-height': '1.5 !important',
                            'padding': '0.5rem !important',
                            'background-color': '#fff3cd !important',
                            'font-weight': 'bold !important'
                        });
                        
                        // Ensure all rows have consistent height
                        $('#consumablesTable tbody tr').css({
                            'height': 'auto !important',
                            'min-height': '45px !important'
                        });
                        
                        $('#consumablesTable tbody tr td').css({
                            'height': 'auto !important',
                            'min-height': '45px !important',
                            'line-height': '1.5 !important',
                            'padding': '0.5rem !important'
                        });
                    }, 100);
                });
                
                console.log("DataTable initialized successfully");
                
            } catch (e) {
                console.error("Error initializing DataTable:", e);
                alert("Error initializing table features. Please check the console for details.");
            }
        });
    </script>
</head>
<body>
    <?php
    // Set the page title for the navigation bar
    $page_title = 'Consumable Materials';
    
    // Include the navigation bar template
    include 'nav_template.php';
    ?>

    <div class="container content-container">
        <!-- Action row will be created via JavaScript -->
        
        
        <?php if ($consumables): ?>
        <div class="table-responsive" style="width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch;">
            <table id="consumablesTable" class="table table-striped" style="width: 100%;">
                <thead>
                    <tr>
                        <th style="width: 50px;">ID</th>
                        <th style="width: 100px;">Item Type</th>
                        <th style="width: 200px;">Item Name</th>
                        <th style="width: 150px;">Normal Location</th>
                        <th style="width: 100px;">Whole Quantity</th>
                        <th style="width: 100px;">Reorder Threshold</th>
                        <th style="width: 100px;">Units (Whole)</th>
                        <th style="width: 100px;">Units (Part)</th>
                        <th style="width: 100px;">Qty Parts Per Whole</th>
                        <th style="width: 100px;">Total Part Units</th>
                        <th style="width: 200px;">Composition Description</th>
                        <th style="width: 120px;">Last Updated</th>
                        <th style="width: 140px; min-width: 140px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($consumables as $item): 
                        // Calculate the total part units
                        $totalPartUnits = 0;
                        if (!empty($item['whole_quantity']) && !empty($item['qty_parts_per_whole'])) {
                            $totalPartUnits = (int)$item['whole_quantity'] * (int)$item['qty_parts_per_whole'];
                        }
                        
                        // Format the date if it exists
                        $formattedDate = 'N/A';
                        if (!empty($item['last_updated'])) {
                            $date = new DateTime($item['last_updated']);
                            $formattedDate = $date->format('M j g:ia');
                        }
                        
                        // Format whole quantity with units
                        $wholeQuantityDisplay = 'N/A';
                        if (isset($item['whole_quantity'])) {
                            if (strtolower(trim($item['item_units_whole'])) === 'each') {
                                // If units is "each", use item type (make first letter lowercase for readability)
                                $type = lcfirst($item['item_type']);
                                $wholeQuantityDisplay = $item['whole_quantity'] . ' ' . ($item['whole_quantity'] == 1 ? $type : $type . 's');
                            } else {
                                // Otherwise use the units_whole
                                $units = $item['item_units_whole'];
                                $wholeQuantityDisplay = $item['whole_quantity'] . ' ' . ($item['whole_quantity'] == 1 ? $units : $units . 's');
                            }
                        }
                        
                        // Check if reordering is needed
                        $needsReorder = false;
                        if (isset($item['reorder_threshold']) && $item['reorder_threshold'] > 0 && 
                            isset($item['whole_quantity']) && $item['whole_quantity'] < $item['reorder_threshold']) {
                            $needsReorder = true;
                        }
                    ?>
                    <tr class="<?= $needsReorder ? 'reorder-needed' : '' ?>" data-needs-reorder="<?= $needsReorder ? 'true' : 'false' ?>">
                        <td><?= htmlspecialchars($item['id']) ?></td>
                        <td><?= htmlspecialchars($item['item_type']) ?></td>
                        <td><?= htmlspecialchars($item['item_name']) ?></td>
                        <td><?= htmlspecialchars($item['normal_location']) ?></td>
                        <td><?= htmlspecialchars($wholeQuantityDisplay) ?></td>
                        <td><?= htmlspecialchars($item['reorder_threshold']) ?></td>
                        <td><?= htmlspecialchars($item['item_units_whole']) ?></td>
                        <td><?= htmlspecialchars($item['item_units_part']) ?></td>
                        <td><?= htmlspecialchars($item['qty_parts_per_whole']) ?></td>
                        <td>
                            <?php if ($totalPartUnits > 0): ?>
                                <?= htmlspecialchars($totalPartUnits) ?> <?= htmlspecialchars($item['item_units_part']) ?>
                            <?php else: ?>
                                N/A
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($item['composition_description']) ?></td>
                        <td><?= htmlspecialchars($formattedDate) ?></td>
                        <td>
                            <div class="action-buttons-cell">
                                <a href="consumable_entry.php?id=<?= htmlspecialchars($item['id']) ?>" class="btn btn-sm btn-warning">
                                    <i class="bi bi-pencil"></i><span class="btn-text">Edit</span>
                                </a>
                                <a href="inventory_entry.php?consumable_id=<?= htmlspecialchars($item['id']) ?>" class="btn btn-sm btn-warning">
                                    <i class="bi bi-box-arrow-in-down"></i><span class="btn-text">Stock Chg</span>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
            <p>No consumable materials found. <a href="consumable_entry.php">Add one now</a>.</p>
        <?php endif; ?>
    </div>
    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>