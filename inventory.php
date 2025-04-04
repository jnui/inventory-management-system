<?php
// inventory.php
// Include authentication check
require_once 'auth_check.php';

// Include database connection
require_once 'db_connection.php';

try {
    // Query to get inventory changes and join the storage location for display.
    $stmt = $pdo->query("
        SELECT ice.id,
               ice.item_short_code,
               ice.item_name,
               loc.location_name AS normal_location,
               ice.reorder_threshold,
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
        ORDER BY ice.change_date DESC
    ");
    $entries = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Inventory</title>
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
    <style>
        .notes-column {
            max-width: 200px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
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
    </style>
</head>
<body>
<?php
// Set the page title for the navigation bar
$page_title = 'Stock Update List';

// Include the navigation bar template
include 'nav_template.php';
?>

<div class="container content-container">
    <div class="d-flex gap-2 mb-3">
        <a href="consumable_list.php" class="btn btn-primary">View Consumables</a>
        <a href="inventory_entry.php" class="btn btn-primary">Add New Entry</a>
        <a href="natural_language_inventory.php" class="btn btn-success">Smart Entry</a>
    </div>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Short Code</th>
                <th>Name</th>
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
                <td><?= htmlspecialchars($entry['normal_location']) ?></td>
                <td><?= htmlspecialchars($entry['reorder_threshold']) ?></td>
                <td><?= htmlspecialchars($entry['items_added']) ?></td>
                <td><?= htmlspecialchars($entry['items_removed']) ?></td>
                <td><?= htmlspecialchars($entry['whole_quantity']) ?></td>
                <td><?= htmlspecialchars($entry['employee_name'] ?? 'N/A') ?></td>
                <td class="<?= $notesClass ?>" data-full-notes="<?= htmlspecialchars($notes) ?>"><?= htmlspecialchars($notes) ?></td>
                <td><?= htmlspecialchars($formattedDate) ?></td>
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
});
</script>
</body>
</html>