<?php
// consumable_list.php
// Include authentication check
require_once 'auth_check.php';

require_once 'db_connection.php';

try {
    // Retrieve consumable materials along with their assigned normal location and most recent change date
    $stmt = $pdo->query("
        SELECT cm.id, cm.item_type, cm.item_name, cm.item_description,
               cm.diameter,
               loc.location_name AS normal_location,
               cm.item_units_whole, cm.item_units_part,
               cm.qty_parts_per_whole, cm.composition_description, cm.vendor,
               cm.whole_quantity, cm.reorder_threshold, cm.optimum_quantity,
               (SELECT MAX(ice.change_date) 
                FROM inventory_change_entries ice 
                WHERE ice.consumable_material_id = cm.id) AS last_updated,
               (SELECT COUNT(*) 
                FROM order_history oh 
                WHERE oh.consumable_id = cm.id 
                AND oh.status_id IN (2, 3)) as pending_orders,
               cm.image_thumb_50, cm.image_full
        FROM consumable_materials cm
        LEFT JOIN item_locations loc ON cm.normal_item_location = loc.id
        ORDER BY cm.item_name ASC
    ");
    $consumables = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Debug: Log the data structure
    error_log("Consumables data: " . print_r($consumables, true));
    
    // Trim whitespace from composition descriptions to prevent duplicate filter entries
    foreach ($consumables as &$c) {
        $c['composition_description'] = trim($c['composition_description']);
        $c['item_type'] = trim($c['item_type']);
    }
    unset($c);
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
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="assets/img/favicon.svg">
    <link rel="alternate icon" type="image/x-icon" href="assets/img/favicon.ico">
    <link rel="mask-icon" href="assets/img/favicon.svg" color="#0d6efd">
    <!-- jQuery (required for DataTables) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap CSS for responsiveness -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css" rel="stylesheet">

    <link href="assets/css/jquery.dataTables.yadcf.css" rel="stylesheet">
    
    <!-- DataTables Core JS -->
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <!-- yadcf JS for column filters -->
    <script src="js/jquery.dataTables.yadcf.js"></script>
    
    <!-- JSZip for Excel export -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    
    <!-- Bootstrap JS Bundle - Move to head -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
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
        
        /* Highlight class for flashing effect - higher specificity to override reorder styles */
        .highlight-row td,
        .table-striped > tbody > tr.highlight-row td,
        .table-striped > tbody > tr.reorder-needed.highlight-row td,
        #consumablesTable tbody tr.highlight-row td {
            background-color: #ffb6c1 !important; /* Pink background */
            z-index: 2 !important;
            position: relative !important;
            transition: none !important;
        }
        
        /* Ensure highlighting works with DataTables striping */
        .table-striped > tbody > tr.reorder-needed:nth-of-type(odd) td,
        .table-striped > tbody > tr.reorder-needed:nth-of-type(even) td {
            background-color: #fff3cd !important;
            transition: none !important;
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
            padding: 0.2rem 0 0 0rem !important;
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
            table-layout: fixed !important;
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
        
        /* Sticky filters row (second header row) */
        #consumablesTable thead tr.sticky-filters {
            position: sticky;
            top: 0;
            z-index: 9;
            background-color: #fff;
        }
        
        /* Action row sticky under navbar */
        .action-row {
            position: sticky;
           
            z-index: 900;
            background-color: #fff;
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
                /* width: auto !important;
                min-width: auto !important; */
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
                padding: 0px 5px !important;
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
        
        /* Compact yadcf dropdown filters */
        .yadcf-filter-wrapper select.yadcf-filter {
            font-size: 0.8rem;      /* smaller text */
            padding: 2px 5px;       /* tighter padding */
            height: auto;           /* let height follow font */
            line-height: 1.2;
        }

        .yadcf-filter-wrapper {
            margin: 0 4px 4px 0;    /* reduce outer spacing */
        }

        /* Place yadcf "X" clear button before the select */
        .yadcf-filter-wrapper{
            display:inline-flex;       /* line up select + X */
            align-items:center;        /* vertical centring */
        }

        .yadcf-filter-reset-button{
            order:-1;                  /* move before select */
            margin-right:4px;          /* space between X and select */
            margin-left:0;
            font-size:0.8rem;          /* optional: match reduced filter font */
        }

        @media (max-width: 991px) {
            #consumablesTable th,
            #consumablesTable td {
                min-width: 50px !important;
            }
        }
    </style>
    <script>
        $(document).ready(function() {
            var table;
            
            try {
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
                            row.css({
                                'height': 'auto',
                                'min-height': '45px'
                            });
                            row.find('td').css({
                                'height': 'auto',
                                'min-height': '45px',
                                'line-height': '1.5',
                                'padding': '0.5rem',
                                'background-color': '#fff3cd',
                                'font-weight': 'bold'
                            });
                        } else {
                            row.removeClass('reorder-needed');
                            row.css({
                                'height': 'auto',
                                'min-height': '45px'
                            });
                            row.find('td').css({
                                'height': 'auto',
                                'min-height': '45px',
                                'line-height': '1.5',
                                'padding': '0.5rem'
                            });
                        }
                    });
                }

                // Initialize DataTables
                table = $('#consumablesTable').DataTable({
                    scrollY: '100%',
                    scrollX: true,
                    scrollCollapse: true,
                    paging: false,
                    order: [[3, 'asc']], // Sort by name column by default
                    processing: true,
                    data: <?php echo json_encode($consumables); ?>,
                    columns: [
                        { 
                            data: null,
                            orderable: false,
                            render: function(data, type, row) {
                                if (!row.image_thumb_50) {
                                    return '<div class="text-muted">No image</div>';
                                }
                                return `<img src="${row.image_thumb_50}" 
                                          alt="Item thumbnail" 
                                          class="img-thumbnail cursor-pointer" 
                                          style="max-width: 50px; cursor: pointer;"
                                          data-full-image="${row.image_full}"
                                          onclick="showFullImage(this)">`;
                            }
                        }, // Image column
                        { data: 'id', visible: false }, // ID (hidden by default)
                        { data: 'item_type', visible: true, orderable: false }, // Type (shown by default)
                        { data: 'item_name' }, // Name
                        { data: 'item_description', visible: false }, // Description
                        { data: 'diameter', orderable: false,render: function (data) {
                            if (data === null || data === '' || data === undefined) return '';
                            return parseFloat(data).toString();   // removes trailing zeroes
                        } },  // New 'diameter' column added as per plan
                        { data: 'normal_location' }, // Location, now shifted
                        { data: 'whole_quantity', orderable: true }, // Whole Qty with units
                        { data: 'reorder_threshold', orderable: false }, // Reorder Threshold
                        { data: 'composition_description', visible: true, orderable: false }, // Material (displayed default)
                        { data: 'vendor', visible: false }, // Vendors (hidden default)
                        { data: null, orderable: false } // Actions column
                    ],
                    createdRow: function(row, data, dataIndex) {
                        $(row).attr('id', 'consumable-' + data.id);
                    },
                    columnDefs: [
                        { width: "90px", targets: 0, className: 'text-center', title: 'Img' }, // Image column
                        { width: "30px", targets: 1, className: 'dt-body-left' }, // ID
                        { width: "140px", targets: 2, className: 'dt-body-center' }, // Type
                        { width: "230px", targets: 3, className: 'dt-body-left' }, // Name
                        { width: "200px", targets: 4, className: 'dt-body-left' }, // Description
                        { width: "100px", targets: 5, className: 'dt-body-center', title: 'Size' }, // Diameter
                        { width: "80px", targets: 6, className: 'dt-body-center', title: 'Location' }, // Location
                        { width: "100px", targets: 7, className: 'dt-body-left' }, // Whole Qty
                        { width: "80px", targets: 8, className: 'dt-body-left', title: 'Re-up' }, // Reorder Threshold
                        { width: "130px", targets: 9, className: 'dt-body-left' }, // Material
                        { width: "120px", targets: 10, className: 'dt-body-left' }, // Vendors
                        { width: "200px", targets: 11, className: 'text-center' }, // Actions column
                        {
                            targets: 7, // Whole Qty column
                            render: function(data, type, row) {
                                if (data === null || data === undefined) {
                                    return '0';
                                }
                                return `${data} ${row.item_units_whole || ''}`;
                            }
                        },
                        {
                            targets: 8, // Reorder Threshold column
                            render: function(data, type, row) {
                                let html = data;
                                if (row.whole_quantity < row.reorder_threshold && row.pending_orders > 0) {
                                    html += `<i class="bi bi-cart-check text-primary ms-1" title="${row.pending_orders} pending order(s)"></i>`;
                                }
                                return html;
                            }
                        },
                        {
                            targets: 11, // Actions column
                            render: function(data, type, row) {
                                return `<div class="action-buttons-cell">
                                    <a href="consumable_entry.php?id=${row.id}" class="btn btn-sm btn-warning">
                                        <i class="bi bi-pencil"></i><span class="btn-text">Edit</span>
                                    </a>
                                    <a href="inventory_entry.php?consumable_id=${row.id}" class="btn btn-sm btn-warning">
                                        <i class="bi bi-box-arrow-in-down"></i><span class="btn-text">Stock Chg</span>
                                    </a>
                                    <a href="inventory.php?id=${row.id}" class="btn btn-sm btn-secondary" title="View Change Log">
                                        <i class="bi bi-clock-history"></i>
                                    </a>
                                </div>`;
                            }
                        }
                    ],
                    info: true,
                    responsive: true,
                    autoWidth: false,
                    dom: 'rt<"d-flex justify-content-between"ip>',
                    scrollY: calculateTableHeight(),
                    initComplete: function() {
                        // Debug: Log the data structure
                        console.log('Data structure:', this.api().data().toArray());
                        
                        // Debug: Log image paths
                        this.api().rows().every(function(rowIdx, data) {
                            console.log('Row ' + rowIdx + ' image data:', {
                                thumb_50: data.image_thumb_50,
                                full: data.image_full
                            });
                        });
                        
                        // Force column width recalculation after paint
                        setTimeout(() => table.columns.adjust(), 0);
                        
                        // Ensure header and body widths match
                        var headerTable = $(this).closest('.dataTables_wrapper').find('.dataTables_scrollHead table');
                        var bodyTable = $(this).closest('.dataTables_wrapper').find('.dataTables_scrollBody table');
                        
                        // Set table layout to fixed
                        headerTable.css('table-layout', 'fixed');
                        bodyTable.css('table-layout', 'fixed');
                        
                        // Match column widths
                        headerTable.find('th').each(function(index) {
                            var width = $(this).outerWidth();
                            bodyTable.find('td:nth-child(' + (index + 1) + ')').width(width);
                        });

                        // Set initial active state for visible columns
                        this.api().columns().every(function(index) {
                            if (this.visible()) {
                                $('.dropdown-item[data-column="' + index + '"]').addClass('active');
                            } else {
                                $('.dropdown-item[data-column="' + index + '"]').removeClass('active');
                            }
                        });

                        // Update dynamic offsets for navbar & action row
                        function updateOffsets() {
                            const navH = $('.navbar').outerHeight() || 0;
                            const actH = $('#consumablesActionRow').outerHeight() || 0;
                            document.documentElement.style.setProperty('--navbar-height', navH + 'px');
                            document.documentElement.style.setProperty('--action-height', actH + 'px');
                        }

                        updateOffsets();

                        $(window).on('resize', updateOffsets);
                        this.api().on('draw', updateOffsets);
                    },
                    drawCallback: function() {
                        // Ensure sticky filters row position is updated on each draw
                        const hdrH = $('#consumablesTable thead tr').first().outerHeight();
                        $('#consumablesTable thead tr').eq(1).addClass('sticky-filters');
                        
                        // Check for scroll_to parameter in URL
                        const urlParams = new URLSearchParams(window.location.search);
                        const scrollToId = urlParams.get('scroll_to');
                        
                        console.log('Scroll debug - URL parameter:', scrollToId);
                        
                        if (scrollToId) {
                            // Use the row ID directly instead of searching through columns
                            const $targetRow = $(`#consumable-${scrollToId}`);
                            
                            console.log('Scroll debug - Target row found:', $targetRow.length > 0);
                            console.log('Scroll debug - Target row ID:', `#consumable-${scrollToId}`);
                            
                            if ($targetRow.length) {
                                const $container = $('.dataTables_scrollBody');
                                const headerHeight = $('.dataTables_scrollHead').outerHeight();
                                const rowPosition = $targetRow.position().top;
                                const scrollPosition = rowPosition - headerHeight - 50;
                                
                                console.log('Scroll debug - Container height:', $container.height());
                                console.log('Scroll debug - Header height:', headerHeight);
                                console.log('Scroll debug - Row position:', rowPosition);
                                console.log('Scroll debug - Scroll position:', scrollPosition);
                                
                                $container.animate({
                                    scrollTop: scrollPosition
                                }, 500, function() {
                                    console.log('Scroll debug - Animation completed');
                                    let flashCount = 0;
                                    const maxFlashes = 6;
                                    
                                    function flashRow() {
                                        if (flashCount < maxFlashes) {
                                            if (flashCount % 2 === 0) {
                                                $targetRow.addClass('highlight-row');
                                                $targetRow.find('td').css({
                                                    'background-color': '#ffb6c1',
                                                    'transition': 'none'
                                                });
                                            } else {
                                                $targetRow.removeClass('highlight-row');
                                                if ($targetRow.hasClass('reorder-needed')) {
                                                    $targetRow.find('td').css({
                                                        'background-color': '#fff3cd',
                                                        'transition': 'none'
                                                    });
                                                } else {
                                                    $targetRow.find('td').css({
                                                        'background-color': '',
                                                        'transition': 'none'
                                                    });
                                                }
                                            }
                                            flashCount++;
                                            setTimeout(flashRow, 250);
                                        } else {
                                            if ($targetRow.hasClass('reorder-needed')) {
                                                $targetRow.find('td').css({
                                                    'background-color': '#fff3cd',
                                                    'transition': 'none'
                                                });
                                            }
                                        }
                                    }
                                    flashRow();
                                });
                            }
                        }
                        
                        $('.dataTables_scrollHead').css('margin-bottom', '5px');
                        $('.dataTables_scrollBody').css('padding-top', '0px');
                        $(this).css('width', '100%');
                        
                        applyHighlighting();
                        
                        // Force column width recalculation after each draw (post-paint)
                        setTimeout(() => table.columns.adjust(), 0);
                        
                        // Ensure header and body alignment
                        var headerTable = $(this).closest('.dataTables_wrapper').find('.dataTables_scrollHead table');
                        var bodyTable = $(this).closest('.dataTables_wrapper').find('.dataTables_scrollBody table');
                        
                        headerTable.css('table-layout', 'fixed');
                        bodyTable.css('table-layout', 'fixed');
                        
                        // Match widths after any table redraw
                        headerTable.find('th').each(function(index) {
                            var width = $(this).outerWidth();
                            bodyTable.find('td:nth-child(' + (index + 1) + ')').width(width);
                        });
                        
                        // Add scroll to top button to the existing DataTables info element
                        if (!$('#scrollToTopBtn').length) {
                            $('.dataTables_info').append('<span id="scrollToTopBtn" class="ms-3" style="cursor: pointer;"><i class="bi bi-arrow-up-circle-fill" style="font-size: 1.5rem;"></i></span>');
                            
                            // Add click event to scroll to top
                            $('#scrollToTopBtn').on('click', function() {
                                $('.dataTables_scrollBody').animate({
                                    scrollTop: 0
                                }, 500);
                            });
                        }
                    }
                });

                // Initialize yadcf filters (simple select, no select2) for Type, Diameter, Material
                if (typeof yadcf !== 'undefined') {
                    yadcf.init(table, [
                        {
                            column_number: 2, // Type
                            filter_type: 'select',
                            filter_default_label: 'Type...'
                        },
                        {
                            column_number: 5, // Diameter/Size
                            filter_type: 'select',
                            filter_default_label: 'Size...',
                            filter_match_mode: 'exact',
                            sort_as: 'custom',
                            sort_as_custom_func: function(a, b) {
                                return parseFloat(a) - parseFloat(b);
                            }
                        },
                        {
                            column_number: 9, // Material
                            filter_type: 'select',
                            filter_default_label: 'Material...'
                        }
                    ]);

                    // Sort diameter filter numerically
                    function sortDiameterFilter() {
                        const $sel = $('#yadcf-filter--consumablesTable-5');
                        if (!$sel.length) return;
                        const $opts = $sel.find('option').not(':first'); // exclude placeholder
                        $opts.sort(function(a, b) {
                            return parseFloat(a.value) - parseFloat(b.value);
                        });
                        $sel.append($opts);
                    }

                    // run once immediately after filters are created
                    setTimeout(sortDiameterFilter, 0);

                    // keep sorted on every table redraw
                    table.on('draw', sortDiameterFilter);
                } else {
                    console.error('yadcf failed to load');
                }

                // Create action buttons after table is initialized
                var actionContainer = $('#consumablesActionRow');
                actionContainer.empty();
                var leftButtons = $('<div class="button-group left-buttons"></div>');
                leftButtons.append($('<a href="consumable_entry.php" class="btn btn-primary btn-mobile-icon"><i class="bi bi-plus-lg"></i><span class="btn-text"> Add New Material</span></a>'));
                
                // Create column visibility dropdown
                var columnToggleHtml = `
                    <div class="dropdown d-inline-block">
                        <button class="btn btn-secondary dropdown-toggle btn-mobile-icon" type="button" id="columnToggleButton" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-layout-three-columns"></i><span class="btn-text"> Show/Hide Cols</span>
                        </button>
                        <div class="dropdown-menu p-2" aria-labelledby="columnToggleButton" style="min-width: 200px;">
                            <div><a class="dropdown-item active" data-column="0" href="#">Image</a></div>
                            <div><a class="dropdown-item" data-column="1" href="#">ID</a></div>
                            <div><a class="dropdown-item" data-column="2" href="#">Type</a></div>
                            <div><a class="dropdown-item active" data-column="3" href="#">Name</a></div>
                            <div><a class="dropdown-item" data-column="4" href="#">Description</a></div>
                            <div><a class="dropdown-item active" data-column="5" href="#">Diameter</a></div>
                            <div><a class="dropdown-item active" data-column="6" href="#">Location</a></div>
                            <div><a class="dropdown-item active" data-column="7" href="#">Qty</a></div>
                            <div><a class="dropdown-item active" data-column="8" href="#">Reorder Threshold</a></div>
                            <div><a class="dropdown-item active" data-column="9" href="#">Material</a></div>
                            <div><a class="dropdown-item" data-column="10" href="#">Vendors</a></div>
                            <div><a class="dropdown-item active" data-column="11" href="#">Actions</a></div>
                        </div>
                    </div>
                `;
                leftButtons.append($(columnToggleHtml));

                // Handle column visibility toggle
                $(document).on('click', '.dropdown-menu .dropdown-item', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    var columnIndex = parseInt($(this).data('column'));
                    var column = table.column(columnIndex);
                    var isVisible = column.visible();
                    column.visible(!isVisible);
                    $(this).toggleClass('active');
                    table.columns.adjust().draw(false);
                });
                
                // Create search group
                var searchGroup = $('<div class="search-container"></div>');
                var searchInput = $('<input type="search" class="form-control" placeholder="Search...">');
                searchGroup.append(searchInput);
                
                // Bind search input to DataTables search
                searchInput.on('keyup', function() {
                    table.search(this.value).draw();
                });
                
                // Create right button group with all buttons in one container
                var rightButtons = $('<div class="button-group right-buttons"></div>');
                // Add Smart Entry button directly to the right buttons group
                rightButtons.append($('<a href="natural_language_inventory.php" class="btn btn-success btn-mobile-icon"><i class="bi bi-magic"></i><span class="btn-text"> Smart Entry</span></a>'));
                rightButtons.append($('<button class="btn btn-info btn-mobile-icon" id="printButton"><i class="bi bi-printer"></i><span class="btn-text"> Print</span></button>'));
                rightButtons.append($('<button class="btn btn-success btn-mobile-icon" id="excelButton"><i class="bi bi-file-excel"></i><span class="btn-text"> Excel</span></button>'));
                
                // Combine all groups
                actionContainer.append(leftButtons);
                actionContainer.append(searchGroup);
                actionContainer.append(rightButtons);
                
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
                        $('#consumablesTable thead th').css({
                            'margin': '0 4px !important'
                        });
                        
                        $('#consumablesTable tbody tr td').css({
                            'height': 'auto !important',
                            'min-height': '45px !important',
                            'line-height': '1.5 !important',
                            'padding': '0.2rem 0 0.2 0 !important',
                            'display': 'inline-block !important'
                        });
                    }, 100);
                });
                
                console.log("DataTable initialized successfully");
                
                // Re-adjust columns on window resize to keep header aligned
                $(window).on('resize', function() {
                    table.columns.adjust();
                });
                
                // Add print functionality
                $('#printButton').on('click', function() {
                    // Create a new window for printing
                    var printWindow = window.open('', '_blank');
                    
                    // Get the table HTML
                    var tableHtml = $('#consumablesTable').clone();
                    
                    // Remove the action buttons column
                    tableHtml.find('th:last-child, td:last-child').remove();
                    
                    // Create the print content
                    var printContent = `
                        <!DOCTYPE html>
                        <html>
                        <head>
                            <title>Consumable Materials List - Print</title>
                            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
                            <style>
                                body { padding: 20px; }
                                .table { width: 100%; }
                                .table th { background-color: #f8f9fa; }
                                .reorder-needed td { background-color: #fff3cd !important; }
                                @media print {
                                    .table th { background-color: #f8f9fa !important; -webkit-print-color-adjust: exact; }
                                    .reorder-needed td { background-color: #fff3cd !important; -webkit-print-color-adjust: exact; }
                                }
                            </style>
                        </head>
                        <body>
                            <h2 class="mb-4">Consumable Materials List</h2>
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
                    var csvContent = "ID,Type,Name,Description,Location,Material,Vendors,Units,Quantity,Reorder Point,Last Updated\n";
                    
                    function escapeCsv(value) {
                        if (value === null || value === undefined) return '';
                        value = String(value).replace(/"/g, '""');
                        if (value.includes(',')) {
                            value = '"' + value + '"';
                        }
                        return value;
                    }

                    data.forEach(function(row) {
                        var id           = row.id;
                        var type         = row.item_type || '';
                        var name         = row.item_name || '';
                        var description  = row.item_description || '';
                        var location     = row.normal_location || '';
                        var material     = row.composition_description || '';
                        var vendor       = row.vendor || '';
                        var units        = row.item_units_whole || '';
                        var quantity     = row.whole_quantity || '0';
                        var reorderPoint = row.reorder_threshold || '0';
                        var lastUpdated  = row.last_updated || row.updated_at || '';

                        csvContent += `${id},${escapeCsv(type)},${escapeCsv(name)},${escapeCsv(description)},${escapeCsv(location)},${escapeCsv(material)},${escapeCsv(vendor)},${escapeCsv(units)},${quantity},${reorderPoint},${lastUpdated}\n`;
                    });
                    
                    // Create a blob and download it
                    var blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
                    var link = document.createElement('a');
                    var url = URL.createObjectURL(blob);
                    
                    link.setAttribute('href', url);
                    link.setAttribute('download', 'consumable_materials.csv');
                    link.style.visibility = 'hidden';
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                });
                
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
        <!-- Toolbar / filters row -->
        <div id="consumablesActionRow" class="action-row"></div>
        
        <?php if ($consumables): ?>
        <div class="table-responsive" style="width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch;">
            <table id="consumablesTable" class="table table-striped compact" style="width: 100%;">
                <thead>
                    <tr>
                        <th></th>
                        <th>ID</th>
                        <th>Type</th>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Diameter</th>
                        <th>Location</th>
                        <th>Qty</th>
                        <th>Reorder Threshold</th>
                        <th>Material</th>
                        <th>Vendors</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($consumables as $consumable): ?>
                    <tr id="consumable-<?= htmlspecialchars($consumable['id']) ?>">
                        <td>
                            <?php if (!empty($consumable['image_thumb_50'])): ?>
                                <img src="<?= htmlspecialchars($consumable['image_thumb_50']) ?>" 
                                     alt="Item thumbnail" 
                                     class="img-thumbnail cursor-pointer" 
                                     style="max-width: 50px; cursor: pointer;"
                                     data-full-image="<?= htmlspecialchars($consumable['image_full']) ?>"
                                     onclick="showFullImage(this)">
                            <?php else: ?>
                                <div class="text-muted">No image</div>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($consumable['id']) ?></td>
                        <td><?= htmlspecialchars($consumable['item_type']) ?></td>
                        <td><?= htmlspecialchars($consumable['item_name']) ?></td>
                        <td><?= htmlspecialchars($consumable['item_description']) ?></td>
                        <td><?= htmlspecialchars($consumable['diameter']) ?></td>
                        <td><?= htmlspecialchars($consumable['normal_location']) ?></td>
                        <td><?= htmlspecialchars($consumable['whole_quantity']) ?> <?= htmlspecialchars($consumable['item_units_whole']) ?></td>
                        <td><?= htmlspecialchars($consumable['reorder_threshold']) ?>
                            <?php if ($consumable['whole_quantity'] < $consumable['reorder_threshold'] && $consumable['pending_orders'] > 0): ?>
                                <i class="bi bi-cart-check text-primary ms-1" title="<?php echo $consumable['pending_orders']; ?> pending order(s)"></i>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($consumable['composition_description']) ?></td>
                        <td><?= htmlspecialchars($consumable['vendor'] ?? '') ?></td>
                        <td>
                            <div class="action-buttons-cell">
                                <a href="consumable_entry.php?id=<?= htmlspecialchars($consumable['id']) ?>" class="btn btn-sm btn-warning">
                                    <i class="bi bi-pencil"></i><span class="btn-text">Edit</span>
                                </a>
                                <a href="inventory_entry.php?consumable_id=<?= htmlspecialchars($consumable['id']) ?>" class="btn btn-sm btn-warning">
                                    <i class="bi bi-box-arrow-in-down"></i><span class="btn-text">Stock Chg</span>
                                </a>
                                <a href="inventory.php?id=<?= htmlspecialchars($consumable['id']) ?>" class="btn btn-sm btn-secondary" title="View Change Log">
                                    <i class="bi bi-clock-history"></i>
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

    <!-- Image Modal -->
    <div class="modal fade" id="imageModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-fullscreen">
            <div class="modal-content">
                <div class="modal-body p-0">
                    <img src="" id="modalImage" class="img-fluid" style="width: 100%; height: 100%; object-fit: contain; cursor: pointer;">
                </div>
            </div>
        </div>
    </div>

    <script>
    function showFullImage(imgElement) {
        const fullImageUrl = imgElement.dataset.fullImage;
        const modal = new bootstrap.Modal(document.getElementById('imageModal'));
        const modalImage = document.getElementById('modalImage');
        
        modalImage.src = fullImageUrl;
        modal.show();
    }

    // Close modal when clicking anywhere on the image
    document.getElementById('modalImage').addEventListener('click', function() {
        bootstrap.Modal.getInstance(document.getElementById('imageModal')).hide();
    });
    </script>
</body>
</html>