<?php
require_once 'db_connection.php';

// Get consumable details
if (!isset($_GET['id'])) {
    die('Missing consumable ID');
}

$consumable_id = $_GET['id'];

// Get consumable details
$stmt = $pdo->prepare("
    SELECT cm.*, 
           COALESCE(oh.status_id, 1) as order_status_id,
           os.status_name,
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
$stmt->execute([$consumable_id]);
$consumable = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$consumable) {
    die('Consumable not found');
}

$has_active_order = in_array($consumable['order_status_id'], [2, 3]);
?>

<!-- Inventory Update Modal -->
<div class="modal fade" id="inventoryUpdateModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Inventory</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <?php if ($has_active_order): ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        This item has an active order (<?php echo htmlspecialchars($consumable['status_name']); ?>).
                        Updating the inventory will mark the order as complete.
                    </div>
                <?php endif; ?>
                
                <form id="inventoryUpdateForm">
                    <input type="hidden" name="consumable_id" value="<?php echo $consumable_id; ?>">
                    
                    <div class="mb-3">
                        <label class="form-label">Item Name</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($consumable['item_name']); ?>" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Current Quantity</label>
                        <input type="number" class="form-control" value="<?php echo $consumable['whole_quantity']; ?>" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label for="quantity_change" class="form-label">Quantity to Add</label>
                        <input type="number" class="form-control" name="quantity_change" id="quantity_change" required min="1">
                        <?php if ($has_active_order): ?>
                            <div class="form-text">
                                Expected delivery quantity: <?php echo $consumable['pending_order_quantity']; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($has_active_order): ?>
                        <div class="mb-3">
                            <label for="delivery_date" class="form-label">Delivery Date</label>
                            <input type="date" class="form-control" name="delivery_date" id="delivery_date" 
                                   value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="received_by" class="form-label">Received By</label>
                            <input type="text" class="form-control" name="received_by" id="received_by" required>
                        </div>
                    <?php endif; ?>
                    
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea class="form-control" name="notes" id="notes" rows="3" 
                                  placeholder="Enter any additional notes about the inventory update"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="submitInventoryUpdate()">
                    <?php echo $has_active_order ? 'Update & Complete Order' : 'Update Inventory'; ?>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function submitInventoryUpdate() {
    const form = document.getElementById('inventoryUpdateForm');
    const formData = new FormData(form);

    fetch('update_inventory.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Close the modal
            bootstrap.Modal.getInstance(document.getElementById('inventoryUpdateModal')).hide();
            
            // Show success message
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-success alert-dismissible fade show';
            alertDiv.innerHTML = `
                ${data.message}
                ${data.order_status?.was_active ? '<br>Order has been marked as complete.' : ''}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.querySelector('.content-container').insertBefore(alertDiv, document.querySelector('.table-responsive'));
            
            // Reload the page after a short delay
            setTimeout(() => location.reload(), 1500);
        } else {
            alert('Error updating inventory: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error updating inventory. Please try again.');
    });
}
</script> 