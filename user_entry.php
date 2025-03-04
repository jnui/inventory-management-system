<?php
// Include authentication check
require_once 'auth_check.php';

// Check if user has admin role
require_admin();

// Include database connection
require_once 'db_connection.php';

// Initialize variables
$user = [
    'id' => '',
    'name' => '',
    'initials' => '',
    'role' => 'user'
];
$errors = [];
$success_message = '';
$is_edit = false;

// Check if we're editing an existing user
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $user_id = $_GET['id'];
    $is_edit = true;
    
    try {
        // Fetch user data
        $stmt = $pdo->prepare("SELECT id, name, initials, role FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user_data) {
            $user = $user_data;
        } else {
            die("User not found.");
        }
    } catch (PDOException $e) {
        die("Database error: " . $e->getMessage());
    }
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate input
    $user['name'] = trim($_POST['name'] ?? '');
    $user['initials'] = strtoupper(trim($_POST['initials'] ?? ''));
    $user['role'] = $_POST['role'] ?? 'user';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validate name
    if (empty($user['name'])) {
        $errors['name'] = "Name is required.";
    } elseif (strlen($user['name']) > 100) {
        $errors['name'] = "Name cannot exceed 100 characters.";
    }
    
    // Validate initials
    if (empty($user['initials'])) {
        $errors['initials'] = "Initials are required.";
    } elseif (strlen($user['initials']) > 10) {
        $errors['initials'] = "Initials cannot exceed 10 characters.";
    } else {
        // Check if initials are already in use (by another user)
        $stmt = $pdo->prepare("SELECT id FROM users WHERE initials = ? AND id != ?");
        $stmt->execute([$user['initials'], $is_edit ? $user['id'] : 0]);
        if ($stmt->fetch()) {
            $errors['initials'] = "These initials are already in use.";
        }
    }
    
    // Validate role
    if (!in_array($user['role'], ['user', 'admin'])) {
        $errors['role'] = "Invalid role selected.";
    }
    
    // Validate password
    if (!$is_edit || !empty($password)) {
        if (empty($password)) {
            $errors['password'] = "Password is required.";
        } elseif (strlen($password) < 6) {
            $errors['password'] = "Password must be at least 6 characters.";
        } elseif ($password !== $confirm_password) {
            $errors['confirm_password'] = "Passwords do not match.";
        }
    }
    
    // If no errors, save to database
    if (empty($errors)) {
        try {
            if ($is_edit) {
                // Update existing user
                if (!empty($password)) {
                    // Update with new password
                    $stmt = $pdo->prepare("UPDATE users SET name = ?, initials = ?, password = ?, role = ? WHERE id = ?");
                    $stmt->execute([
                        $user['name'],
                        $user['initials'],
                        password_hash($password, PASSWORD_DEFAULT),
                        $user['role'],
                        $user['id']
                    ]);
                } else {
                    // Update without changing password
                    $stmt = $pdo->prepare("UPDATE users SET name = ?, initials = ?, role = ? WHERE id = ?");
                    $stmt->execute([
                        $user['name'],
                        $user['initials'],
                        $user['role'],
                        $user['id']
                    ]);
                }
                $success_message = "User updated successfully.";
            } else {
                // Create new user
                $stmt = $pdo->prepare("INSERT INTO users (name, initials, password, role) VALUES (?, ?, ?, ?)");
                $stmt->execute([
                    $user['name'],
                    $user['initials'],
                    password_hash($password, PASSWORD_DEFAULT),
                    $user['role']
                ]);
                $success_message = "User created successfully.";
                
                // Clear form for new entry
                $user = [
                    'id' => '',
                    'name' => '',
                    'initials' => '',
                    'role' => 'user'
                ];
            }
        } catch (PDOException $e) {
            $errors['database'] = "Database error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $is_edit ? 'Edit' : 'Add' ?> User</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        /* Navigation bar */
        .navigation-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            padding: 10px 15px;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            background-color: #f8f9fa;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            height: 60px;
        }
        
        /* Ensure content doesn't get hidden under the navigation bar */
        .content-container {
            padding-top: 80px;
            max-width: 800px;
        }
        
        /* Form styling */
        .form-container {
            background-color: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .form-buttons {
            margin-top: 20px;
            display: flex;
            gap: 10px;
        }
        
        .error-message {
            color: #dc3545;
        }
        
        .success-message {
            color: #198754;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <?php
    // Set the page title for the navigation bar
    $page_title = ($is_edit ? 'Edit' : 'Add') . ' User';
    
    // Include the navigation bar template
    include 'nav_template.php';
    ?>

    <div class="container content-container">
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success">
                <?= htmlspecialchars($success_message) ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($errors['database'])): ?>
            <div class="alert alert-danger">
                <?= htmlspecialchars($errors['database']) ?>
            </div>
        <?php endif; ?>
        
        <div class="form-container">
            <form method="post" action="<?= htmlspecialchars($_SERVER['PHP_SELF'] . ($is_edit ? '?id=' . $user['id'] : '')) ?>">
                <div class="mb-3">
                    <label for="name" class="form-label">Name</label>
                    <input type="text" class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>" id="name" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>
                    <?php if (isset($errors['name'])): ?>
                        <div class="invalid-feedback"><?= htmlspecialchars($errors['name']) ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="mb-3">
                    <label for="initials" class="form-label">Initials</label>
                    <input type="text" class="form-control <?= isset($errors['initials']) ? 'is-invalid' : '' ?>" id="initials" name="initials" value="<?= htmlspecialchars($user['initials']) ?>" required maxlength="10">
                    <?php if (isset($errors['initials'])): ?>
                        <div class="invalid-feedback"><?= htmlspecialchars($errors['initials']) ?></div>
                    <?php endif; ?>
                    <div class="form-text">Initials are used for login and must be unique.</div>
                </div>
                
                <div class="mb-3">
                    <label for="role" class="form-label">Role</label>
                    <select class="form-select <?= isset($errors['role']) ? 'is-invalid' : '' ?>" id="role" name="role" required>
                        <option value="user" <?= $user['role'] === 'user' ? 'selected' : '' ?>>User</option>
                        <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Administrator</option>
                    </select>
                    <?php if (isset($errors['role'])): ?>
                        <div class="invalid-feedback"><?= htmlspecialchars($errors['role']) ?></div>
                    <?php endif; ?>
                    <div class="form-text">Administrators can manage users and have full access to all features.</div>
                </div>
                
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>" id="password" name="password" <?= $is_edit ? '' : 'required' ?>>
                    <?php if (isset($errors['password'])): ?>
                        <div class="invalid-feedback"><?= htmlspecialchars($errors['password']) ?></div>
                    <?php endif; ?>
                    <?php if ($is_edit): ?>
                        <div class="form-text">Leave blank to keep current password.</div>
                    <?php endif; ?>
                </div>
                
                <div class="mb-3">
                    <label for="confirm_password" class="form-label">Confirm Password</label>
                    <input type="password" class="form-control <?= isset($errors['confirm_password']) ? 'is-invalid' : '' ?>" id="confirm_password" name="confirm_password" <?= $is_edit ? '' : 'required' ?>>
                    <?php if (isset($errors['confirm_password'])): ?>
                        <div class="invalid-feedback"><?= htmlspecialchars($errors['confirm_password']) ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="form-buttons">
                    <button type="submit" class="btn btn-primary"><?= $is_edit ? 'Update' : 'Create' ?> User</button>
                    <a href="user_list.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 