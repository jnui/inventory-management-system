<?php
// Include authentication check
require_once 'auth_check.php';

// Include database connection
require_once 'db_connection.php';

// Set the page title for the navigation bar
$page_title = "Location Report";

// Include the navigation template
include 'nav_template.php';

// Get all locations for autocomplete
$locStmt = $pdo->query("SELECT id, location_name as name FROM item_locations ORDER BY location_name ASC");
$locations = $locStmt->fetchAll(PDO::FETCH_ASSOC);

// Check if a location is selected
$selectedLocation = null;
$materials = [];

if (isset($_GET['location_id']) && !empty($_GET['location_id'])) {
    $locationId = $_GET['location_id'];
    
    // Get the selected location name
    $locNameStmt = $pdo->prepare("SELECT location_name as name FROM item_locations WHERE id = ?");
    $locNameStmt->execute([$locationId]);
    $selectedLocation = $locNameStmt->fetch(PDO::FETCH_ASSOC);
    
    // Get materials for the selected location
    $matStmt = $pdo->prepare("
        SELECT 
            cm.id,
            cm.item_type,
            cm.item_name,
            cm.item_description,
            cm.whole_quantity,
            cm.reorder_threshold,
            l.location_name as location_name
        FROM 
            consumable_materials cm
        JOIN 
            item_locations l ON cm.normal_item_location = l.id
        WHERE 
            cm.normal_item_location = ?
        ORDER BY 
            cm.item_name ASC
    ");
    $matStmt->execute([$locationId]);
    $materials = $matStmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Location Report</title>
    
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
        
        .location-search-container {
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
        
        .below-threshold {
            background-color: #fff3cd;
        }
    </style>
</head>
<body>
    <div class="container content-container">
        <div class="row mb-4">
            <div class="col">
                <h1 class="display-5 mb-3">Location Report</h1>
                <p class="lead">View all inventory items stored at a specific location.</p>
                <a href="reports.php" class="btn btn-outline-secondary mb-4">
                    <i class="bi bi-arrow-left"></i> Back to Reports
                </a>
            </div>
        </div>
        
        <div class="row">
            <div class="col-12">
                <div class="location-search-container">
                    <h4 class="mb-3">Select a Location</h4>
                    <form id="locationForm" method="get" action="report_location.php" class="row g-3">
                        <div class="col-md-8">
                            <label for="location" class="form-label">Location Name</label>
                            <input type="text" class="form-control" id="location" placeholder="Start typing to search locations...">
                            <input type="hidden" id="location_id" name="location_id">
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">Generate Report</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <?php if ($selectedLocation): ?>
        <div class="row">
            <div class="col-12">
                <div class="report-header">
                    <h3>Items at: <?php echo htmlspecialchars($selectedLocation['name']); ?></h3>
                    <p>Total Items: <?php echo count($materials); ?></p>
                </div>
                
                <?php if (count($materials) > 0): ?>
                <div class="table-responsive">
                    <table id="materialsTable" class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>Item Type</th>
                                <th>Name</th>
                                <th>Description</th>
                                <th>Quantity</th>
                                <th>Reorder Threshold</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($materials as $material): ?>
                            <tr class="<?php echo ($material['whole_quantity'] < $material['reorder_threshold']) ? 'below-threshold' : ''; ?>">
                                <td><?php echo htmlspecialchars($material['item_type']); ?></td>
                                <td><?php echo htmlspecialchars($material['item_name']); ?></td>
                                <td><?php echo htmlspecialchars($material['item_description'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($material['whole_quantity']); ?></td>
                                <td><?php echo htmlspecialchars($material['reorder_threshold'] ?? 'N/A'); ?></td>
                                <td>
                                    <a href="inventory_entry.php?material_id=<?php echo $material['id']; ?>" class="btn btn-sm btn-primary">
                                        <i class="bi bi-pencil-square"></i> Stock Change
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> No materials found at this location.
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
            $('#materialsTable').DataTable({
                responsive: true,
                dom: 'Bfrtip',
                buttons: [
                    'copy', 'excel', 'pdf', 'print', 'colvis'
                ]
            });
            
            // Location autocomplete
            var locations = <?php echo json_encode($locations); ?>;
            
            $("#location").autocomplete({
                source: function(request, response) {
                    var matcher = new RegExp($.ui.autocomplete.escapeRegex(request.term), "i");
                    response($.grep(locations, function(item) {
                        return matcher.test(item.name);
                    }));
                },
                minLength: 1,
                select: function(event, ui) {
                    $("#location").val(ui.item.name);
                    $("#location_id").val(ui.item.id);
                    return false;
                }
            }).autocomplete("instance")._renderItem = function(ul, item) {
                return $("<li>")
                    .append("<div>" + item.name + "</div>")
                    .appendTo(ul);
            };
            
            // Form validation
            $("#locationForm").on("submit", function(e) {
                if (!$("#location_id").val()) {
                    e.preventDefault();
                    alert("Please select a location from the dropdown.");
                }
            });
        });
    </script>
</body>
</html> 