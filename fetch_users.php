<?php
require 'db.php';

$query = isset($_GET['query']) ? trim($_GET['query']) : '';
if (strlen($query) < 2) {
    echo json_encode([]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT CONCAT(nachname, ' ', vorname) AS full_name 
        FROM personal 
        WHERE nachname LIKE :query OR vorname LIKE :query 
        ORDER BY nachname, vorname
    ");
    $stmt->execute(['query' => "%$query%"]);
    $results = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode($results);
} catch (PDOException $e) {
    echo json_encode([]);
}
