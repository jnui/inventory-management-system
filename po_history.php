<?php
require_once 'auth_check.php';
require_once 'db_connection.php';

// Set a page title variable to be used in nav_template.php
$page_title = 'PO History';

// Include the navigation bar template
require_once 'nav_template.php';

// Fetch aggregated PO history data
try {
    $stmt = $pdo->prepare("SELECT 
            MIN(oh.ordered_at)        AS creation_date,
            oh.PO                      AS po_number,
            COUNT(DISTINCT oh.consumable_id) AS item_count,
            SUM(os.status_name = 'Complete')         AS complete_cnt,
            SUM(os.status_name = 'Ordered & Waiting') AS waiting_cnt,
            COUNT(*)                   AS row_cnt,
            MAX(oh.ordered_at)        AS last_date,
            GROUP_CONCAT(DISTINCT COALESCE(cm.vendor, '')) AS vendors
        FROM order_history oh
        JOIN order_status os ON oh.status_id = os.id
        LEFT JOIN consumable_materials cm ON oh.consumable_id = cm.id
        WHERE oh.PO IS NOT NULL AND oh.PO <> ''
        GROUP BY oh.PO
        ORDER BY creation_date DESC");
    $stmt->execute();
    $poRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Post-process each PO group to derive summary status and vendor label
    foreach ($poRows as &$row) {
        // Determine status
        if ($row['complete_cnt'] == $row['row_cnt']) {
            $row['summary_status'] = 'complete';
        } elseif ($row['waiting_cnt'] == $row['row_cnt']) {
            $row['summary_status'] = 'waiting';
        } else {
            $row['summary_status'] = 'partial';
        }

        // Determine vendor display
        $vendorsArray = array_filter(array_unique(explode(',', $row['vendors'])));
        $row['vendor_display'] = count($vendorsArray) === 1 ? $vendorsArray[0] : 'Multiple';
    }
    unset($row);
} catch (PDOException $e) {
    error_log('DB Error in po_history.php: ' . $e->getMessage());
    die('<div class="alert alert-danger">Database error occurred.</div>');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>PO History</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap CSS & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- DataTables CSS & JS -->
    <link href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css" rel="stylesheet">
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <!-- DataTables Buttons for export -->
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
    <link href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="custom.css" rel="stylesheet">
    <style>
        body { padding: 70px 10px 30px; }
        table.dataTable tbody tr.complete-row td { background-color: #d1e7dd; }
        table.dataTable tbody tr.waiting-row td { background-color: #fff3cd; }
        table.dataTable tbody tr.partial-row td { background-color: #cff4fc; }
    </style>
</head>
<body>
<div class="container-fluid">
    <h2 class="mb-4">PO History</h2>
    <div class="table-responsive">
        <table id="poHistoryTable" class="display table table-striped" style="width:100%">
            <thead>
                <tr>
                    <th>Creation Date</th>
                    <th>PO Number</th>
                    <th>Items Ordered</th>
                    <th>Status</th>
                    <th>Last Date</th>
                    <th>Vendor</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($poRows as $row): ?>
                <?php
                    $statusClass = $row['summary_status'] . '-row'; // e.g., complete-row
                ?>
                <tr class="<?php echo htmlspecialchars($statusClass); ?>">
                    <td><?php echo htmlspecialchars(date('Y-m-d', strtotime($row['creation_date']))); ?></td>
                    <td><a href="po_detail.php?po=<?php echo urlencode($row['po_number']); ?>" title="View PO details"><?php echo htmlspecialchars($row['po_number']); ?></a></td>
                    <td><?php echo htmlspecialchars($row['item_count']); ?></td>
                    <td><?php echo htmlspecialchars(ucfirst($row['summary_status'])); ?></td>
                    <td><?php echo htmlspecialchars(date('Y-m-d', strtotime($row['last_date']))); ?></td>
                    <td><?php echo htmlspecialchars($row['vendor_display']); ?></td>
                    <td>
                        <button class="btn btn-sm btn-primary" onclick="editPO('<?php echo htmlspecialchars($row['po_number']); ?>')">
                            <i class="bi bi-pencil-square"></i> Edit
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Edit PO Modal -->
<div class="modal fade" id="editPOModal" tabindex="-1" aria-labelledby="editPOModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editPOModalLabel">Edit PO</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Form for updating existing items -->
                <form id="updateItemsForm">
                    <input type="hidden" id="po_number_hidden" name="po_number" value="">
                    <h6>Existing Items</h6>
                    <div id="existingItemsContainer">
                        <p>Loading...</p>
                    </div>
                    <button type="button" class="btn btn-primary mt-2" onclick="saveChanges()">Save Changes</button>
                </form>

                <hr class="my-4">

                <!-- Form for adding new items -->
                <h6>Add New Item</h6>
                <form id="addItemForm">
                     <input type="hidden" id="po_number_add_hidden" name="po_number" value="">
                    <div class="mb-3">
                        <label for="consumable_id_add" class="form-label">Item</label>
                        <select class="form-control" id="consumable_id_add" name="consumable_id" style="width: 100%;" required></select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="quantity_add" class="form-label">Quantity</label>
                            <input type="number" class="form-control" id="quantity_add" name="quantity" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="notes_add" class="form-label">Notes</label>
                            <input type="text" class="form-control" id="notes_add" name="notes">
                        </div>
                    </div>
                    <button type="button" class="btn btn-success" onclick="addItem()">Add Item</button>
                </form>
            </div>
        </div>
    </div>
</div>


<!-- Bootstrap Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>


<script>
$(document).ready(function() {
    $('#poHistoryTable').DataTable({
        order: [[0, 'desc']],
        pageLength: 25,
        dom: 'Bfrtip',
        buttons: [
            'copy',
            { extend: 'csv', title: 'po_history_export' },
            { extend: 'excel', title: 'po_history_export' },
            'print'
        ]
    });

    $('#consumable_id_add').select2({
        dropdownParent: $('#editPOModal'),
        ajax: {
            url: 'get_items.php',
            dataType: 'json',
            delay: 250,
            data: function (params) { return { q: params.term }; },
            processResults: function (data) { return { results: data }; },
            cache: true
        },
        placeholder: 'Search for an item',
        minimumInputLength: 1
    });
});

function editPO(poNumber) {
    $('#editPOModalLabel').text('Edit PO: ' + poNumber);
    $('#po_number_hidden').val(poNumber);
    $('#po_number_add_hidden').val(poNumber);
    
    $('#existingItemsContainer').html('<p>Loading...</p>');
    
    $.ajax({
        url: 'get_po_details.php',
        type: 'POST',
        data: { po_number: poNumber },
        dataType: 'json',
        success: function(items) {
            let tableHtml = '<table class="table"><thead><tr><th>Item</th><th>Quantity</th><th>Notes</th><th>Action</th></tr></thead><tbody>';
            if (items.length > 0) {
                items.forEach(function(item) {
                    tableHtml += `<tr>
                        <td>${item.item_name}</td>
                        <td><input type="number" class="form-control" name="quantity[${item.id}]" value="${item.quantity_ordered}"></td>
                        <td><input type="text" class="form-control" name="notes[${item.id}]" value="${item.notes}"></td>
                        <td><button type="button" class="btn btn-danger btn-sm" onclick="removeItem(${item.id})">Remove</button></td>
                    </tr>`;
                });
            } else {
                tableHtml += '<tr><td colspan="4">No items found for this PO.</td></tr>';
            }
            tableHtml += '</tbody></table>';
            $('#existingItemsContainer').html(tableHtml);
            $('#editPOModal').modal('show');
        },
        error: function() {
            $('#existingItemsContainer').html('<p class="text-danger">Error loading items.</p>');
            $('#editPOModal').modal('show');
        }
    });
}

function saveChanges() {
    $.ajax({
        url: 'update_po.php',
        type: 'POST',
        data: $('#updateItemsForm').serialize() + '&action=update',
        success: function(response) {
            $('#editPOModal').modal('hide');
            location.reload();
        },
        error: function() { alert('Error saving changes.'); }
    });
}

function removeItem(orderId) {
    if(confirm('Are you sure you want to remove this item from the PO?')) {
        $.ajax({
            url: 'update_po.php',
            type: 'POST',
            data: { order_id: orderId, action: 'remove' },
            success: function(response) {
                // Refresh the modal content instead of reloading the whole page
                editPO($('#po_number_hidden').val());
            },
            error: function() { alert('Error removing item.'); }
        });
    }
}

function addItem() {
    $.ajax({
        url: 'update_po.php',
        type: 'POST',
        data: $('#addItemForm').serialize() + '&action=add',
        success: function(response) {
            // Reset the add form and refresh the modal content
            $('#addItemForm')[0].reset();
            $('#consumable_id_add').val(null).trigger('change');
            editPO($('#po_number_add_hidden').val());
        },
        error: function() { alert('Error adding item.'); }
    });
}
</script>
</body>
</html> 