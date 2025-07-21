<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'db_connection.php';

// --- ALL PHP LOGIC BEFORE ANY HTML OUTPUT ---

$step = 1;
$po_number = '';
$vendor = '';
$items_in_po = [];
$error = null;

// Finish PO and clear session
if (isset($_GET['finish'])) {
    unset($_SESSION['po_number']);
    unset($_SESSION['vendor']);
    header("Location: po_numbers.php?finished=true");
    exit;
}

// Step 1: Create a new PO number
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_po'])) {
    $po_number = trim($_POST['po_number']);
    $vendor = trim($_POST['vendor']);

    if (!empty($po_number) && !empty($vendor)) {
        // Check if PO number already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM order_history WHERE PO = ?");
        $stmt->execute([$po_number]);
        if ($stmt->fetchColumn() > 0) {
            $error = "PO Number '$po_number' already exists. Please choose a different one.";
        } else {
            $_SESSION['po_number'] = $po_number;
            $_SESSION['vendor'] = $vendor;
            header("Location: po_numbers.php");
            exit;
        }
    } else {
        $error = "Please provide both a PO Number and a Vendor.";
    }
}

// Step 2: Add an item to the PO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_item'])) {
    if (isset($_SESSION['po_number'])) {
        $po_number = $_SESSION['po_number'];
        $consumable_id = $_POST['consumable_id'];
        $quantity = $_POST['quantity'];
        $notes = $_POST['notes'];
        $ordered_by = $_SESSION['username'] ?? 'System User';

        if (!empty($consumable_id) && !empty($quantity)) {
            $stmt = $pdo->prepare("
                INSERT INTO order_history (consumable_id, status_id, quantity_ordered, notes, ordered_by, PO)
                VALUES (?, 2, ?, ?, ?, ?) 
            ");
            $stmt->execute([$consumable_id, $quantity, $notes, $ordered_by, $po_number]);
            header("Location: po_numbers.php");
            exit;
        } else {
            $error = "Please select an item and enter a quantity.";
        }
    }
}

// --- LOGIC FOR DISPLAYING THE PAGE ---

// Check if a PO is in progress
if (isset($_SESSION['po_number'])) {
    $step = 2;
    $po_number = $_SESSION['po_number'];
    $vendor = $_SESSION['vendor'];

    // Fetch items already added to this PO
    $stmt = $pdo->prepare("
        SELECT oh.id, c.item_name, oh.quantity_ordered, oh.notes
        FROM order_history oh
        JOIN consumable_materials c ON oh.consumable_id = c.id
        WHERE oh.PO = ?
        ORDER BY oh.id DESC
    ");
    $stmt->execute([$po_number]);
    $items_in_po = $stmt->fetchAll();
}

// --- START HTML OUTPUT ---
require_once 'nav_template.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Purchase Order</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        .select2-container .select2-selection--single {
            height: 38px;
        }
    </style>
</head>
<body>
    <div class="container py-4 mt-5">
        <h1>Create Purchase Order</h1>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>
        <?php if (isset($_GET['finished'])): ?>
            <div class="alert alert-success">Purchase Order created successfully!</div>
        <?php endif; ?>


        <?php if ($step === 1): ?>
        <div class="card">
            <div class="card-header">Step 1: Enter PO Details</div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label for="po_number" class="form-label">PO Number</label>
                        <input type="text" class="form-control" id="po_number" name="po_number" required>
                    </div>
                    <div class="mb-3">
                        <label for="vendor" class="form-label">Vendor</label>
                        <input type="text" class="form-control" id="vendor" name="vendor" required>
                    </div>
                    <button type="submit" name="create_po" class="btn btn-primary">Start PO</button>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($step === 2): ?>
        <div class="card mb-4">
            <div class="card-header">
                Step 2: Add Items to PO: <strong><?= htmlspecialchars($po_number) ?></strong> (Vendor: <?= htmlspecialchars($vendor) ?>)
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label for="consumable_id" class="form-label">Item</label>
                        <select class="form-control" id="consumable_id" name="consumable_id" required></select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="quantity" class="form-label">Quantity</label>
                            <input type="number" class="form-control" id="quantity" name="quantity" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <input type="text" class="form-control" id="notes" name="notes">
                        </div>
                    </div>
                    <button type="submit" name="add_item" class="btn btn-success">Add Item to PO</button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">Items in this PO</div>
            <div class="card-body">
                <?php if (empty($items_in_po)): ?>
                    <p>No items have been added to this PO yet.</p>
                <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Item Name</th>
                            <th>Quantity Ordered</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items_in_po as $item): ?>
                        <tr>
                            <td><?= htmlspecialchars($item['item_name']) ?></td>
                            <td><?= htmlspecialchars($item['quantity_ordered']) ?></td>
                            <td><?= htmlspecialchars($item['notes']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
                <a href="po_numbers.php?finish=true" class="btn btn-primary mt-3">Finish PO</a>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
    $(document).ready(function() {
        $('#consumable_id').select2({
            ajax: {
                url: 'get_items.php',
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {
                        q: params.term // search term
                    };
                },
                processResults: function (data) {
                    return {
                        results: data
                    };
                },
                cache: true
            },
            placeholder: 'Search for an item',
            minimumInputLength: 1,
            templateResult: formatRepo,
            templateSelection: formatRepoSelection
        });

        function formatRepo (repo) {
            if (repo.loading) {
                return repo.text;
            }

            var $container = $(
                "<div class='select2-result-repository clearfix'>" +
                    "<div class='select2-result-repository__title'></div>" +
                    "<div class='select2-result-repository__description'></div>" +
                "</div>"
            );

            $container.find(".select2-result-repository__title").text(repo.text);
            $container.find(".select2-result-repository__description").text("Current Stock: " + repo.stock);

            return $container;
        }

        function formatRepoSelection (repo) {
            return repo.text;
        }
    });
    </script>
</body>
</html>
