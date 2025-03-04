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
            position: sticky;
            top: 0px; /* Already set to 0px */
            z-index: 10;
            background-color: #fff;
        }
        
        /* Fix for first row being covered */
        .dataTables_wrapper .dataTables_scrollBody {
            padding-top: 0px !important; /* Changed from 00px to 0px for clarity */
            margin-top: 0px !important;
        }
        
        /* Additional styling for DataTables */
        #consumablesTable_wrapper {
            margin-top: 0px;
            width: 100% !important;
            overflow-x: auto !important;
        }
        
        /* Fix for navigation bar in Opera */
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
            height: 60px; /* Fixed height for the navigation bar */
        }
        
        /* Ensure content doesn't get hidden under the navigation bar */
        .content-container {
            padding-top: 80px; /* Increased padding to prevent overlap with navigation bar */
        }
        
        .action-row {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 5px; /* Reduce gap between elements */
            margin-bottom: 5px !important; /* Reduce bottom margin */
            justify-content: flex-start !important; /* Align items to the left */
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
        }
        
        /* Custom table controls container */
        #custom-table-controls {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        /* Style for the search box */
        #custom-search {
            display: inline-flex;
            align-items: center;
        }
        
        #custom-search label {
            margin-right: 5px;
            margin-bottom: 0;
        }
        
        #custom-search input {
            padding: 4px 8px;
            border: 1px solid #ced4da;
            border-radius: 4px;
        }
        
        /* Make item name column wider */
        #consumablesTable th:nth-child(3),
        #consumablesTable td:nth-child(3) {
            min-width: 200px;
            width: 20%;
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
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .action-row {
                flex-direction: column;
                align-items: flex-start;
            }
            
            #custom-table-controls {
                margin-top: 10px;
                width: 100%;
                flex-wrap: wrap;
            }
            
            #custom-search {
                width: 100%;
                margin-top: 10px;
            }
            
            #custom-search input {
                width: 100%;
            }
            
            .dt-buttons {
                margin-top: 10px;
                margin-left: 0;
            }
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
            margin-top: 5px !important;
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
            .dataTables_wrapper {
                overflow-x: auto !important;
                width: 100% !important;
            }
            
            .table-responsive {
                overflow-x: auto !important;
                -webkit-overflow-scrolling: touch;
            }
            
            .container.content-container {
                max-width: 100% !important;
                padding-right: 5px !important;
                padding-left: 5px !important;
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
    </style>
    <script>
        $(document).ready(function() {
            // Check DataTables version
            if ($.fn.dataTable) {
                console.log("DataTables version:", $.fn.dataTable.version);
            } else {
                console.error("DataTables is not loaded!");
            }
            
            try {
                // Create a container for our action buttons
                var actionContainer = $('<div class="action-row mb-3 d-flex align-items-center"></div>');
                
                // Create an Add Material button
                var addButton = $('<a href="consumable_entry.php" class="btn btn-primary">Add New Material</a>');
                addButton.appendTo(actionContainer);
                
                // Create buttons container
                var buttonsContainer = $('<div class="buttons-container ms-2 btn-group"></div>');
                
                // Add Show/Hide Columns button
                var colvisButton = $('<button class="btn btn-secondary">Show/Hide Columns</button>');
                colvisButton.on('click', function(e) {
                    e.stopPropagation();
                    
                    // Remove any existing dropdowns
                    $('.column-visibility-dropdown').remove();
                    
                    // Create a dropdown with column visibility options
                    var dropdown = $('<div class="dropdown-menu column-visibility-dropdown"></div>');
                    
                    // Add options for each column
                    table.columns().every(function(index) {
                        var column = this;
                        var visible = column.visible();
                        var name = $(column.header()).text();
                        
                        var item = $('<a class="dropdown-item"></a>');
                        item.text(name);
                        if (visible) {
                            item.addClass('active');
                        }
                        
                        item.on('click', function(e) {
                            e.stopPropagation();
                            column.visible(!visible);
                            $(this).toggleClass('active');
                        });
                        
                        dropdown.append(item);
                    });
                    
                    // Position the dropdown relative to the button
                    var buttonPos = $(this).offset();
                    dropdown.css({
                        position: 'absolute',
                        top: buttonPos.top + $(this).outerHeight(),
                        left: buttonPos.left,
                        zIndex: 1000,
                        display: 'block',
                        backgroundColor: '#fff',
                        border: '1px solid rgba(0,0,0,.15)',
                        borderRadius: '.25rem',
                        padding: '.5rem 0',
                        minWidth: '200px'
                    });
                    
                    // Add the dropdown to the document
                    $('body').append(dropdown);
                    
                    // Close the dropdown when clicking outside
                    $(document).one('click', function() {
                        dropdown.remove();
                    });
                    
                    return false;
                });
                buttonsContainer.append(colvisButton);
                
                // Add Print button
                var printButton = $('<button class="btn btn-sm btn-info ms-1"><i class="bi bi-printer"></i></button>');
                printButton.attr('title', 'Print');
                printButton.on('click', function() {
                    // Create a new window for printing
                    var printWindow = window.open('', '_blank');
                    
                    // Create the print content
                    var printContent = '<html><head><title>Consumable Materials</title>';
                    
                    // Add styles
                    printContent += '<style>';
                    printContent += 'body { font-family: Arial, sans-serif; }';
                    printContent += 'table { border-collapse: collapse; width: 100%; }';
                    printContent += 'th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }';
                    printContent += 'th { background-color: #f2f2f2; }';
                    printContent += '.reorder-needed td { background-color: #fff3cd; font-weight: bold; }';
                    printContent += 'tr { height: 100%; line-height: inherit; }';
                    printContent += '@media print { .no-print { display: none; } }';
                    printContent += '</style>';
                    
                    // Close the head and open the body
                    printContent += '</head><body>';
                    
                    // Add a title
                    printContent += '<h1>Consumable Materials</h1>';
                    
                    // Add a print date
                    var now = new Date();
                    printContent += '<p>Printed on: ' + now.toLocaleDateString() + ' ' + now.toLocaleTimeString() + '</p>';
                    
                    // Create a table with only visible columns
                    printContent += '<table>';
                    
                    // Add the header row
                    printContent += '<thead><tr>';
                    $('#consumablesTable thead th:visible').each(function() {
                        printContent += '<th>' + $(this).text() + '</th>';
                    });
                    printContent += '</tr></thead>';
                    
                    // Add the body rows
                    printContent += '<tbody>';
                    $('#consumablesTable tbody tr:visible').each(function() {
                        var needsReorder = $(this).hasClass('reorder-needed');
                        printContent += needsReorder ? '<tr class="reorder-needed">' : '<tr>';
                        
                        $(this).find('td:visible').each(function() {
                            printContent += '<td>' + $(this).html() + '</td>';
                        });
                        printContent += '</tr>';
                    });
                    printContent += '</tbody>';
                    
                    // Close the table
                    printContent += '</table>';
                    
                    // Add a print button
                    printContent += '<div class="no-print" style="margin-top: 20px;">';
                    printContent += '<button onclick="window.print()">Print</button>';
                    printContent += '<button onclick="window.close()">Close</button>';
                    printContent += '</div>';
                    
                    // Close the body and html
                    printContent += '</body></html>';
                    
                    // Write the content to the new window
                    printWindow.document.open();
                    printWindow.document.write(printContent);
                    printWindow.document.close();
                    
                    // Focus the new window
                    printWindow.focus();
                });
                buttonsContainer.append(printButton);
                
                // Add Excel button
                var excelButton = $('<button class="btn btn-sm btn-success ms-1"><i class="bi bi-file-excel"></i></button>');
                excelButton.attr('title', 'Export to Excel');
                excelButton.on('click', function() {
                    // Create a CSV string with BOM for Excel
                    var csv = ['\ufeff']; // Add BOM for Excel
                    
                    // Get all visible headers
                    var headers = [];
                    $('#consumablesTable thead th:visible').each(function() {
                        headers.push('"' + $(this).text().replace(/"/g, '""') + '"');
                    });
                    csv.push(headers.join(','));
                    
                    // Get all visible rows and cells
                    $('#consumablesTable tbody tr:visible').each(function() {
                        var row = [];
                        $(this).find('td:visible').each(function() {
                            row.push('"' + $(this).text().replace(/"/g, '""') + '"');
                        });
                        csv.push(row.join(','));
                    });
                    
                    // Download the CSV file
                    var csvString = csv.join('\n');
                    var filename = 'consumable_materials_' + new Date().toISOString().slice(0, 10) + '.csv';
                    
                    // Create a blob with the CSV data
                    var blob = new Blob([csvString], { type: 'text/csv;charset=utf-8;' });
                    
                    // Create a download link
                    if (navigator.msSaveBlob) { // IE 10+
                        navigator.msSaveBlob(blob, filename);
                    } else {
                        var link = document.createElement('a');
                        if (link.download !== undefined) { // Feature detection
                            // Create a URL for the blob
                            var url = URL.createObjectURL(blob);
                            link.setAttribute('href', url);
                            link.setAttribute('download', filename);
                            link.style.display = 'none';
                            document.body.appendChild(link);
                            link.click();
                            document.body.removeChild(link);
                            URL.revokeObjectURL(url);
                        } else {
                            // Fallback for older browsers
                            window.open('data:text/csv;charset=utf-8,' + encodeURIComponent(csvString));
                        }
                    }
                });
                buttonsContainer.append(excelButton);
                
                // Add the buttons container to the action row
                buttonsContainer.appendTo(actionContainer);
                
                // Create a custom search input
                var searchContainer = $('<div class="search-container"></div>');
                var searchInput = $('<input type="search" class="form-control form-control-sm" placeholder="Search...">');
                searchInput.on('keyup', function() {
                    table.search(this.value).draw();
                });
                searchContainer.append(searchInput);
                
                // Position the search container closer to the buttons
                searchContainer.css({
                    'margin-left': '10px',
                    'margin-right': 'auto'
                });
                
                // Add the search container to the action row
                searchContainer.appendTo(actionContainer);
                
                // Insert the action container before the table
                actionContainer.insertBefore('#consumablesTable');
                
                // Initialize DataTables
                var table = $('#consumablesTable').DataTable({
                    scrollY: '60vh',
                    scrollCollapse: true,
                    scrollX: true, // Enable horizontal scrolling if needed
                    paging: false,
                    ordering: true,
                    info: true,
                    responsive: true,
                    autoWidth: false, // Disable auto width to allow manual column width control
                    dom: 'rt<"d-flex justify-content-between"ip>', // Remove 'f' to hide the default search
                    columnDefs: [
                        // Hide Units (Whole), Units (Part), and Qty Parts Per Whole columns on load
                        { visible: false, targets: [0, 6, 7, 8, 10, 11] }, // Column indices: 6=Units (Whole), 7=Units (Part), 8=Qty Parts Per Whole
                        // Set width for the Actions column
                        { width: "140px", targets: -1 }, // Last column (Actions)
                        // Set widths for other columns to ensure proper alignment
                        { width: "50px", targets: 0 }, // ID column
                        { width: "100px", targets: 1 }, // Item Type
                        { width: "200px", targets: 2 }, // Item Name
                        { width: "150px", targets: 3 }, // Normal Location
                        { width: "100px", targets: 4 }, // Whole Quantity
                        { width: "100px", targets: 5 }, // Reorder Threshold
                        { width: "100px", targets: 9 }, // Total Part Units
                        { width: "200px", targets: 10 }, // Composition Description
                        { width: "120px", targets: 11 } // Last Updated
                    ],
                    drawCallback: function() {
                        // Add extra space after the header is drawn
                        $('.dataTables_scrollHead').css('margin-bottom', '5px');
                        $('.dataTables_scrollBody').css('padding-top', '0px');
                        
                        // Ensure the table takes full width
                        $(this).css('width', '100%');
                        
                        // Fix for the first row being covered - remove extra spacing
                        $('.dataTables_scrollBody tbody tr:first-child').css({
                            'margin-top': '0px',
                            'border-top': '0px solid transparent',
                            'position': 'relative',
                            'top': '0px'
                        });
                        
                        // Ensure all rows have consistent height
                        $('.dataTables_scrollBody tbody tr').css({
                            'height': 'auto',
                            'min-height': '45px',
                            'position': 'relative',
                            'top': '0px'
                        });
                        
                        // Force alignment between header and body
                        $('.dataTables_scrollHeadInner').css('width', '100%');
                        $('.dataTables_scrollHeadInner table').css('width', '100%');
                        
                        // Apply highlighting to ensure reorder rows are properly styled
                        applyHighlighting();
                    }
                });
                
                // Hide any default DataTables search boxes
                $('.dataTables_filter').hide();
                
                // Apply row highlighting based on data attribute
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
                        
                        // Check if reordering is needed
                        $needsReorder = false;
                        if (isset($item['reorder_threshold']) && $item['reorder_threshold'] > 0 && 
                            isset($item['whole_quantity']) && $item['whole_quantity'] < $item['reorder_threshold']) {
                            $needsReorder = true;
                            // Add debug comment
                            echo "<!-- Debug: Item {$item['id']} needs reorder. Whole Quantity: {$item['whole_quantity']}, Threshold: {$item['reorder_threshold']} -->";
                        }
                    ?>
                    <tr class="<?= $needsReorder ? 'reorder-needed' : '' ?>" data-needs-reorder="<?= $needsReorder ? 'true' : 'false' ?>">
                        <td><?= htmlspecialchars($item['id']) ?></td>
                        <td><?= htmlspecialchars($item['item_type']) ?></td>
                        <td><?= htmlspecialchars($item['item_name']) ?></td>
                        <td><?= htmlspecialchars($item['normal_location']) ?></td>
                        <td><?= htmlspecialchars($item['whole_quantity']) ?></td>
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
                                <a href="consumable_entry.php?id=<?= htmlspecialchars($item['id']) ?>" class="btn btn-sm btn-warning">Edit</a>
                                <a href="inventory_entry.php?consumable_id=<?= htmlspecialchars($item['id']) ?>" class="btn btn-sm btn-info">Stock Chg</a>
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