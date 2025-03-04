<?php
// location_entry.php
require_once 'db_connection.php';

$error = "";
$editMode = false;

// Initialize default values for the form fields
$location = [
    'location_short_code' => '',
    'location_name'       => '',
    'location_description'=> ''
];

// Check if an ID is passed via GET; if so, load the record for editing
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $editMode = true;
    $stmt = $pdo->prepare("SELECT * FROM item_locations WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    if ($stmt->rowCount() > 0) {
        $location = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        $error = "Location not found.";
    }
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // If there's a hidden 'id' field, we're in edit mode
    if (isset($_POST['id']) && !empty($_POST['id'])) {
        $editMode = true;
    }

    // Validate required fields
    if (empty($_POST['location_short_code']) || empty($_POST['location_name'])) {
        $error = "Location short code and location name are required.";
    } else {
        try {
            if ($editMode) {
                // Update the existing location
                $stmt = $pdo->prepare("
                    UPDATE item_locations 
                    SET location_short_code = :location_short_code, 
                        location_name = :location_name, 
                        location_description = :location_description
                    WHERE id = :id
                ");
                $stmt->execute([
                    ':location_short_code' => $_POST['location_short_code'],
                    ':location_name'       => $_POST['location_name'],
                    ':location_description'=> $_POST['location_description'] ?? '',
                    ':id'                  => $_POST['id']
                ]);
            } else {
                // Insert a new location
                $stmt = $pdo->prepare("
                    INSERT INTO item_locations (location_short_code, location_name, location_description) 
                    VALUES (:location_short_code, :location_name, :location_description)
                ");
                $stmt->execute([
                    ':location_short_code' => $_POST['location_short_code'],
                    ':location_name'       => $_POST['location_name'],
                    ':location_description'=> $_POST['location_description'] ?? ''
                ]);
            }
            // Redirect to the list page after successful submission
            header("Location: location_list.php");
            exit;
        } catch (PDOException $e) {
            $error = "Error saving location: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= $editMode ? 'Edit' : 'Add New' ?> Location</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Opera compatibility -->
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <!-- Bootstrap CSS for responsive layout -->
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
    <h1 class="page-title"><?= $editMode ? 'Edit' : 'Add New' ?> Location</h1>
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
    <form action="location_entry.php" method="POST">
        <?php if ($editMode): ?>
            <!-- Include a hidden field with the entry ID in edit mode -->
            <input type="hidden" name="id" value="<?= htmlspecialchars($location['id']) ?>">
        <?php endif; ?>
        <div class="mb-3">
            <label for="location_short_code" class="form-label">Location Short Code</label>
            <input type="text" name="location_short_code" id="location_short_code" class="form-control" placeholder="Enter short code (e.g., WH1)" required value="<?= htmlspecialchars($location['location_short_code']) ?>">
        </div>
        <div class="mb-3">
            <label for="location_name" class="form-label">Location Name</label>
            <input type="text" name="location_name" id="location_name" class="form-control" placeholder="Enter location name" required value="<?= htmlspecialchars($location['location_name']) ?>">
        </div>
        <div class="mb-3">
            <label for="location_description" class="form-label">Location Description</label>
            <textarea name="location_description" id="location_description" class="form-control" placeholder="Enter description (optional)"><?= htmlspecialchars($location['location_description']) ?></textarea>
        </div>
        <button type="submit" class="btn btn-success"><?= $editMode ? 'Update' : 'Add' ?> Location</button>
        <a href="location_list.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>
<!-- Optionally include Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>