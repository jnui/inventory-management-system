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
           COALESCE(oh.status_id, 1) as current_status_id,
           os.status_name as current_status
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

// Get order history
$stmt = $pdo->prepare("
    SELECT oh.*, os.status_name, os.description as status_description
    FROM order_history oh
    JOIN order_status os ON oh.status_id = os.id
    WHERE oh.consumable_id = ?
    ORDER BY oh.ordered_at DESC
");
$stmt->execute([$consumable_id]);
$order_history = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all possible statuses
$stmt = $pdo->query("SELECT * FROM order_status ORDER BY id");
$all_statuses = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$consumable) {
    die('Consumable not found');
}
?>

<!-- Order History Modal -->
<div class="modal fade" id="orderHistoryModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header py-3">
                <h5 class="modal-title">Order History - <?php echo htmlspecialchars($consumable['item_name']); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Current Status -->
                <div class="alert alert-info mb-3">
                    <strong>Current Status:</strong> 
                    <span class="status-badge status-<?php echo $consumable['current_status_id']; ?>">
                        <?php echo htmlspecialchars($consumable['current_status']); ?>
                    </span>
                </div>

                <!-- Order History Table -->
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Quantity</th>
                                <th>Ordered By</th>
                                <th>Notes</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($order_history as $order): ?>
                            <tr>
                                <td><?php echo date('Y-m-d', strtotime($order['ordered_at'])); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $order['status_id']; ?>">
                                        <?php echo htmlspecialchars($order['status_name']); ?>
                                    </span>
                                </td>
                                <td><?php echo $order['quantity_ordered']; ?></td>
                                <td><?php echo htmlspecialchars($order['ordered_by']); ?></td>
                                <td>
                                    <small class="text-muted">
                                        <?php echo nl2br(htmlspecialchars($order['notes'])); ?>
                                    </small>
                                </td>
                                <td>
                                    <?php if ($order['status_id'] != 4): // Don't allow updates to completed orders ?>
                                    <button type="button" class="btn btn-sm btn-outline-primary" 
                                            onclick="showStatusUpdateModal(<?php echo $order['id']; ?>)">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- New Order Button -->
                <div class="mt-3">
                    <button type="button" class="btn btn-success" onclick="showNewOrderModal()">
                        <i class="bi bi-plus-lg"></i> New Order
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Status Update Modal -->
<div class="modal fade" id="statusUpdateModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Order Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="statusUpdateForm">
                    <input type="hidden" name="order_id" id="order_id">
                    <input type="hidden" name="consumable_id" value="<?php echo $consumable_id; ?>">
                    
                    <div class="mb-3">
                        <label for="status_id" class="form-label">New Status</label>
                        <select class="form-select" name="status_id" id="status_id" required>
                            <?php foreach ($all_statuses as $status): ?>
                            <option value="<?php echo $status['id']; ?>">
                                <?php echo htmlspecialchars($status['status_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea class="form-control" name="notes" id="notes" rows="3" 
                                  placeholder="Enter any notes about this status change"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="submitStatusUpdate()">Update Status</button>
            </div>
        </div>
    </div>
</div>

<!-- New Order Modal -->
<div class="modal fade" id="newOrderModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">New Order</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="newOrderForm">
                    <input type="hidden" name="consumable_id" value="<?php echo $consumable_id; ?>">
                    
                    <div class="mb-3">
                        <label for="new_status_id" class="form-label">Initial Status</label>
                        <select class="form-select" name="status_id" id="new_status_id" required>
                            <?php foreach ($all_statuses as $status): ?>
                            <option value="<?php echo $status['id']; ?>">
                                <?php echo htmlspecialchars($status['status_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="quantity_ordered" class="form-label">Quantity Ordered</label>
                        <input type="number" class="form-control" name="quantity_ordered" id="quantity_ordered" 
                               required min="1" value="<?php echo $consumable['reorder_threshold']; ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="ordered_by" class="form-label">Ordered By</label>
                        <input type="text" class="form-control" name="ordered_by" id="ordered_by" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="new_order_notes" class="form-label">Notes</label>
                        <textarea class="form-control" name="notes" id="new_order_notes" rows="3" 
                                  placeholder="Enter any notes about this order"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="submitNewOrder()">Create Order</button>
            </div>
        </div>
    </div>
</div>

<style>
.modal-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
    padding: 1rem;
}

.modal-title {
    margin: 0;
    font-size: 1.25rem;
    line-height: 1.5;
}

.modal-body {
    padding: 1rem;
}

.table th {
    background-color: #f8f9fa;
    position: sticky;
    top: 0;
    z-index: 1;
}

.status-badge {
    font-size: 0.875rem;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
}

.status-1 { background-color: #ffc107; color: #000; }
.status-2 { background-color: #17a2b8; color: #fff; }
.status-3 { background-color: #dc3545; color: #fff; }
.status-4 { background-color: #28a745; color: #fff; }
</style> 