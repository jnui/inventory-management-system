<?php
// inventory_entry.php
// Include authentication check
require_once 'auth_check.php';

// Check if user has write access (not read-only)
require_write_access();

// Check if this is a natural language submission
if (isset($_GET['nl']) && isset($_SESSION['nl_form_data'])) {
    $_POST = array_merge($_POST, $_SESSION['nl_form_data']);
    unset($_SESSION['nl_form_data']);
}

// Get consumable ID from either parameter
if (!isset($_GET['id']) && !isset($_GET['consumable_id'])) {
    die('Missing consumable ID (GET parameter check)');
}

$consumable_material_id = $_GET['id'] ?? $_GET['consumable_id'];

// Process the form submission if POST data is present.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Debug log the POST data
        error_log("POST data received: " . print_r($_POST, true));
        
        // Include database connection
        require_once 'db_connection.php';
        
        // Get consumable ID from POST data
        if (!isset($_POST['consumable_material_id'])) {
            error_log("Missing consumable_material_id in POST data");
            die('Missing consumable ID (POST data check)');
        }
        
        error_log("Processing inventory update for consumable_material_id: " . $_POST['consumable_material_id']);
        
        // Start a transaction to ensure data consistency
        $pdo->beginTransaction();
        
        // Get the selected consumable material details
        $consumableStmt = $pdo->prepare("
            SELECT id, item_type, item_name, item_description, normal_item_location, whole_quantity 
            FROM consumable_materials 
            WHERE id = :id
            FOR UPDATE
        ");
        $consumableStmt->execute([':id' => $_POST['consumable_material_id']]);
        $consumable = $consumableStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$consumable) {
            throw new Exception("Selected consumable material not found.");
        }
        
        // Determine if adding or removing based on toggle
        $itemsAdded = 0;
        $itemsRemoved = 0;
        $wholeQuantity = isset($consumable['whole_quantity']) ? (int)$consumable['whole_quantity'] : 0;
        
        if ($_POST['inventory_action'] === 'add') {
            $itemsAdded = (int)$_POST['quantity'];
            $wholeQuantity += $itemsAdded;
        } else {
            $itemsRemoved = (int)$_POST['quantity'];
            $wholeQuantity -= $itemsRemoved;
            // Ensure whole_quantity doesn't go below 0
            if ($wholeQuantity < 0) {
                $wholeQuantity = 0;
            }
        }

        // If we're editing an existing entry, we need to reverse its effects first
        if (isset($_GET['entry_id']) || isset($_POST['entry_id'])) {
            $entry_id = $_GET['entry_id'] ?? $_POST['entry_id'];
            error_log("Editing entry ID: " . $entry_id); // Add debug logging
            
            // Get the original entry
            $originalEntryStmt = $pdo->prepare("SELECT items_added, items_removed FROM inventory_change_entries WHERE id = :id");
            $originalEntryStmt->execute([':id' => $entry_id]);
            $originalEntry = $originalEntryStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($originalEntry) {
                error_log("Found original entry: " . print_r($originalEntry, true)); // Add debug logging
                // Reverse the original entry's effect on whole_quantity
                $wholeQuantity = isset($consumable['whole_quantity']) ? (int)$consumable['whole_quantity'] : 0;
                $wholeQuantity -= (int)$originalEntry['items_added'];
                $wholeQuantity += (int)$originalEntry['items_removed'];
                
                // Now apply the new changes
                if ($_POST['inventory_action'] === 'add') {
                    $wholeQuantity += $itemsAdded;
                } else {
                    $wholeQuantity -= $itemsRemoved;
                }
                
                // Ensure whole_quantity doesn't go below 0
                if ($wholeQuantity < 0) {
                    $wholeQuantity = 0;
                }
                
                // Update the existing entry
                $stmt = $pdo->prepare("
                    UPDATE inventory_change_entries 
                    SET item_short_code = :item_short_code,
                        item_name = :item_name,
                        item_description = :item_description,
                        item_notes = :item_notes,
                        normal_item_location = :normal_item_location,
                        reorder_threshold = :reorder_threshold,
                        items_added = :items_added,
                        items_removed = :items_removed,
                        whole_quantity = :whole_quantity,
                        employee_id = :employee_id
                    WHERE id = :entry_id
                ");
                
                $stmt->execute([
                    ':entry_id'              => $entry_id,
                    ':item_short_code'       => $_POST['item_short_code'],
                    ':item_name'             => $consumable['item_name'],
                    ':item_description'      => $consumable['item_description'],
                    ':item_notes'            => $_POST['item_notes'],
                    ':normal_item_location'  => $consumable['normal_item_location'],
                    ':reorder_threshold'     => $_POST['reorder_threshold'],
                    ':items_added'           => $itemsAdded,
                    ':items_removed'         => $itemsRemoved,
                    ':whole_quantity'        => $wholeQuantity,
                    ':employee_id'           => $_POST['employee_id']
                ]);
            }
        } else {
            // Insert new entry if not editing
            $stmt = $pdo->prepare("
                INSERT INTO inventory_change_entries 
                        (consumable_material_id, item_short_code, item_name, item_description, item_notes, normal_item_location, reorder_threshold, items_added, items_removed, whole_quantity, employee_id, change_date)
                VALUES 
                        (:consumable_material_id, :item_short_code, :item_name, :item_description, :item_notes, :normal_item_location, :reorder_threshold, :items_added, :items_removed, :whole_quantity, :employee_id, NOW())
            ");
            
            $stmt->execute([
                ':consumable_material_id' => $_POST['consumable_material_id'],
                ':item_short_code'        => $_POST['item_short_code'],
                ':item_name'              => $consumable['item_name'],
                ':item_description'       => $consumable['item_description'],
                ':item_notes'             => $_POST['item_notes'],
                ':normal_item_location'   => $consumable['normal_item_location'],
                ':reorder_threshold'      => $_POST['reorder_threshold'],
                ':items_added'            => $itemsAdded,
                ':items_removed'          => $itemsRemoved,
                ':whole_quantity'         => $wholeQuantity,
                ':employee_id'            => $_POST['employee_id']
            ]);
        }
        
        // Update the whole_quantity in the consumable_materials table
        $updateStmt = $pdo->prepare("
            UPDATE consumable_materials 
            SET whole_quantity = :whole_quantity 
            WHERE id = :id
        ");
        
        $updateStmt->execute([
            ':whole_quantity' => $wholeQuantity,
            ':id' => $consumable['id']
        ]);

        // If an order was selected and we're adding stock, update that specific order
        if ($_POST['inventory_action'] === 'add' && !empty($_POST['selected_order_id'])) {
            $orderUpdateStmt = $pdo->prepare("
                UPDATE order_history 
                SET status_id = 4,
                    notes = CONCAT(COALESCE(notes, ''), '\nOrder completed on ', NOW(), ' - Inventory updated to ', :whole_quantity, ' units')
                WHERE id = :order_id
            ");
            
            $orderUpdateStmt->execute([
                ':order_id' => $_POST['selected_order_id'],
                ':whole_quantity' => $wholeQuantity
            ]);
        }
        
        // Commit the transaction
        $pdo->commit();
        
        // Debug log
        error_log("Redirecting to consumable_list with ID: " . $consumable['id']);
        
        // Redirect to consumable list with the item ID for scrolling
        header("Location: consumable_list.php?scroll_to=" . $consumable['id']);
        exit;
    } catch (Exception $e) {
        // Roll back the transaction on error
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error = "Error: " . $e->getMessage();
    }
}

// Retrieve locations for the drop-down list.
try {
    // Include database connection
    require_once 'db_connection.php';
    
    // Get locations
    $locStmt = $pdo->query("SELECT id, location_name FROM item_locations ORDER BY location_name ASC");
    $locations = $locStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get employees - ensure they are sorted alphabetically
    $empStmt = $pdo->query("SELECT id, first_name FROM employees ORDER BY first_name ASC");
    $employees = $empStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get consumable materials with all needed details
    $consStmt = $pdo->query("
        SELECT cm.id, cm.item_type, cm.item_name, cm.item_description, 
               loc.location_name, cm.normal_item_location, cm.whole_quantity,
               cm.item_units_whole, cm.item_units_part, cm.qty_parts_per_whole,
               CONCAT(cm.item_type, '-', cm.id) AS item_short_code
        FROM consumable_materials cm
        LEFT JOIN item_locations loc ON cm.normal_item_location = loc.id
        ORDER BY cm.item_name ASC
    ");
    $consumables = $consStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Check if a specific consumable_id was passed in the URL
    $preselectedConsumableId = $consumable_material_id;
    
    // Check if editing an existing entry
    $editMode = false;
    $entryData = null;
    $preselectedQuantity = 1;
    $preselectedEmployeeId = null;
    $preselectedAction = 'remove'; // Default action
    
    if (isset($_GET['entry_id'])) {
        $editMode = true;
        $entryStmt = $pdo->prepare("
            SELECT * FROM inventory_change_entries 
            WHERE id = :id
        ");
        $entryStmt->execute([':id' => $_GET['entry_id']]);
        $entryData = $entryStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($entryData) {
            // Set preselected values from the entry data
            $preselectedConsumableId = $entryData['consumable_material_id'];
            $preselectedEmployeeId = $entryData['employee_id'];
            
            // Determine quantity and action based on items_added and items_removed
            if ($entryData['items_added'] > 0) {
                $preselectedQuantity = $entryData['items_added'];
                $preselectedAction = 'add';
            } else {
                $preselectedQuantity = $entryData['items_removed'];
                $preselectedAction = 'remove';
            }
        }
    }
    
    // Get consumable details and check for active orders
    $stmt = $pdo->prepare("
        SELECT cm.*, 
               COALESCE(oh.status_id, 1) as current_status_id,
               os.status_name as current_status,
               oh.quantity_ordered as pending_order_quantity
        FROM consumable_materials cm
        LEFT JOIN order_history oh ON cm.id = oh.consumable_id 
            AND oh.id = (
                SELECT MAX(id) 
                FROM order_history 
                WHERE consumable_id = cm.id
            )
        LEFT JOIN order_status os ON COALESCE(oh.status_id, 1) = os.id
        WHERE cm.id = ?
    ");
    $stmt->execute([$consumable_material_id]);
    $consumable = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$consumable) {
        die('Consumable not found');
    }

    // Get all pending orders for this item
    $pendingOrdersStmt = $pdo->prepare("
        SELECT oh.*, os.status_name
        FROM order_history oh
        JOIN order_status os ON oh.status_id = os.id
        WHERE oh.consumable_id = ? 
        AND oh.status_id IN (2, 3)
        ORDER BY oh.ordered_at DESC
    ");
    $pendingOrdersStmt->execute([$consumable_material_id]);
    $pendingOrders = $pendingOrdersStmt->fetchAll(PDO::FETCH_ASSOC);

    $has_active_order = count($pendingOrders) > 0;
    
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Stock Update Entry</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Opera compatibility -->
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="assets/img/favicon.svg">
    <link rel="alternate icon" type="image/x-icon" href="assets/img/favicon.ico">
    <link rel="mask-icon" href="assets/img/favicon.svg" color="#0d6efd">
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Custom CSS for iPad optimization -->
    <link href="custom.css" rel="stylesheet">
    <style>
        .content-container {
            padding-top: 80px;
            padding-bottom: 80px;
            max-width: 800px;
            margin: 0 auto;
        }
        
        .form-container {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 2rem;
        }
        
        .status-badge {
            font-size: 1rem;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            font-weight: 500;
            display: inline-block;
            margin: 0.5rem 0;
        }
        
        .status-1 { background-color: #ffc107; color: #000; } /* Not Ordered - Yellow */
        .status-2 { background-color: #17a2b8; color: #fff; } /* Ordered & Waiting - Blue */
        .status-3 { background-color: #dc3545; color: #fff; } /* Backordered - Red */
        .status-4 { background-color: #28a745; color: #fff; } /* Complete - Green */
        
        .item-details {
            background-color: #f8f9fa;
            border-radius: 6px;
            padding: 1.5rem;
            margin: 1rem 0;
        }
        
        .detail-label {
            font-weight: 500;
            color: #495057;
            margin-right: 0.5rem;
        }
        
        .detail-value {
            color: #212529;
        }
        
        .toggle-container {
            background-color: #f8f9fa;
            border-radius: 6px;
            padding: 1rem;
            margin: 1rem 0;
            transition: background-color 0.3s ease;
        }
        
        .bg-danger-light {
            background-color: #f8d7da !important;
        }
        
        .bg-success-light {
            background-color: #d4edda !important;
        }
        
        .action-label {
            font-weight: 500;
            margin: 0 1rem;
        }
        
        .form-label {
            font-weight: 500;
            color: #495057;
        }
        
        .form-control, .form-select {
            border-radius: 6px;
            border: 1px solid #ced4da;
            padding: 0.5rem 1rem;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #80bdff;
            box-shadow: 0 0 0 0.2rem rgba(0,123,255,0.25);
        }
        
        .btn {
            padding: 0.5rem 1.5rem;
            border-radius: 6px;
            font-weight: 500;
        }
        
        .alert {
            border-radius: 6px;
            margin-bottom: 1.5rem;
        }
        
        @media (max-width: 767px) {
            .content-container {
                padding-top: 60px;
                padding-bottom: 60px;
            }
            
            .form-container {
                padding: 1rem;
            }
            
            .item-details {
                padding: 1rem;
            }
            
            .detail-label {
                display: block;
                margin-bottom: 0.25rem;
            }
            
            .detail-value {
                display: block;
                margin-bottom: 0.5rem;
            }
            
            .toggle-container {
                padding: 0.75rem;
            }
            
            .action-label {
                margin: 0 0.5rem;
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
<?php 
// Set the page title for the navigation bar
$page_title = $editMode ? 'Edit Inventory Entry' : 'Add New Inventory Entry';
// Include the navigation template
include 'nav_template.php'; 
?>

<div class="container content-container">
    <?php if (!empty($error)) { echo '<div class="alert alert-danger">' . htmlspecialchars($error) . '</div>'; } ?>
    
    <?php if ($has_active_order): ?>
    <div class="alert alert-warning">
        <h5 class="alert-heading"><i class="bi bi-exclamation-triangle"></i> Active Orders</h5>
        <p class="mb-0">This item has <?php echo count($pendingOrders); ?> active order(s):</p>
        <ul class="mb-0">
            <?php foreach ($pendingOrders as $order): ?>
                <li>Order #<?= htmlspecialchars($order['id']) ?> - <?= htmlspecialchars($order['quantity_ordered']) ?> units (<?= htmlspecialchars($order['status_name']) ?>)</li>
            <?php endforeach; ?>
        </ul>
        <p class="mt-2 mb-0">When adding stock, you can choose to apply it to an existing order.</p>
    </div>
    <?php endif; ?>
    
    <div class="form-container">
        <h4 class="mb-4"><?php echo htmlspecialchars($consumable['item_name']); ?></h4>
        <?php if (!$has_active_order): ?>
        <div class="status-badge status-<?php echo $consumable['current_status_id']; ?>">
            <?php echo htmlspecialchars($consumable['current_status']); ?>
        </div>
        <?php endif; ?>
        
        <form method="POST" action="<?= $_SERVER['PHP_SELF'] ?>?<?= http_build_query(array_merge($_GET, ['consumable_id' => $consumable_material_id])) ?>" id="inventoryForm">
            <?php if ($editMode && $entryData): ?>
                <input type="hidden" name="entry_id" value="<?= htmlspecialchars($entryData['id']) ?>">
            <?php endif; ?>
            <?php 
            // Debug output
            error_log("Setting consumable_material_id in form: " . $consumable_material_id);
            ?>
            <input type="hidden" name="consumable_material_id" value="<?= htmlspecialchars($consumable_material_id) ?>">
            <input type="hidden" name="item_short_code" value="<?= htmlspecialchars($consumable['item_type'] . '-' . $consumable['id']) ?>">
            <input type="hidden" name="reorder_threshold" value="0">
            <input type="hidden" name="selected_order_id" id="selected_order_id" value="">
            
            <!-- Item details row -->
            <div id="item-details" class="item-details">
                <!-- First row for short code and location -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <span class="detail-label">Short Code:</span>
                        <span id="detail-short-code" class="detail-value"></span>
                    </div>
                    <div class="col-md-6">
                        <span class="detail-label">Location:</span>
                        <span id="detail-location" class="detail-value"></span>
                    </div>
                </div>
                
                <!-- Second row for description and notes -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <span class="detail-label">Description:</span>
                        <span id="detail-description" class="detail-value"></span>
                    </div>
                    <div class="col-md-6">
                        <span class="detail-label">Notes:</span>
                        <span id="detail-notes" class="detail-value"></span>
                    </div>
                </div>
                
                <!-- Third row for whole quantity -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <span class="detail-label">Current Whole Quantity:</span>
                        <span id="detail-whole-quantity" class="detail-value"></span>
                        <span id="detail-units-whole" class="detail-value ms-1"></span>
                    </div>
                    <div class="col-md-6">
                        <span class="detail-label">Total Part Units:</span>
                        <span id="detail-part-total" class="detail-value"></span>
                        <span id="detail-units-part" class="detail-value ms-1"></span>
                    </div>
                </div>
            </div>
            
            <div class="mb-4">
                <label class="form-label d-block">Inventory Action</label>
                <div class="toggle-container <?= ($preselectedAction === 'add') ? 'bg-success-light' : 'bg-danger-light' ?>">
                    <span id="action_remove" class="action-label text-danger">Remove Items</span>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="inventory_action_toggle" <?= ($preselectedAction === 'add') ? 'checked' : '' ?>>
                        <input type="hidden" name="inventory_action" id="inventory_action_value" value="<?= htmlspecialchars($preselectedAction) ?>">
                    </div>
                    <span id="action_add" class="action-label text-success">Add Items</span>
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="quantity" class="form-label">Quantity</label>
                    <input type="number" class="form-control" name="quantity" id="quantity" value="<?= htmlspecialchars($preselectedQuantity) ?>" min="1" required>
                </div>
                <div class="col-md-6">
                    <label for="employee_id" class="form-label">Employee</label>
                    <select class="form-select" name="employee_id" id="employee_id" required>
                        <option value="">-- Select an employee --</option>
                        <?php foreach ($employees as $employee): ?>
                            <option value="<?= htmlspecialchars($employee['id']) ?>" <?= ($preselectedEmployeeId && $preselectedEmployeeId == $employee['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($employee['first_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-12">
                    <label for="item_notes" class="form-label">Notes</label>
                    <textarea class="form-control" name="item_notes" id="item_notes" rows="3" placeholder="Enter any notes about this inventory change"><?= $editMode && $entryData ? htmlspecialchars($entryData['item_notes']) : '' ?></textarea>
                </div>
            </div>
            
            <button type="submit" class="btn btn-success" id="submitBtn">Submit Entry</button>
            <a href="inventory.php" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</div>

<!-- Order Selection Modal -->
<?php if ($has_active_order): ?>
<div class="modal fade" id="orderSelectionModal" tabindex="-1" aria-labelledby="orderSelectionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="orderSelectionModalLabel">Apply Stock to Order</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Would you like to apply the stock to an existing order?</p>
                <div class="list-group">
                    <?php foreach ($pendingOrders as $order): ?>
                    <button type="button" class="list-group-item list-group-item-action order-select" 
                            data-order-id="<?= htmlspecialchars($order['id']) ?>"
                            data-quantity="<?= htmlspecialchars($order['quantity_ordered']) ?>">
                        <div class="d-flex w-100 justify-content-between">
                            <h6 class="mb-1">Order #<?= htmlspecialchars($order['id']) ?></h6>
                            <small><?= htmlspecialchars($order['ordered_at']) ?></small>
                        </div>
                        <p class="mb-1">Quantity: <?= htmlspecialchars($order['quantity_ordered']) ?></p>
                        <small>Status: <?= htmlspecialchars($order['status_name']) ?></small>
                    </button>
                    <?php endforeach; ?>
                    <button type="button" class="list-group-item list-group-item-action" id="noOrderSelect">
                        <div class="d-flex w-100 justify-content-between">
                            <h6 class="mb-1">No, just increase stock</h6>
                        </div>
                        <p class="mb-1">Add stock without applying to any order</p>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Bootstrap JS and custom script -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Toggle switch functionality
        const toggle = document.getElementById('inventory_action_toggle');
        const actionRemove = document.getElementById('action_remove');
        const actionAdd = document.getElementById('action_add');
        const hiddenAction = document.getElementById('inventory_action_value');
        const toggleContainer = document.querySelector('.toggle-container');
        
        function updateToggleState(isAdd) {
            if (isAdd) {
                hiddenAction.value = 'add';
                actionAdd.classList.add('fw-bold');
                actionRemove.classList.remove('fw-bold');
                toggleContainer.classList.remove('bg-danger-light');
                toggleContainer.classList.add('bg-success-light');
            } else {
                hiddenAction.value = 'remove';
                actionRemove.classList.add('fw-bold');
                actionAdd.classList.remove('fw-bold');
                toggleContainer.classList.remove('bg-success-light');
                toggleContainer.classList.add('bg-danger-light');
            }
        }
        
        toggle.addEventListener('change', function() {
            updateToggleState(this.checked);
        });
        
        // Set initial state based on the hidden input value
        updateToggleState(hiddenAction.value === 'add');
        
        // Update item details display
        const itemDetails = document.getElementById('item-details');
        const detailShortCode = document.getElementById('detail-short-code');
        const detailLocation = document.getElementById('detail-location');
        const detailDescription = document.getElementById('detail-description');
        const detailNotes = document.getElementById('detail-notes');
        const detailWholeQuantity = document.getElementById('detail-whole-quantity');
        const detailUnitsWhole = document.getElementById('detail-units-whole');
        const detailPartTotal = document.getElementById('detail-part-total');
        const detailUnitsPart = document.getElementById('detail-units-part');
        
        // Update the displayed details
        detailShortCode.textContent = '<?= htmlspecialchars($consumable['item_type'] . '-' . $consumable['id']) ?>';
        detailLocation.textContent = '<?= htmlspecialchars($consumable['normal_item_location']) ?>';
        detailDescription.textContent = '<?= htmlspecialchars($consumable['item_description']) ?>';
        detailNotes.textContent = 'None'; // Default text for notes
        detailWholeQuantity.textContent = '<?= htmlspecialchars($consumable['whole_quantity']) ?>';
        detailUnitsWhole.textContent = '<?= htmlspecialchars($consumable['item_units_whole']) ?>';
        
        // Calculate and display the total part units
        const wholeQuantity = <?= (int)$consumable['whole_quantity'] ?>;
        const partsPerWhole = <?= (int)$consumable['qty_parts_per_whole'] ?>;
        const totalPartUnits = wholeQuantity * partsPerWhole;
        
        if (totalPartUnits > 0 && '<?= htmlspecialchars($consumable['item_units_part']) ?>') {
            detailPartTotal.textContent = totalPartUnits;
            detailUnitsPart.textContent = '<?= htmlspecialchars($consumable['item_units_part']) ?>';
        } else {
            detailPartTotal.textContent = 'N/A';
            detailUnitsPart.textContent = '';
        }
        
        // Show the details row
        itemDetails.style.display = 'block';

        // Order selection modal handling
        const form = document.getElementById('inventoryForm');
        const submitBtn = document.getElementById('submitBtn');
        const orderSelectionModal = document.getElementById('orderSelectionModal');
        const modal = new bootstrap.Modal(orderSelectionModal);
        const selectedOrderInput = document.getElementById('selected_order_id');
        const noOrderSelect = document.getElementById('noOrderSelect');
        const orderSelects = document.querySelectorAll('.order-select');

        // Show modal when submitting form with 'add' action
        form.addEventListener('submit', function(e) {
            const actionValue = document.getElementById('inventory_action_value').value;
            if (actionValue === 'add' && orderSelectionModal) {
                e.preventDefault();
                modal.show();
            }
        });

        // Handle order selection
        orderSelects.forEach(select => {
            select.addEventListener('click', function() {
                selectedOrderInput.value = this.dataset.orderId;
                modal.hide();
                form.submit();
            });
        });

        // Handle "no order" selection
        if (noOrderSelect) {
            noOrderSelect.addEventListener('click', function() {
                selectedOrderInput.value = '';
                modal.hide();
                form.submit();
            });
        }
    });
</script>
</body>
</html>