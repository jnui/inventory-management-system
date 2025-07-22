<?php
// update_consumable.php
// AJAX endpoint to update a single field in consumable_materials (admin only)

header('Content-Type: application/json');

require_once 'auth_check.php';
require_admin(); // ensure admin
require_once 'db_connection.php';

$response = ['status' => 'error', 'msg' => 'Unknown error'];

// Basic validation of POST params
$id     = isset($_POST['id'])     ? intval($_POST['id'])     : 0;
$column = isset($_POST['column']) ? trim($_POST['column'])   : '';
$value  = isset($_POST['value'])  ? trim($_POST['value'])    : null; // allow empty string

if ($id <= 0 || $column === '') {
    $response['msg'] = 'Invalid parameters';
    echo json_encode($response);
    exit;
}

// Whitelist of editable columns and their expected types
$editableColumns = [
    'item_type'               => 'string',
    'item_name'               => 'string',
    'item_description'        => 'string',
    'diameter'                => 'numeric',
    'reorder_threshold'       => 'numeric',
    'composition_description' => 'string',
    'vendor'                  => 'string',
    'normal_item_location'    => 'string',
    'whole_quantity'          => 'numeric',
    'item_units_whole'        => 'string',
    'item_units_part'         => 'string',
    'qty_parts_per_whole'     => 'numeric'
];

if (!array_key_exists($column, $editableColumns)) {
    $response['msg'] = 'Column not editable';
    echo json_encode($response);
    exit;
}

// Type-specific sanitisation
$type = $editableColumns[$column];
if ($type === 'numeric') {
    if ($value === '') {
        $value = null; // allow clearing numeric field
    } elseif (!is_numeric($value)) {
        $response['msg'] = 'Numeric value required';
        echo json_encode($response);
        exit;
    }
}

try {
    $sql = "UPDATE consumable_materials SET {$column} = :val WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':val', $value, ($type === 'numeric' && $value !== null) ? PDO::PARAM_STR : PDO::PARAM_STR);
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();

    $response['status'] = 'ok';
    unset($response['msg']);
} catch (PDOException $e) {
    $response['msg'] = 'DB error: ' . $e->getMessage();
}

echo json_encode($response);
?> 