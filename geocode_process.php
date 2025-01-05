<?php
session_start();
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $adresse = $_POST['adresse'] ?? null;
    $stadtteil = $_POST['stadtteil'] ?? null;

    if (!$id || !$adresse || !$stadtteil) {
        echo json_encode(['error' => 'Invalid data']);
        exit;
    }

    // Geocodierung mit Nominatim
    $url = "https://nominatim.openstreetmap.org/search?q=" . urlencode("$adresse, $stadtteil") . "&format=json&limit=1";

    try {
        $response = file_get_contents($url);
        $data = json_decode($response, true);

        if (!empty($data) && isset($data[0]['lat']) && isset($data[0]['lon'])) {
            $latitude = $data[0]['lat'];
            $longitude = $data[0]['lon'];

            // Koordinaten in der Datenbank speichern
            $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $stmt = $pdo->prepare("
                UPDATE einsaetze
                SET latitude = :latitude, longitude = :longitude
                WHERE id = :id
            ");
            $stmt->execute([
                ':latitude' => $latitude,
                ':longitude' => $longitude,
                ':id' => $id
            ]);

            echo json_encode(['id' => $id, 'latitude' => $latitude, 'longitude' => $longitude]);
        } else {
            echo json_encode(['id' => $id, 'error' => 'No coordinates found']);
        }
    } catch (Exception $e) {
        echo json_encode(['id' => $id, 'error' => $e->getMessage()]);
    }
    exit;
}

echo json_encode(['error' => 'Invalid request']);
exit;
