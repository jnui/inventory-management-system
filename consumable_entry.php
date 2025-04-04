<?php
// Enable error reporting
error_reporting(E_ALL); // Report all errors, warnings, and notices
ini_set('display_errors', 1); // Display errors on the screen
ini_set('display_startup_errors', 1); // Display startup errors

require_once 'db_connection.php';

$error = "";
$editMode = false;

// Initialize values for the form fields.
$consumable = [
    'item_type'            => '',
    'item_name'            => '',
    'item_description'     => '',
    'normal_item_location' => '',
    'item_units_whole'     => '',
    'item_units_part'      => '',
    'qty_parts_per_whole'  => '',
    'composition_description' => '',
    'reorder_threshold'    => 0
];

// Check if an ID is passed via GET; if so, load the record for editing.
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $editMode = true;
    $stmt = $pdo->prepare("SELECT * FROM consumable_materials WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    if ($stmt->rowCount() > 0) {
        $consumable = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        $error = "Consumable material not found.";
    }
}

// Process form submission.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // If there's a hidden 'id' field, we're in edit mode.
    if (isset($_POST['id']) && !empty($_POST['id'])) {
        $editMode = true;
    }
    // Basic validation: required fields.
    if (empty($_POST['item_type']) || empty($_POST['item_name']) || empty($_POST['normal_item_location'])) {
        $error = "Item Type, Item Name, and Normal Item Location are required.";
    } else {
        try {
            if ($editMode) {
                // Perform an UPDATE if editing.
                $stmt = $pdo->prepare("
                    UPDATE consumable_materials 
                    SET item_type = :item_type, 
                        item_name = :item_name, 
                        item_description = :item_description,
                        normal_item_location = :normal_item_location, 
                        item_units_whole = :item_units_whole, 
                        item_units_part = :item_units_part,
                        qty_parts_per_whole = :qty_parts_per_whole,
                        composition_description = :composition_description,
                        reorder_threshold = :reorder_threshold
                    WHERE id = :id
                ");
                $stmt->execute([
                    ':item_type'            => $_POST['item_type'],
                    ':item_name'            => $_POST['item_name'],
                    ':item_description'     => $_POST['item_description'] ?? '',
                    ':normal_item_location' => $_POST['normal_item_location'],
                    ':item_units_whole'     => $_POST['item_units_whole'] ?? '',
                    ':item_units_part'      => $_POST['item_units_part'] ?? '',
                    ':qty_parts_per_whole'  => $_POST['qty_parts_per_whole'] ?? null,
                    ':composition_description' => $_POST['composition_description'] ?? '',
                    ':reorder_threshold'    => $_POST['reorder_threshold'] ?? 0,
                    ':id'                   => $_POST['id']
                ]);
                
                // Redirect back to list with scroll_to parameter
                header("Location: consumable_list.php?scroll_to=" . $_POST['id']);
                exit;
            } else {
                // Otherwise, perform an INSERT.
                $stmt = $pdo->prepare("
                    INSERT INTO consumable_materials 
                        (item_type, item_name, item_description, normal_item_location, item_units_whole, item_units_part, qty_parts_per_whole, composition_description, reorder_threshold)
                    VALUES 
                        (:item_type, :item_name, :item_description, :normal_item_location, :item_units_whole, :item_units_part, :qty_parts_per_whole, :composition_description, :reorder_threshold)
                ");
                $stmt->execute([
                    ':item_type'            => $_POST['item_type'],
                    ':item_name'            => $_POST['item_name'],
                    ':item_description'     => $_POST['item_description'] ?? '',
                    ':normal_item_location' => $_POST['normal_item_location'],
                    ':item_units_whole'     => $_POST['item_units_whole'] ?? '',
                    ':item_units_part'      => $_POST['item_units_part'] ?? '',
                    ':qty_parts_per_whole'  => $_POST['qty_parts_per_whole'] ?? null,
                    ':composition_description' => $_POST['composition_description'] ?? '',
                    ':reorder_threshold'    => $_POST['reorder_threshold'] ?? 0
                ]);
                
                // Get the ID of the newly inserted item
                $newItemId = $pdo->lastInsertId();
                header("Location: consumable_list.php?scroll_to=" . $newItemId);
                exit;
            }
        } catch (PDOException $e) {
            $error = "Error saving consumable material: " . $e->getMessage();
        }
    }
}

// Retrieve locations for the dropdown.
try {
    $stmt = $pdo->query("SELECT id, location_name FROM item_locations ORDER BY location_name ASC");
    $locations = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= $editMode ? 'Edit' : 'Add New' ?> Consumable Material</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Opera compatibility -->
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="assets/img/favicon.svg">
    <link rel="alternate icon" type="image/x-icon" href="assets/img/favicon.ico">
    <link rel="mask-icon" href="assets/img/favicon.svg" color="#0d6efd">
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap CSS for responsive design -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Custom CSS for iPad optimization -->
    <link href="custom.css" rel="stylesheet">
</head>
<body>
<!-- Navigation Bar -->
<div class="navigation-bar">
    <a href="javascript:history.back()" class="back-button">
        <i class="bi bi-chevron-left"></i>
    </a>
    <h1 class="page-title"><?= $editMode ? 'Edit' : 'Add New' ?> Consumable Material</h1>
    <a href="index.php" class="home-button">
        <i class="bi bi-house-fill"></i>
    </a>
</div>

<div class="container content-container">
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>
    <form action="consumable_entry.php" method="POST">
        <?php if ($editMode): ?>
            <input type="hidden" name="id" value="<?= htmlspecialchars($consumable['id'] ?? '') ?>">
        <?php endif; ?>
        <div class="mb-3">
            <label for="item_type" class="form-label">Item Type</label>
            <input type="text" name="item_type" id="item_type" class="form-control" placeholder="Enter item type" required value="<?= htmlspecialchars($consumable['item_type'] ?? '') ?>">
        </div>
        <div class="mb-3">
            <label for="item_name" class="form-label">Item Name</label>
            <input type="text" name="item_name" id="item_name" class="form-control" placeholder="Enter item name" required value="<?= htmlspecialchars($consumable['item_name'] ?? '') ?>">
        </div>
        <div class="mb-3">
            <label for="item_description" class="form-label">Item Description</label>
            <textarea name="item_description" id="item_description" class="form-control" placeholder="Enter item description"><?= htmlspecialchars($consumable['item_description'] ?? '') ?></textarea>
        </div>
        <div class="mb-3">
            <label for="normal_item_location" class="form-label">Normal Item Location</label>
            <select name="normal_item_location" id="normal_item_location" class="form-select" required>
                <option value="">Select Location</option>
                <?php foreach ($locations as $loc): ?>
                    <option value="<?= htmlspecialchars($loc['id'] ?? '') ?>" <?= ($loc['id'] == ($consumable['normal_item_location'] ?? null)) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($loc['location_name'] ?? '') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <label for="item_units_whole" class="form-label">Units (Whole)</label>
            <input type="text" name="item_units_whole" id="item_units_whole" class="form-control" placeholder="e.g., each, stick, roll" value="<?= htmlspecialchars($consumable['item_units_whole'] ?? '') ?>">
        </div>
        <div class="mb-3">
            <label for="item_units_part" class="form-label">Units (Part)</label>
            <input type="text" name="item_units_part" id="item_units_part" class="form-control" placeholder="e.g., feet, inches" value="<?= htmlspecialchars($consumable['item_units_part'] ?? '') ?>">
        </div>
        <div class="mb-3">
            <label for="qty_parts_per_whole" class="form-label">Qty Parts Per Whole</label>
            <input type="number" name="qty_parts_per_whole" id="qty_parts_per_whole" class="form-control" placeholder="Enter quantity of parts per whole" value="<?= htmlspecialchars($consumable['qty_parts_per_whole'] ?? '') ?>">
        </div>
        <div class="mb-3">
            <label for="composition_description" class="form-label">Composition Description</label>
            <textarea name="composition_description" id="composition_description" class="form-control" placeholder="Enter composition description"><?= htmlspecialchars($consumable['composition_description'] ?? '') ?></textarea>
        </div>
        <div class="mb-3">
            <label for="reorder_threshold" class="form-label">Reorder Threshold</label>
            <input type="number" name="reorder_threshold" id="reorder_threshold" class="form-control" placeholder="Enter quantity at which to reorder" value="<?= htmlspecialchars($consumable['reorder_threshold'] ?? 0) ?>" min="0">
            <div class="form-text">When whole quantity falls below this number, the item will be highlighted in the list.</div>
        </div>
        <div class="mb-3">
            <label for="item_notes" class="form-label">Notes</label>
            <textarea class="form-control" name="item_notes" id="item_notes" rows="3"><?= htmlspecialchars($consumable['item_notes'] ?? '') ?></textarea>
        </div>

        <!-- Image Upload Section -->
        <div class="mb-3">
            <label class="form-label">Item Image</label>
            <div class="d-flex align-items-center">
                <?php if (!empty($consumable['image_thumb_50'])): ?>
                    <img src="<?= htmlspecialchars($consumable['image_thumb_50']) ?>" alt="Item thumbnail" class="me-3" style="max-width: 50px;">
                <?php endif; ?>
                <button type="button" class="btn btn-primary" id="uploadImageBtn">
                    <i class="bi bi-camera"></i> <?= !empty($consumable['image_thumb_50']) ? 'Change Image' : 'Add Image' ?>
                </button>
            </div>
            <input type="file" id="imageInput" accept="image/*" capture="environment" style="display: none;">
            <input type="hidden" name="image_full" value="<?= htmlspecialchars($consumable['image_full'] ?? '') ?>">
            <input type="hidden" name="image_thumb_50" value="<?= htmlspecialchars($consumable['image_thumb_50'] ?? '') ?>">
            <input type="hidden" name="image_thumb_150" value="<?= htmlspecialchars($consumable['image_thumb_150'] ?? '') ?>">
        </div>

        <button type="submit" class="btn btn-success"><?= $editMode ? 'Update' : 'Add' ?> Consumable Material</button>
        <a href="consumable_list.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const uploadBtn = document.getElementById('uploadImageBtn');
    const imageInput = document.getElementById('imageInput');
    const form = document.querySelector('form');
    
    uploadBtn.addEventListener('click', function() {
        imageInput.click();
    });
    
    imageInput.addEventListener('change', function(e) {
        if (e.target.files.length > 0) {
            const formData = new FormData();
            formData.append('image', e.target.files[0]);
            formData.append('consumable_id', '<?= $consumable['id'] ?? '' ?>');
            
            fetch('scripts/process_image.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update hidden inputs
                    document.querySelector('input[name="image_full"]').value = data.full_path;
                    document.querySelector('input[name="image_thumb_50"]').value = data.thumb_50_path;
                    document.querySelector('input[name="image_thumb_150"]').value = data.thumb_150_path;
                    
                    // Update preview
                    const preview = document.querySelector('.me-3');
                    if (preview) {
                        preview.src = data.thumb_50_path;
                    } else {
                        const newPreview = document.createElement('img');
                        newPreview.src = data.thumb_50_path;
                        newPreview.alt = 'Item thumbnail';
                        newPreview.className = 'me-3';
                        newPreview.style.maxWidth = '50px';
                        uploadBtn.parentNode.insertBefore(newPreview, uploadBtn);
                    }
                    
                    // Update button text
                    uploadBtn.innerHTML = '<i class="bi bi-camera"></i> Change Image';
                } else {
                    alert('Error uploading image: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error uploading image');
            });
        }
    });
});
</script>
</body>
</html>