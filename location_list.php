<?php
// location_list.php
// Include authentication check
require_once 'auth_check.php';

// Include the database connection file
require_once 'db_connection.php';

try {
    // Query to retrieve all locations, ordered by location name
    $stmt = $pdo->query("SELECT id, location_short_code, location_name, location_description FROM item_locations ORDER BY location_name ASC");
    $locations = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Locations</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Opera compatibility -->
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <!-- Bootstrap CSS for responsiveness -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Custom CSS for iPad optimization -->
    <link href="custom.css" rel="stylesheet">
</head>
<body>
<?php 
// Set the page title for the navigation bar
$page_title = 'Locations';
// Include the navigation template
include 'nav_template.php'; 
?>

<div class="container content-container">
    <a href="location_entry.php" class="btn btn-primary mb-3">Add New Location</a>
    <?php if ($locations): ?>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Short Code</th>
                <th>Name</th>
                <th>Description</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($locations as $location): ?>
            <tr>
                <td><?= htmlspecialchars($location['id']) ?></td>
                <td><?= htmlspecialchars($location['location_short_code']) ?></td>
                <td><?= htmlspecialchars($location['location_name']) ?></td>
                <td><?= htmlspecialchars($location['location_description']) ?></td>
                <td>
                    <a href="location_entry.php?id=<?= htmlspecialchars($location['id']) ?>" class="btn btn-sm btn-warning">Edit</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
        <p>No locations found. <a href="location_entry.php">Add one now</a>.</p>
    <?php endif; ?>
</div>
<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>