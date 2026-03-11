<?php
require __DIR__ . '/_auth.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || !isset($data['table']) || !isset($data['order'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid data']);
    exit;
}

$table = $data['table'];
$order = $data['order'];

// Basic validation for allowed tables to prevent SQL injection
$allowedTables = ['magazines', 'news_flash', 'exclusive_magazines', 'ozlanka_magazines'];
if (!in_array($table, $allowedTables)) {
    echo json_encode(['success' => false, 'error' => 'Invalid table']);
    exit;
}

$pdo = pdo();
try {
    $pdo->beginTransaction();

    // The order array comes from SortableJS which represents the visual top-to-bottom order.
    // We update the 'sort_order' column so that when selecting we use `ORDER BY sort_order ASC`
    $stmt = $pdo->prepare("UPDATE `$table` SET `sort_order` = :order WHERE `id` = :id");

    foreach ($order as $index => $id) {
        $stmt->execute([':order' => $index, ':id' => (int)$id]);
    }

    $pdo->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
