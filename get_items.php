<?php
require_once 'db_connection.php';

header('Content-Type: application/json');

$searchTerm = $_GET['q'] ?? '';

if (empty($searchTerm)) {
    echo json_encode([]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT id, item_name, whole_quantity, optimum_quantity, reorder_threshold 
    FROM consumable_materials 
    WHERE item_name LIKE ?
    LIMIT 20
");
$stmt->execute(['%' . $searchTerm . '%']);
$items = $stmt->fetchAll();

$results = [];
foreach ($items as $item) {
    $results[] = [
        'id' => $item['id'],
        'text' => $item['item_name'],
        'stock' => $item['whole_quantity'],
        'top_up_qty' => $item['optimum_quantity'],
        'reup_qty' => $item['reorder_threshold']
    ];
}

echo json_encode($results);
