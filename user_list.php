<?php
// Include authentication check
require_once 'auth_check.php';

// Check if user has admin role
require_admin();

// Include database connection
require_once 'db_connection.php';

try {
    // Retrieve all users
    $stmt = $pdo->query("SELECT id, name, initials, role, created_at, updated_at FROM users ORDER BY name ASC");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css" rel="stylesheet">
    <!-- jQuery (required for DataTables) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- DataTables Core JS -->
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <style>
        /* Custom styles for sticky header */
        .dataTables_wrapper .dataTables_scrollHead {
            position: sticky;
            top: 0;
            z-index: 10;
            background-color: #fff;
        }
        
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
        }
        
        /* Action row styling */
        .action-row {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        /* Responsive table */
        .table-responsive {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
    </style>
</head>
<body>
    <?php
    // Set the page title for the navigation bar
    $page_title = 'User Management';
    
    // Include the navigation bar template
    include 'nav_template.php';
    ?>

    <div class="container content-container">
        <div class="header-section">
            <h1>User Management</h1>
        </div>

        <div class="action-row">
            <a href="user_entry.php" class="btn btn-primary">Add New User</a>
        </div>
        
        <?php if ($users): ?>
        <div class="table-responsive">
            <table id="usersTable" class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Initials</th>
                        <th>Role</th>
                        <th>Created</th>
                        <th>Updated</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): 
                        // Format dates
                        $createdDate = new DateTime($user['created_at']);
                        $updatedDate = new DateTime($user['updated_at']);
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($user['id']) ?></td>
                        <td><?= htmlspecialchars($user['name']) ?></td>
                        <td><?= htmlspecialchars($user['initials']) ?></td>
                        <td><?= htmlspecialchars($user['role']) ?></td>
                        <td><?= $createdDate->format('M j, Y g:ia') ?></td>
                        <td><?= $updatedDate->format('M j, Y g:ia') ?></td>
                        <td>
                            <div class="action-buttons-cell">
                                <a href="user_entry.php?id=<?= htmlspecialchars($user['id']) ?>" class="btn btn-sm btn-warning">Edit</a>
                                <button class="btn btn-sm btn-danger delete-user" data-id="<?= htmlspecialchars($user['id']) ?>" data-name="<?= htmlspecialchars($user['name']) ?>">Delete</button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
            <p>No users found. <a href="user_entry.php">Add one now</a>.</p>
        <?php endif; ?>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete user <span id="userName"></span>?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form id="deleteForm" method="post" action="user_delete.php">
                        <input type="hidden" id="userId" name="user_id" value="">
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize DataTable
            $('#usersTable').DataTable({
                scrollY: '60vh',
                scrollCollapse: true,
                paging: false,
                ordering: true,
                info: true
            });
            
            // Handle delete button click
            $('.delete-user').on('click', function() {
                var userId = $(this).data('id');
                var userName = $(this).data('name');
                
                $('#userId').val(userId);
                $('#userName').text(userName);
                
                var deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
                deleteModal.show();
            });
        });
    </script>
</body>
</html> 