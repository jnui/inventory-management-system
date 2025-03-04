<?php
// employee_entry.php
require_once 'db_connection.php';

$error = "";
$editMode = false;

// Initialize default values for the form fields
$employee = [
    'first_name' => ''
];

// Check if an ID is passed via GET; if so, load the record for editing
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $editMode = true;
    $stmt = $pdo->prepare("SELECT * FROM employees WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    if ($stmt->rowCount() > 0) {
        $employee = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        $error = "Employee not found.";
    }
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // If there's a hidden 'id' field, we're in edit mode
    if (isset($_POST['id']) && !empty($_POST['id'])) {
        $editMode = true;
    }

    // Validate required fields
    if (empty($_POST['first_name'])) {
        $error = "First name is required.";
    } else {
        try {
            if ($editMode) {
                // Update the existing employee
                $stmt = $pdo->prepare("
                    UPDATE employees 
                    SET first_name = :first_name
                    WHERE id = :id
                ");
                $stmt->execute([
                    ':first_name' => $_POST['first_name'],
                    ':id'         => $_POST['id']
                ]);
            } else {
                // Insert a new employee
                $stmt = $pdo->prepare("
                    INSERT INTO employees (first_name) 
                    VALUES (:first_name)
                ");
                $stmt->execute([
                    ':first_name' => $_POST['first_name']
                ]);
            }
            // Redirect to the list page after successful submission
            header("Location: employee_list.php");
            exit;
        } catch (PDOException $e) {
            $error = "Error saving employee: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= $editMode ? 'Edit' : 'Add New' ?> Employee</title>
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
    <h1 class="page-title"><?= $editMode ? 'Edit' : 'Add New' ?> Employee</h1>
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
    <form action="employee_entry.php" method="POST">
        <?php if ($editMode): ?>
            <!-- Include a hidden field with the entry ID in edit mode -->
            <input type="hidden" name="id" value="<?= htmlspecialchars($employee['id']) ?>">
        <?php endif; ?>
        <div class="mb-3">
            <label for="first_name" class="form-label">First Name</label>
            <input type="text" name="first_name" id="first_name" class="form-control" placeholder="Enter first name" required value="<?= htmlspecialchars($employee['first_name']) ?>">
        </div>
        <button type="submit" class="btn btn-success"><?= $editMode ? 'Update' : 'Add' ?> Employee</button>
        <a href="employee_list.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>
<!-- Optionally include Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>