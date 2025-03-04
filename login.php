<?php
// Start session
session_start();

// If user is already logged in, redirect to index page
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Include database connection
require_once 'db_connection.php';

$error = '';

// Process login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $initials = $_POST['initials'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($initials) || empty($password)) {
        $error = "Please enter both initials and password.";
    } else {
        try {
            // Prepare a statement to find the user by initials
            $stmt = $pdo->prepare("SELECT id, name, initials, password, role FROM users WHERE initials = ?");
            $stmt->execute([$initials]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Verify user exists and password is correct
            if ($user && password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_initials'] = $user['initials'];
                $_SESSION['user_role'] = $user['role'];
                
                // Redirect to index page
                header("Location: index.php");
                exit;
            } else {
                $error = "Invalid initials or password.";
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .login-container {
            max-width: 400px;
            margin: 100px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .login-header {
            text-align: center;
            margin-bottom: 20px;
        }
        .login-form {
            margin-top: 20px;
        }
        .error-message {
            color: #dc3545;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container">
            <div class="login-header">
                <h2>Inventory Management</h2>
                <p>Please login to continue</p>
            </div>
            
            <?php if (!empty($error)): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form class="login-form" method="post" action="login.php">
                <div class="mb-3">
                    <label for="initials" class="form-label">Initials</label>
                    <input type="text" class="form-control" id="initials" name="initials" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">Login</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 