<?php
session_start();
require_once 'db_connection.php';

// Function to check if user has read-only access
function is_readonly() {
    if (!isset($_SESSION['user_role'])) {
        return true; // Default to read-only if no role is set
    }
    return $_SESSION['user_role'] === 'readonly';
}

// Set user role for testing (remove this in production)
if (!isset($_SESSION['user_role'])) {
    $_SESSION['user_role'] = 'admin'; // or 'readonly' to test read-only access
}

try {
    // Retrieve orders with status "ordered & waiting" along with item details
    $stmt = $pdo->query("
        SELECT 
            oh.id as order_id,
            oh.quantity_ordered,
            oh.received_quantity,
            oh.notes as order_notes,
            oh.PO as po_number,
            cm.id as item_id,
            cm.item_name,
            cm.whole_quantity as current_quantity,
            cm.reorder_threshold,
            cm.optimum_quantity,
            os.status_name,
            os.description as status_description
        FROM order_history oh
        JOIN consumable_materials cm ON oh.consumable_id = cm.id
        JOIN order_status os ON oh.status_id = os.id
        WHERE os.status_name = 'ordered & waiting'
        ORDER BY oh.ordered_at DESC
    ");
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Get all consumable materials
$stmt = $pdo->query("SELECT id, item_name, item_type, item_units_whole, whole_quantity, reorder_threshold FROM consumable_materials ORDER BY item_name");
$consumables = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all locations
$stmt = $pdo->query("SELECT id, location_name FROM item_locations ORDER BY location_name");
$locations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Receiving Orders</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
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
    
    <!-- DataTables -->
    <link href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css" rel="stylesheet">
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    
    <!-- Custom CSS -->
    <link href="custom.css" rel="stylesheet">
</head>
<body>
    <?php
    $page_title = 'Receiving Orders';
    include 'nav_template.php';
    ?>

    <div class="container-fluid content-container">
        <h1 class="mb-4">Receiving Orders</h1>
        <div class="table-responsive">
            <table id="receivingTable" class="table table-striped">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Item Name</th>
                        <th>PO Number</th>
                        <th>Qty Ordered</th>
                        <th>Qty Recvd So Far</th>
                        <th>Qty To Receive</th>
                        <th>Current Stock</th>
                        <th>Reorder Level</th>
                        <th>Notes</th>
                        <th>Actions</th>
                        <th>PO Receive</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td><?= htmlspecialchars($order['order_id']) ?></td>
                            <td>
                                <?= htmlspecialchars($order['item_name']) ?>
                                <?php if ($order['received_quantity'] > 0 && $order['received_quantity'] < $order['quantity_ordered']): ?>
                                    <span class="badge bg-warning text-dark ms-2">Partial Fill</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($order['po_number'] ?? 'N/A') ?></td>
                            <td class="text-center"><?= htmlspecialchars($order['quantity_ordered']) ?></td>
                            <td class="text-center"><?= htmlspecialchars($order['received_quantity']) ?></td>
                            <td class="text-center"><?= htmlspecialchars($order['quantity_ordered'] - $order['received_quantity']) ?></td>
                            <td class="text-center <?= $order['current_quantity'] < $order['reorder_threshold'] ? 'quantity-warning' : 'quantity-good' ?>">
                                <?= htmlspecialchars($order['current_quantity']) ?>
                            </td>
                            <td class="text-center"><?= htmlspecialchars($order['reorder_threshold']) ?></td>
                            <td class="notes-cell" onclick="showNotesModal('<?= htmlspecialchars(addslashes($order['item_name'])) ?>', '<?= htmlspecialchars(addslashes($order['order_notes'])) ?>')" title="Click to view full notes">
                                <?= htmlspecialchars($order['order_notes']) ?>
                            </td>
                            <td>
                                <?php if (!is_readonly()): ?>
                                <div class="action-buttons">
                                    <button class="btn btn-success btn-sm receive-btn" 
                                            data-order-id="<?= $order['order_id'] ?>"
                                            data-item-id="<?= $order['item_id'] ?>"
                                            data-item-name="<?= htmlspecialchars($order['item_name']) ?>"
                                            data-order-qty="<?= $order['quantity_ordered'] ?>"
                                            data-received-qty="<?= $order['received_quantity'] ?>"
                                            data-po-number="<?= htmlspecialchars($order['po_number'] ?? 'N/A') ?>">
                                        <i class="bi bi-box-arrow-in-down"></i> Receive
                                    </button>
                                </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($order['po_number'])): ?>
                                    <button type="button" class="btn btn-sm btn-primary po-receive-btn" data-po-number="<?= htmlspecialchars($order['po_number']) ?>">
                                        PO Receive
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Notes Modal -->
    <div class="modal fade" id="notesModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Notes for <span id="notesModalItemName"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="notesModalContent" style="white-space: pre-wrap;"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Receive Modal -->
    <div class="modal fade" id="receiveModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Receive Order</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="receiveForm">
                        <input type="hidden" id="orderId" name="orderId">
                        <input type="hidden" id="itemId" name="itemId">
                        
                        <div class="mb-3">
                            <label class="form-label">Item</label>
                            <input type="text" class="form-control" id="itemName" readonly>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">PO Number</label>
                            <input type="text" class="form-control" id="poNumber" readonly>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Ordered Quantity</label>
                            <input type="text" class="form-control" id="orderedQuantity" readonly>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Received So Far</label>
                            <input type="text" class="form-control" id="receivedQtySoFar" readonly>
                        </div>
                        
                        <div class="mb-3">
                            <label for="receivedQuantity" class="form-label">Received Quantity</label>
                            <input type="number" class="form-control" id="receivedQuantity" name="receivedQuantity" required min="0">
                        </div>
                        
                        <div class="mb-3">
                            <label for="receiveNotes" class="form-label">Notes</label>
                            <textarea class="form-control" id="receiveNotes" name="receiveNotes" rows="3" 
                                    placeholder="Enter any discrepancies or issues..."></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" id="confirmReceive">Confirm Receipt</button>
                </div>
            </div>
        </div>
    </div>

    <!-- PO Receive Modal -->
    <div id="poReceiveModal" class="modal fade" tabindex="-1">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <form id="poReceiveForm" action="bulk_receive.php" method="post">
            <div class="modal-header">
              <h5 class="modal-title">Bulk Receive PO <span id="modalPoNumber"></span></h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
              <input type="hidden" name="po_number" id="formPoNumber">
              <table class="table">
                <thead>
                  <tr>
                    <th>Item Name</th>
                    <th>Qty Ordered</th>
                    <th>Qty Recvd So Far</th>
                    <th>Qty To Receive</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody id="poItemsTableBody">
                </tbody>
              </table>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" class="btn btn-primary">Submit Received</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Initialize DataTable
            var table = $('#receivingTable').DataTable({
                scrollX: true,
                scrollY: '60vh',
                scrollCollapse: true,
                paging: false,
                ordering: true,
                info: true,
                columnDefs: [
                    { className: "dt-center", targets: [2,3,4,5] }
                ]
            });

            // Initialize modals
            var receiveModal = new bootstrap.Modal(document.getElementById('receiveModal'));
            var notesModal = new bootstrap.Modal(document.getElementById('notesModal'));
            
            // Handle receive button click
            $('.receive-btn').on('click', function() {
                var btn = $(this);
                var orderId = btn.data('order-id');
                var itemId = btn.data('item-id');
                var itemName = btn.data('item-name');
                var orderQty = btn.data('order-qty');
                var poNumber = btn.data('po-number');
                
                $('#orderId').val(orderId);
                $('#itemId').val(itemId);
                $('#itemName').val(itemName);
                $('#orderedQuantity').val(orderQty);
                $('#receivedQtySoFar').val(btn.data('received-qty'));
                var outstanding = orderQty - btn.data('received-qty');
                if(outstanding < 0) outstanding = 0;
                $('#receivedQuantity').attr('max', outstanding).val(outstanding);
                $('#poNumber').val(poNumber || 'N/A');
                
                receiveModal.show();
            });
            
            // Handle confirm receive button
            $('#confirmReceive').on('click', function() {
                var receivedQty = $('#receivedQuantity').val();
                if (!receivedQty) {
                    alert('Please enter the received quantity');
                    return;
                }

                var formData = {
                    orderId: $('#orderId').val(),
                    itemId: $('#itemId').val(),
                    receivedQuantity: receivedQty,
                    notes: $('#receiveNotes').val()
                };
                
                // Send to process_receive.php
                $.ajax({
                    url: 'process_receive.php',
                    method: 'POST',
                    data: formData,
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            alert('Order received successfully');
                            receiveModal.hide();
                            location.reload(); // Refresh the page to show updated status
                        } else {
                            alert('Error: ' + response.message);
                        }
                    },
                    error: function() {
                        alert('Error processing the receipt. Please try again.');
                    }
                });
            });

            // PO Receive logic
            $('.po-receive-btn').on('click', function() {
                var poNumber = $(this).data('po-number');
                $('#modalPoNumber').text(poNumber);
                $('#formPoNumber').val(poNumber);
                $('#poItemsTableBody').empty();
                $.getJSON('get_po_items.php', { po_number: poNumber }, function(data) {
                    data.forEach(function(item) {
                        var isComplete   = item.status_id == 4; // Complete status
                        var outstanding  = Math.max(item.quantity_ordered - item.received_quantity, 0);
                        var rowClass     = isComplete ? 'table-success' : (item.received_quantity > 0 ? 'table-warning' : '');
                        var statusText   = isComplete ? 'Complete' : (item.received_quantity > 0 ? 'Partial' : 'Open');
                        var qtyInput;
                        if (isComplete) {
                            qtyInput = '<input type="number" class="form-control" value="0" disabled>';
                        } else {
                            qtyInput = '<input type="number" name="received_qty[' + item.order_id + ']" min="0" max="' + outstanding + '" class="form-control" value="' + outstanding + '">';
                        }
                        var rowHtml = '<tr class="' + rowClass + '" data-order-id="' + item.order_id + '">' +
                            '<td>' + item.item_name + '</td>' +
                            '<td class="text-center">' + item.quantity_ordered + '</td>' +
                            '<td class="text-center">' + item.received_quantity + '</td>' +
                            '<td>' + qtyInput + '</td>' +
                            '<td class="text-center">' + statusText + '</td>' +
                        '</tr>';
                        $('#poItemsTableBody').append(rowHtml);
                    });
                    var poModal = new bootstrap.Modal(document.getElementById('poReceiveModal'));
                    poModal.show();
                });
            });
        });

        // Function to show notes modal
        function showNotesModal(itemName, notes) {
            document.getElementById('notesModalItemName').textContent = itemName;
            document.getElementById('notesModalContent').textContent = notes || 'No notes available';
            new bootstrap.Modal(document.getElementById('notesModal')).show();
        }
    </script>
</body>
</html> 