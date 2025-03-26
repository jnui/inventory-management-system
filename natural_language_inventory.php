<?php
require_once 'auth_check.php';
require_once 'db_connection.php';
require_once 'natural_language_processor.php';

// Set the page title for the navigation bar
$page_title = 'Natural Language Inventory';

$processor = new NaturalLanguageProcessor($pdo);
$message = '';
$error = '';
$parsedCommands = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['command'])) {
        try {
            // Process the natural language command
            $result = $processor->processCommand($_POST['command']);
            
            if (!$result['success']) {
                // Show suggestions if no exact match found
                $error = $result['message'] . "\n\nSuggestions:\n" . implode("\n", $result['suggestions']);
            } else {
                $parsedCommands = $result['commands'];
            }
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    } elseif (isset($_POST['confirm']) && isset($_POST['commands'])) {
        try {
            // Execute each command
            $commands = json_decode($_POST['commands'], true);
            $lastItemId = null;
            
            foreach ($commands as $command) {
                $formData = $processor->executeCommand($command);
                $result = $processor->processInventoryUpdate($formData);
                
                if ($result['success']) {
                    $lastItemId = $result['item_id'];
                } else {
                    $error = "Error processing command: " . $result['message'];
                    break;
                }
            }
            
            if (!$error) {
                // Debug log
                error_log("Redirecting to consumable_list with lastItemId: " . $lastItemId);
                
                // Redirect to inventory list with success message and item ID
                $_SESSION['success_message'] = "Inventory updated successfully!";
                header('Location: consumable_list.php?scroll_to=' . $lastItemId);
                exit;
            }
            
        } catch (Exception $e) {
            $error = "Error processing command: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Natural Language Inventory Management</title>
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="custom.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Navigation bar styles */
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
        }
        
        /* Navigation buttons */
        .back-button, .home-button, .help-button, .logout-button {
            color: #212529;
            text-decoration: none;
            font-size: 1.5rem;
        }
        
        .help-button {
            color: #3498db;
        }
        
        .logout-button {
            color: #dc3545;
        }
        
        .page-title {
            font-size: 1.5rem;
            margin: 0;
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <div class="navigation-bar">
        <a href="javascript:history.back()" class="back-button">
            <i class="bi bi-chevron-left"></i>
        </a>
        <h1 class="page-title"><?php echo $page_title ?? 'Inventory Management'; ?></h1>
        <div class="d-flex align-items-center">
            <span class="me-4"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
            <a href="index.php" class="home-button me-4">
                <i class="bi bi-house-fill"></i>
            </a>
            <a href="manual_index.html" class="help-button me-4" title="Help & Manuals">
                <i class="bi bi-question-circle-fill"></i>
            </a>
            <a href="logout.php" class="logout-button">
                <i class="bi bi-box-arrow-right"></i>
            </a>
        </div>
    </div>
    
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="mb-3">
                    <a href="consumable_list.php" class="btn btn-secondary">Back to Inventory List</a>
                </div>
                <div class="card">
                    <div class="card-header">
                        <h2 class="text-center">Natural Language Inventory Management</h2>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo nl2br(htmlspecialchars($error)); ?></div>
                        <?php endif; ?>
                        
                        <?php if ($message): ?>
                            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
                        <?php endif; ?>
                        
                        <?php if ($parsedCommands): ?>
                            <div class="alert alert-info">
                                <h4>Please confirm the following actions:</h4>
                                <?php foreach ($parsedCommands as $cmd): ?>
                                    <div class="mb-2">
                                        <?php echo ucfirst($cmd['action']); ?> <?php echo $cmd['quantity']; ?> 
                                        <?php echo $cmd['item_name']; ?> 
                                        <?php if (isset($cmd['employee_name'])): ?>
                                            (Employee: <?php echo $cmd['employee_name']; ?>)
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                                
                                <form method="POST" action="" class="mt-3">
                                    <input type="hidden" name="commands" value="<?php echo htmlspecialchars(json_encode($parsedCommands)); ?>">
                                    <div class="d-flex justify-content-between">
                                        <button type="submit" name="confirm" value="1" class="btn btn-success">Confirm</button>
                                        <a href="?" class="btn btn-secondary">Cancel</a>
                                    </div>
                                </form>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="command" class="form-label">Enter your inventory command:</label>
                                <input type="text" class="form-control form-control-lg" id="command" name="command" 
                                       placeholder="e.g. 'Phil took 1 12 x 12 Tee, and returned 2 12 inch, 90'" required>
                            </div>
                            
                            <div class="text-center">
                                <button type="submit" class="btn btn-primary btn-lg">Process Command</button>
                            </div>
                        </form>
                        
                        <div class="mt-4">
                            <h4>Example commands:</h4>
                            <ul>
                                <li>"Phil took 1 12 x 12 Tee, and returned 2 12 inch, 90"</li>
                                <li>"Phil took 1 12 inch, 90"</li>
                                <li>"Phil returned 3 12 x 12 Tee"</li>
                                <li>"Vicente took 1 ribbed 15 inch 45"</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 