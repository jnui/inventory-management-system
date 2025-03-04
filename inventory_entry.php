<?php
// inventory_entry.php
// Include authentication check
require_once 'auth_check.php';

// Process the form submission if POST data is present.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Include database connection
        require_once 'db_connection.php';
        
        // Start a transaction to ensure data consistency
        $pdo->beginTransaction();
        
        // Check if we're updating an existing entry
        if (isset($_POST['entry_id']) && !empty($_POST['entry_id'])) {
            // Get the existing entry to determine the previous quantity change
            $entryStmt = $pdo->prepare("
                SELECT consumable_material_id, items_added, items_removed, whole_quantity
                FROM inventory_change_entries 
                WHERE id = :id
            ");
            $entryStmt->execute([':id' => $_POST['entry_id']]);
            $existingEntry = $entryStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$existingEntry) {
                throw new Exception("Existing entry not found.");
            }
            
            // Get the current consumable material details
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
            
            // Reverse the previous change to get the original quantity
            $originalQuantity = $consumable['whole_quantity'];
            if ($existingEntry['items_added'] > 0) {
                $originalQuantity -= $existingEntry['items_added'];
            } else if ($existingEntry['items_removed'] > 0) {
                $originalQuantity += $existingEntry['items_removed'];
            }
            
            // Apply the new change
            $itemsAdded = 0;
            $itemsRemoved = 0;
            $wholeQuantity = $originalQuantity;
            
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
            
            // Update the existing inventory change entry
            $stmt = $pdo->prepare("
                UPDATE inventory_change_entries 
                SET consumable_material_id = :consumable_material_id,
                    item_short_code = :item_short_code,
                    item_name = :item_name,
                    item_description = :item_description,
                    item_notes = :item_notes,
                    normal_item_location = :normal_item_location,
                    reorder_threshold = :reorder_threshold,
                    items_added = :items_added,
                    items_removed = :items_removed,
                    whole_quantity = :whole_quantity,
                    employee_id = :employee_id,
                    change_date = NOW()
                WHERE id = :id
            ");
            
            $stmt->execute([
                ':id' => $_POST['entry_id'],
                ':consumable_material_id' => $consumable['id'],
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
            
        } else {
            // This is a new entry - original code
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
            
            // Insert the inventory change entry
        $stmt = $pdo->prepare("
            INSERT INTO inventory_change_entries 
                    (consumable_material_id, item_short_code, item_name, item_description, item_notes, normal_item_location, reorder_threshold, items_added, items_removed, whole_quantity, employee_id, change_date)
            VALUES 
                    (:consumable_material_id, :item_short_code, :item_name, :item_description, :item_notes, :normal_item_location, :reorder_threshold, :items_added, :items_removed, :whole_quantity, :employee_id, NOW())
        ");
            
        $stmt->execute([
                ':consumable_material_id' => $consumable['id'],
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
        
        // Commit the transaction
        $pdo->commit();
        
        header("Location: inventory.php");
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
    $preselectedConsumableId = isset($_GET['consumable_id']) ? $_GET['consumable_id'] : null;
    
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
    
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Inventory Entry</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Opera compatibility -->
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <!-- Bootstrap CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Custom CSS for iPad optimization -->
    <link href="custom.css" rel="stylesheet">
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
    <form method="POST" action="inventory_entry.php">
        <?php if ($editMode && $entryData): ?>
            <input type="hidden" name="entry_id" value="<?= htmlspecialchars($entryData['id']) ?>">
        <?php endif; ?>
        <div class="mb-3">
            <label for="consumable_material_id" class="form-label">Select Item</label>
            <select class="form-select" name="consumable_material_id" id="consumable_material_id" required>
                <option value="">-- Select an item --</option>
                <?php foreach ($consumables as $item): ?>
                    <option value="<?= htmlspecialchars($item['id']) ?>" 
                            data-short-code="<?= htmlspecialchars($item['item_short_code']) ?>"
                            data-location="<?= htmlspecialchars($item['location_name']) ?>"
                            data-location-id="<?= htmlspecialchars($item['normal_item_location']) ?>"
                            data-description="<?= htmlspecialchars($item['item_description']) ?>"
                            data-whole-quantity="<?= htmlspecialchars($item['whole_quantity']) ?>"
                            data-units-whole="<?= htmlspecialchars($item['item_units_whole']) ?>"
                            data-units-part="<?= htmlspecialchars($item['item_units_part']) ?>"
                            data-parts-per-whole="<?= htmlspecialchars($item['qty_parts_per_whole']) ?>"
                            <?= ($preselectedConsumableId && $preselectedConsumableId == $item['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($item['item_name']) ?> (<?= htmlspecialchars($item['item_type']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <!-- Item details row that appears when an item is selected -->
        <div id="item-details" class="item-details">
            <!-- First row for short code and location -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <span class="detail-label">Short Code:</span>
                    <span id="detail-short-code" class="detail-value"></span>
                    <input type="hidden" name="item_short_code" id="item_short_code" value="">
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
                    <input type="hidden" name="item_notes" id="item_notes" value="">
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
            
            <input type="hidden" name="reorder_threshold" id="reorder_threshold" value="0">
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
        
        <button type="submit" class="btn btn-success">Submit Entry</button>
        <a href="inventory.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>

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
        
        toggle.addEventListener('change', function() {
            if (this.checked) {
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
        });
        
        // Set initial state based on the hidden input value
        if (hiddenAction.value === 'add') {
            toggle.checked = true;
            actionAdd.classList.add('fw-bold');
            actionRemove.classList.remove('fw-bold');
            toggleContainer.classList.remove('bg-danger-light');
            toggleContainer.classList.add('bg-success-light');
        } else {
            toggle.checked = false;
            actionRemove.classList.add('fw-bold');
            actionAdd.classList.remove('fw-bold');
            toggleContainer.classList.remove('bg-success-light');
            toggleContainer.classList.add('bg-danger-light');
        }
        
        // Item selection functionality
        const itemSelect = document.getElementById('consumable_material_id');
        const itemDetails = document.getElementById('item-details');
        const detailShortCode = document.getElementById('detail-short-code');
        const detailLocation = document.getElementById('detail-location');
        const detailDescription = document.getElementById('detail-description');
        const detailNotes = document.getElementById('detail-notes');
        const detailWholeQuantity = document.getElementById('detail-whole-quantity');
        const detailUnitsWhole = document.getElementById('detail-units-whole');
        const detailPartTotal = document.getElementById('detail-part-total');
        const detailUnitsPart = document.getElementById('detail-units-part');
        const shortCodeInput = document.getElementById('item_short_code');
        const notesInput = document.getElementById('item_notes');
        
        // If an item is already selected (from PHP preselection), show the item details
        if (itemSelect.value) {
            const selectedOption = itemSelect.options[itemSelect.selectedIndex];
            
            // Update the displayed details
            detailShortCode.textContent = selectedOption.dataset.shortCode || 'N/A';
            detailLocation.textContent = selectedOption.dataset.location || 'N/A';
            detailDescription.textContent = selectedOption.dataset.description || 'N/A';
            detailNotes.textContent = 'None'; // Default text for notes
            detailWholeQuantity.textContent = selectedOption.dataset.wholeQuantity || '0';
            detailUnitsWhole.textContent = selectedOption.dataset.unitsWhole || '';
            
            // Calculate and display the total part units
            const wholeQuantity = parseInt(selectedOption.dataset.wholeQuantity) || 0;
            const partsPerWhole = parseInt(selectedOption.dataset.partsPerWhole) || 0;
            const totalPartUnits = wholeQuantity * partsPerWhole;
            
            if (totalPartUnits > 0 && selectedOption.dataset.unitsPart) {
                detailPartTotal.textContent = totalPartUnits;
                detailUnitsPart.textContent = selectedOption.dataset.unitsPart;
            } else {
                detailPartTotal.textContent = 'N/A';
                detailUnitsPart.textContent = '';
            }
            
            // Update the hidden inputs
            shortCodeInput.value = selectedOption.dataset.shortCode || '';
            notesInput.value = ''; // We'll use an empty string for notes
            
            // Show the details row
            itemDetails.style.display = 'block';
        }
        
        itemSelect.addEventListener('change', function() {
            if (this.value) {
                const selectedOption = this.options[this.selectedIndex];
                
                // Update the displayed details
                detailShortCode.textContent = selectedOption.dataset.shortCode || 'N/A';
                detailLocation.textContent = selectedOption.dataset.location || 'N/A';
                detailDescription.textContent = selectedOption.dataset.description || 'N/A';
                detailNotes.textContent = 'None'; // Default text for notes
                detailWholeQuantity.textContent = selectedOption.dataset.wholeQuantity || '0';
                detailUnitsWhole.textContent = selectedOption.dataset.unitsWhole || '';
                
                // Calculate and display the total part units
                const wholeQuantity = parseInt(selectedOption.dataset.wholeQuantity) || 0;
                const partsPerWhole = parseInt(selectedOption.dataset.partsPerWhole) || 0;
                const totalPartUnits = wholeQuantity * partsPerWhole;
                
                if (totalPartUnits > 0 && selectedOption.dataset.unitsPart) {
                    detailPartTotal.textContent = totalPartUnits;
                    detailUnitsPart.textContent = selectedOption.dataset.unitsPart;
                } else {
                    detailPartTotal.textContent = 'N/A';
                    detailUnitsPart.textContent = '';
                }
                
                // Update the hidden inputs
                shortCodeInput.value = selectedOption.dataset.shortCode || '';
                notesInput.value = ''; // We'll use an empty string for notes
                
                // Show the details row
                itemDetails.style.display = 'block';
            } else {
                // Hide the details row if no item is selected
                itemDetails.style.display = 'none';
            }
        });
    });
</script>
</body>
</html>