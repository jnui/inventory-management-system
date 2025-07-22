<?php
// inventory.php
// Include authentication check
require_once 'auth_check.php';

// Include database connection
require_once 'db_connection.php';

// Optional filter: show log for specific item ID
$filterId = isset($_GET['id']) ? (int)$_GET['id'] : null;

try {
    // Fetch inventory change entries, optionally filtered by consumable_material_id
    if ($filterId) {
        $stmt = $pdo->prepare("SELECT ice.id,
               ice.item_short_code,
               ice.item_name,
               loc.location_name AS normal_location,
               cm.reorder_threshold AS reorder_threshold,
               ice.items_added,
               ice.items_removed,
               ice.whole_quantity,
               ice.change_date,
               ice.consumable_material_id,
               ice.item_notes,
               emp.first_name AS employee_name
        FROM inventory_change_entries ice
        LEFT JOIN item_locations loc ON ice.normal_item_location = loc.id
        LEFT JOIN employees emp ON ice.employee_id = emp.id
        LEFT JOIN consumable_materials cm ON ice.consumable_material_id = cm.id
        WHERE ice.consumable_material_id = :filterId
        ORDER BY ice.change_date DESC");
        $stmt->execute([':filterId' => $filterId]);
    } else {
        $stmt = $pdo->query("SELECT ice.id,
               ice.item_short_code,
               ice.item_name,
               loc.location_name AS normal_location,
               cm.reorder_threshold AS reorder_threshold,
               ice.items_added,
               ice.items_removed,
               ice.whole_quantity,
               ice.change_date,
               ice.consumable_material_id,
               ice.item_notes,
               emp.first_name AS employee_name
        FROM inventory_change_entries ice
        LEFT JOIN item_locations loc ON ice.normal_item_location = loc.id
        LEFT JOIN employees emp ON ice.employee_id = emp.id
        LEFT JOIN consumable_materials cm ON ice.consumable_material_id = cm.id
        ORDER BY ice.change_date DESC");
    }
    $entries = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= $filterId ? 'Inventory Change Log for Item ' . $filterId : 'Inventory Change Log' ?></title>
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
    <!-- Custom CSS for iPad optimization -->
    <link href="custom.css" rel="stylesheet">
    <!-- Include DataTables CSS and yadcf plugin -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/yadcf/0.9.4/jquery.dataTables.yadcf.min.js"></script>
    <style>
        .notes-column {
            max-width: 400px;
            white-space: normal;
            word-wrap: break-word;
            position: relative;
        }
        .notes-column.has-content {
            cursor: pointer;
        }
        .notes-column.has-content:hover {
            background-color: rgba(0,0,0,0.05);
        }
        .notes-full {
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        .modal-body {
            white-space: pre-wrap;
            word-wrap: break-word;
            max-height: 500px;
            overflow-y: auto;
        }
        /* Footer wrapper around DataTables info */
        .footer-wrapper {
            position: fixed !important;
            bottom: 0 !important;
            left: 0 !important;
            right: 0 !important;
            background-color: #f8f9fa !important;
            border-top: 1px solid #dee2e6 !important;
            z-index: 1000 !important;
            padding: 5px !important;
        }
        /* ===== Added styles for column visibility UI ===== */
        .action-row {
            display: flex !important;
            flex-wrap: wrap !important;
            gap: 8px !important;
            margin-bottom: 10px !important;
            align-items: center !important;
            width: 100% !important;
        }
        .button-group {
            display: flex !important;
            gap: 8px !important;
            flex-shrink: 0 !important;
            align-items: center !important;
        }
        .dropdown-menu .dropdown-item.active {
            background-color: #0d6efd !important;
            color: #fff !important;
        }
    </style>
</head>
<body>
<?php
// Set the page title for the navigation bar
$page_title = $filterId ? 'Inventory Change Log for Item ' . $filterId : 'Inventory Change Log';

// Include the navigation bar template
include 'nav_template.php';
?>

<div class="container content-container">
    <h1 class="mb-4"><?= htmlspecialchars(
        $page_title
    ) ?></h1>
    <div class="d-flex gap-2 mb-3" id="button-row-a">
        <a href="consumable_list.php" class="btn btn-primary">View Consumables</a>
        <a href="inventory_entry.php" class="btn btn-primary">Add New Entry</a>
        <a href="natural_language_inventory.php" class="btn btn-success">Smart Entry</a>
    </div>
    <div class="row mb-3 filter-row-b">
        <div class="col-md-3">
            <label for="material-id-filter">Material ID:</label>
            <div id="material-id-filter"></div>
        </div>
        <div class="col-md-3">
            <label for="name-search">Name Search:</label>
            <div id="name-search"></div>
        </div>
    </div>
    <!-- ===== Added Action Row for column visibility controls ===== -->
    <div id="inventoryActionRow" class="action-row"></div>
    <div class="inventory-table-container">
      <table id="inventoryTable" class="table table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Short Code</th>
                <th>Name</th>
                <th>Material ID</th>
                <th>Normal Location</th>
                <th>Reorder Thresh.</th>
                <th>Items Added</th>
                <th>Items Removed</th>
                <th>Whole Quantity</th>
                <th>Employee</th>
                <th>Notes</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($entries as $entry): 
                // Format the date
                $formattedDate = 'N/A';
                if (!empty($entry['change_date'])) {
                    $date = new DateTime($entry['change_date']);
                    $formattedDate = $date->format('M j g:ia');
                }
                
                // Format notes
                $notes = $entry['item_notes'] ?? '';
                $notesClass = !empty($notes) ? 'notes-column has-content' : 'notes-column';
            ?>
            <tr>
                <td><?= htmlspecialchars($entry['id']) ?></td>
                <td><?= htmlspecialchars($entry['item_short_code']) ?></td>
                <td><?= htmlspecialchars($entry['item_name']) ?></td>
                <td><?= htmlspecialchars($entry['consumable_material_id']) ?></td>
                <td><?= htmlspecialchars($entry['normal_location']) ?></td>
                <td><?= htmlspecialchars($entry['reorder_threshold']) ?></td>
                <td><?= htmlspecialchars($entry['items_added']) ?></td>
                <td><?= htmlspecialchars($entry['items_removed']) ?></td>
                <td><?= htmlspecialchars($entry['whole_quantity']) ?></td>
                <td><?= htmlspecialchars($entry['employee_name'] ?? 'N/A') ?></td>
                <td class="<?= $notesClass ?>" data-full-notes="<?= htmlspecialchars($notes) ?>">
                    <div class="notes-text"><?= htmlspecialchars($notes) ?></div>
                </td>
                <td data-order="<?= htmlspecialchars($entry['change_date']) ?>"><?= htmlspecialchars($formattedDate) ?></td>
                <td>
                    <?php if (!empty($entry['consumable_material_id'])): ?>
                        <a href="inventory_entry.php?entry_id=<?= htmlspecialchars($entry['id']) ?>&consumable_id=<?= htmlspecialchars($entry['consumable_material_id']) ?>" class="btn btn-sm btn-warning">Edit</a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
      </table>
    </div>
</div>

<!-- Notes Modal -->
<div class="modal fade" id="notesModal" tabindex="-1" aria-labelledby="notesModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="notesModalLabel">Notes</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Initialize modal
    var notesModal = new bootstrap.Modal(document.getElementById('notesModal'));
    
    // Add click handlers for notes cells
    document.querySelectorAll('.notes-column').forEach(function(cell) {
        if (cell.textContent.trim()) {
            cell.classList.add('has-content');
            cell.setAttribute('data-bs-toggle', 'tooltip');
            cell.setAttribute('data-bs-placement', 'top');
            cell.setAttribute('title', 'Click to view full notes');
            
            cell.addEventListener('click', function() {
                var modalBody = document.querySelector('#notesModal .modal-body');
                modalBody.textContent = this.getAttribute('data-full-notes');
                notesModal.show();
            });
        }
    });

    // Initialize DataTable with column configurations
    var table = $('#inventoryTable').DataTable({
        order: [[11, 'desc']],
        dom: 'rt<"d-flex justify-content-between"ip>',
        pageLength: -1,
        lengthMenu: [[-1], ['All']],
        columns: [
            { visible: false },   // ID
            { visible: false },   // Short Code
            null,                 // Name
            { visible: false },   // Material ID
            { visible: false, orderable: true }, // Normal Location
            null,   // Reorder Threshold
            null,   // Items Added
            null,   // Items Removed
            null,   // Whole Quantity
            null,   // Employee
            null,   // Notes
            null,   // Date
            { orderable: false }  // Actions
        ]
    });

    // Initialize YADCF filters
    yadcf.init(table, [
        {
            column_number: 3,
            filter_type: 'text',
            filter_container_id: 'material-id-filter',
            filter_default_label: 'Enter Material ID'
        },
        {
            column_number: 2,
            filter_type: 'text',
            filter_container_id: 'name-search',
            filter_default_label: 'Search Name'
        }
    ]);

    // Restrict Material ID filter to numeric input only
    $('#material-id-filter input').attr('inputmode', 'numeric')
        .on('keypress', function(e) {
            if (e.which < 48 || e.which > 57) e.preventDefault();
        });

    // Manual filtering handlers to ensure filters work
    $('#material-id-filter input').on('keyup change', function() {
        table.column(3).search(this.value).draw();
    });
    $('#name-search input').on('keyup change', function() {
        table.column(2).search(this.value).draw();
    });

    // Wrap DataTable info in footer wrapper and add back-to-top button
    if (!$('.footer-wrapper').length) {
        $('.dataTables_info').wrap('<div class="footer-wrapper"></div>');
    }
    if (!$('#scrollToTopBtn').length) {
        $('.dataTables_info').append(
          '<span id="scrollToTopBtn" class="ms-3" style="cursor:pointer;"><i class="bi bi-arrow-up-circle-fill" style="font-size:1.5rem;"></i></span>'
        );
        $('#scrollToTopBtn').on('click', function() {
            $('html, body').animate({ scrollTop: 0 }, 500);
        });
    }

    // ===== Build column visibility dropdown =====
    var actionContainer = $('#inventoryActionRow');
    actionContainer.empty();
    var leftButtons = $('<div class="button-group left-buttons"></div>');
    var columnToggleHtml = `
        <div class="dropdown d-inline-block">
            <button class="btn btn-secondary dropdown-toggle btn-mobile-icon" type="button" id="invColumnToggleButton" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-layout-three-columns"></i><span class="btn-text"> Show/Hide Cols</span>
            </button>
            <div class="dropdown-menu p-2" aria-labelledby="invColumnToggleButton" style="min-width: 220px;">
                <div><a class="dropdown-item" data-column="0" href="#">ID</a></div>
                <div><a class="dropdown-item" data-column="1" href="#">Short Code</a></div>
                <div><a class="dropdown-item active" data-column="2" href="#">Name</a></div>
                <div><a class="dropdown-item" data-column="3" href="#">Material ID</a></div>
                <div><a class="dropdown-item" data-column="4" href="#">Normal Location</a></div>
                <div><a class="dropdown-item active" data-column="5" href="#">Reorder Thresh.</a></div>
                <div><a class="dropdown-item active" data-column="6" href="#">Items Added</a></div>
                <div><a class="dropdown-item active" data-column="7" href="#">Items Removed</a></div>
                <div><a class="dropdown-item active" data-column="8" href="#">Whole Quantity</a></div>
                <div><a class="dropdown-item active" data-column="9" href="#">Employee</a></div>
                <div><a class="dropdown-item active" data-column="10" href="#">Notes</a></div>
                <div><a class="dropdown-item active" data-column="11" href="#">Date</a></div>
                <div><a class="dropdown-item active" data-column="12" href="#">Actions</a></div>
            </div>
        </div>`;
    leftButtons.append($(columnToggleHtml));
    actionContainer.append(leftButtons);

    // Initialize Bootstrap dropdown for the newly added button
    if (typeof bootstrap !== 'undefined' && bootstrap.Dropdown) {
        new bootstrap.Dropdown(document.getElementById('invColumnToggleButton'));
    }

    // Explicitly toggle dropdown via Bootstrap instance and log events
    $('#invColumnToggleButton')
        .on('click', function (e) {
            e.preventDefault();
            const dd = bootstrap.Dropdown.getOrCreateInstance(this);
            dd.toggle();
            console.log('Show/Hide Cols dropdown button clicked – manual toggle invoked');
        })
        .on('show.bs.dropdown', function () { console.log('show.bs.dropdown fired'); })
        .on('shown.bs.dropdown', function () { console.log('shown.bs.dropdown fired'); })
        .on('hide.bs.dropdown', function () { console.log('hide.bs.dropdown fired'); })
        .on('hidden.bs.dropdown', function () { console.log('hidden.bs.dropdown fired'); });

    // Handle column visibility toggle (robust selector)
    $(document).on('click', '.dropdown-item[data-column]', function(e) {
        console.log('Dropdown item clicked');
        e.preventDefault();
        e.stopPropagation();
        var columnIndex = parseInt($(this).data('column'));
        var column = table.column(columnIndex);
        var currentlyVisible = column.visible();
        column.visible(!currentlyVisible, false); // toggle visibility
        $(this).toggleClass('active');
        table.columns.adjust().draw(false);
        console.log('Toggled column', columnIndex, 'visible now?', !currentlyVisible);
    });

    // Set initial active state after table draw
    table.columns().every(function(index) {
        if (this.visible()) {
            $('.dropdown-item[data-column="' + index + '"]').addClass('active');
        } else {
            $('.dropdown-item[data-column="' + index + '"]').removeClass('active');
        }
    });
});
</script>
</body>
</html>