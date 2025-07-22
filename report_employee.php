<?php
// Include authentication check
require_once 'auth_check.php';

// Include database connection
require_once 'db_connection.php';

// Set the page title for the navigation bar
$page_title = "Employee Report";

// Include the navigation template
include 'nav_template.php';

// Get all employees for autocomplete
$empStmt = $pdo->query("SELECT id, first_name FROM employees ORDER BY first_name ASC");
$employees = $empStmt->fetchAll(PDO::FETCH_ASSOC);

// Check if an employee is selected
$selectedEmployee = null;
$inventoryChanges = [];

if (isset($_GET['employee_id']) && !empty($_GET['employee_id'])) {
    $employeeId = $_GET['employee_id'];
    
    // Get the selected employee name
    $empNameStmt = $pdo->prepare("SELECT first_name FROM employees WHERE id = ?");
    $empNameStmt->execute([$employeeId]);
    $selectedEmployee = $empNameStmt->fetch(PDO::FETCH_ASSOC);
    
    // Get inventory changes for the selected employee
    $invStmt = $pdo->prepare("
        SELECT 
            i.id,
            i.consumable_material_id as material_id,
            i.items_added as quantity,
            CASE WHEN i.items_added > 0 THEN 1 ELSE 0 END as is_addition,
            i.whole_quantity as new_quantity,
            i.item_notes as notes,
            i.change_date as created_at,
            cm.item_name,
            l.location_name as location_name,
            e.first_name as employee_name
        FROM 
            inventory_change_entries i
        JOIN 
            consumable_materials cm ON i.consumable_material_id = cm.id
        JOIN 
            item_locations l ON cm.normal_item_location = l.id
        JOIN 
            employees e ON i.employee_id = e.id
        WHERE 
            i.employee_id = ?
        ORDER BY 
            i.change_date DESC
    ");
    $invStmt->execute([$employeeId]);
    $inventoryChanges = $invStmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Report</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <!-- jQuery UI CSS -->
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="datatables/datatables.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="custom.css">
    
    <style>
        .content-container {
            padding-top: 80px;
        }
        
        .ui-autocomplete {
            max-height: 200px;
            overflow-y: auto;
            overflow-x: hidden;
            z-index: 2000;
        }
        
        .employee-search-container {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        .report-header {
            background-color: #e9ecef;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .addition {
            color: #198754;
            font-weight: bold;
        }
        
        .removal {
            color: #dc3545;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container content-container">
        <div class="row mb-4">
            <div class="col">
                <h1 class="display-5 mb-3">Employee Report</h1>
                <p class="lead">View inventory changes made by a specific employee.</p>
                <a href="reports.php" class="btn btn-outline-secondary mb-4">
                    <i class="bi bi-arrow-left"></i> Back to Reports
                </a>
            </div>
        </div>
        
        <div class="row">
            <div class="col-12">
                <div class="employee-search-container">
                    <h4 class="mb-3">Select an Employee</h4>
                    <form id="employeeForm" method="get" action="report_employee.php" class="row g-3">
                        <div class="col-md-8">
                            <label for="employee" class="form-label">Employee Name</label>
                            <input type="text" class="form-control" id="employee" placeholder="Start typing to search employees...">
                            <input type="hidden" id="employee_id" name="employee_id">
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">Generate Report</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <?php if ($selectedEmployee): ?>
        <div class="row">
            <div class="col-12">
                <div class="report-header">
                    <h3>Inventory Changes by: <?php echo htmlspecialchars($selectedEmployee['first_name']); ?></h3>
                    <p>Total Changes: <?php echo count($inventoryChanges); ?></p>
                </div>
                
                <?php if (count($inventoryChanges) > 0): ?>
                <div class="table-responsive">
                    <table id="changesTable" class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Item</th>
                                <th>Location</th>
                                <th>Change</th>
                                <th>New Quantity</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($inventoryChanges as $change): ?>
                            <tr>
                                <td><?php echo date('M j, Y g:i a', strtotime($change['created_at'])); ?></td>
                                <td><?php echo htmlspecialchars($change['item_name']); ?></td>
                                <td><?php echo htmlspecialchars($change['location_name']); ?></td>
                                <td class="<?php echo $change['is_addition'] ? 'addition' : 'removal'; ?>">
                                    <?php echo $change['is_addition'] ? '+' : '-'; ?><?php echo htmlspecialchars($change['quantity']); ?>
                                </td>
                                <td><?php echo htmlspecialchars($change['new_quantity']); ?></td>
                                <td><?php echo htmlspecialchars($change['notes'] ?? ''); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> No inventory changes found for this employee.
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- jQuery and jQuery UI -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- DataTables JS -->
    <script src="datatables/datatables.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Initialize DataTable
            $('#changesTable').DataTable({
                responsive: true,
                dom: 'Bfrtip',
                buttons: [
                    'copy', 'excel', 'pdf', 'print', 'colvis'
                ],
                order: [[0, 'desc']] // Sort by date descending
            });
            
            // Employee autocomplete
            var employees = <?php echo json_encode($employees); ?>;
            
            $("#employee").autocomplete({
                source: function(request, response) {
                    var matcher = new RegExp($.ui.autocomplete.escapeRegex(request.term), "i");
                    response($.grep(employees, function(item) {
                        return matcher.test(item.first_name);
                    }));
                },
                minLength: 1,
                select: function(event, ui) {
                    $("#employee").val(ui.item.first_name);
                    $("#employee_id").val(ui.item.id);
                    return false;
                }
            }).autocomplete("instance")._renderItem = function(ul, item) {
                return $("<li>")
                    .append("<div>" + item.first_name + "</div>")
                    .appendTo(ul);
            };
            
            // Form validation
            $("#employeeForm").on("submit", function(e) {
                if (!$("#employee_id").val()) {
                    e.preventDefault();
                    alert("Please select an employee from the dropdown.");
                }
            });
        });
    </script>
</body>
</html> 