<?php
// employee_list.php
// Include authentication check
require_once 'auth_check.php';

// Check if user has admin role
require_admin();

// Include the database connection file
require_once 'db_connection.php';

try {
    // Query to retrieve all employees, ordered by first name
    $stmt = $pdo->query("SELECT id, first_name FROM employees ORDER BY first_name ASC");
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Employees</title>
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
$page_title = 'Employees';
// Include the navigation template
include 'nav_template.php'; 
?>

<div class="container content-container">
    <?php if (is_admin()): ?>
    <a href="employee_entry.php" class="btn btn-primary mb-3">Add New Employee</a>
    <?php endif; ?>
    <?php if ($employees): ?>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>First Name</th>
                <?php if (is_admin()): ?>
                <th>Actions</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($employees as $employee): ?>
            <tr>
                <td><?= htmlspecialchars($employee['id']) ?></td>
                <td><?= htmlspecialchars($employee['first_name']) ?></td>
                <?php if (is_admin()): ?>
                <td>
                    <a href="employee_entry.php?id=<?= htmlspecialchars($employee['id']) ?>" class="btn btn-sm btn-warning">Edit</a>
                </td>
                <?php endif; ?>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
        <p>No employees found. <?php if (is_admin()): ?><a href="employee_entry.php">Add one now</a>.<?php endif; ?></p>
    <?php endif; ?>
</div>
<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>