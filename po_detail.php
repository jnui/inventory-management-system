<?php
require_once 'auth_check.php';
require_once 'db_connection.php';

// Validate and retrieve PO number from URL
if (!isset($_GET['po'])) {
    die('<div class="alert alert-danger m-3">PO number not specified.</div>');
}
$poNumber = trim($_GET['po']);
if ($poNumber === '' || strlen($poNumber) > 50) {
    die('<div class="alert alert-danger m-3">Invalid PO number supplied.</div>');
}

// Page title for nav template
$page_title = 'PO Detail';
require_once 'nav_template.php';

// Fetch PO detail items
try {
    $stmt = $pdo->prepare("SELECT
            oh.id,
            cm.item_name,
            cm.diameter,
            cm.vendor,
            oh.quantity_ordered,
            oh.received_quantity AS qty_recvd,
            os.status_name,
            oh.notes,
            oh.ordered_at,
            (SELECT MAX(change_date)
             FROM inventory_change_entries ice
             WHERE ice.item_notes LIKE CONCAT('%Order ID: ', oh.id, '%')) AS date_recvd
        FROM order_history oh
        JOIN order_status os ON oh.status_id = os.id
        LEFT JOIN consumable_materials cm ON oh.consumable_id = cm.id
        WHERE oh.PO = :po
        ORDER BY cm.item_name ASC");
    $stmt->execute([':po' => $poNumber]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('DB Error in po_detail.php: ' . $e->getMessage());
    die('<div class="alert alert-danger m-3">Database error occurred.</div>');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>PO Detail</title>
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
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="m-0">PO Detail – <?php echo htmlspecialchars($poNumber); ?></h2>
        <a href="po_history.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Back to PO History</a>
    </div>

    <?php if (empty($items)): ?>
        <div class="alert alert-info">No items found for PO <?php echo htmlspecialchars($poNumber); ?>.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table id="poDetailTable" class="display table table-striped" style="width:100%">
                <thead>
                    <tr>
                        <th>Item Name</th>
                        <th>DIA/Size</th>
                        <th>Quantity Ordered</th>
                        <th>Qty Recvd</th>
                        <th>Date Recvd</th>
                        <th>Status</th>
                        <th>Vendor</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($items as $row): ?>
                    <?php
                        // Determine row class based on status
                        $statusLower = strtolower($row['status_name']);
                        $rowClass = ($statusLower === 'complete' ? 'complete-row' : ($statusLower === 'ordered & waiting' ? 'waiting-row' : 'partial-row'));
                    ?>
                    <tr class="<?php echo $rowClass; ?>">
                        <td><?php echo htmlspecialchars($row['item_name']); ?></td>
                        <td><?php echo htmlspecialchars(rtrim(rtrim($row['diameter'], '0'), '.')); ?></td>
                        <td><?php echo htmlspecialchars($row['quantity_ordered']); ?></td>
                        <td><?php echo htmlspecialchars($row['qty_recvd']); ?></td>
                        <td><?php echo $row['date_recvd'] ? htmlspecialchars(date('Y-m-d', strtotime($row['date_recvd']))) : '—'; ?></td>
                        <td><?php echo htmlspecialchars($row['status_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['vendor']); ?></td>
                        <td><?php echo htmlspecialchars($row['notes']); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Bootstrap Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
$(document).ready(function() {
    $('#poDetailTable').DataTable({
        order: [[0, 'asc']],
        pageLength: 25,
        dom: 'Bfrtip',
        buttons: [
            'copy',
            {
                extend: 'csv',
                title: 'po_detail_export_<?php echo preg_replace('/[^A-Za-z0-9_-]/', '', $poNumber); ?>'
            },
            {
                extend: 'excel',
                title: 'po_detail_export_<?php echo preg_replace('/[^A-Za-z0-9_-]/', '', $poNumber); ?>'
            },
            'print'
        ]
    });
});
</script>
</body>
</html> 