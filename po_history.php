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
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Bootstrap Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
$(document).ready(function() {
    $('#poHistoryTable').DataTable({
        order: [[0, 'desc']],
        pageLength: 25,
        dom: 'Bfrtip',
        buttons: [
            'copy',
            {
                extend: 'csv',
                title: 'po_history_export'
            },
            {
                extend: 'excel',
                title: 'po_history_export'
            },
            'print'
        ]
    });
});
</script>
</body>
</html> 