<?php
// Datenbankverbindung laden
require_once 'db.php';

// Funktion zur Abfrage der Koordinaten von OpenStreetMap
function fetchCoordinates($address) {
    $url = "https://nominatim.openstreetmap.org/search?format=json&q=" . urlencode($address);
    
    $options = [
        "http" => [
            "header" => "User-Agent: Trottlig-GeoCoder/1.0 (https://example.com; kontakt@example.com)\r\n"
        ]
    ];
    $context = stream_context_create($options);

    try {
        $response = file_get_contents($url, false, $context);
        $data = json_decode($response, true);
        
        if (!empty($data)) {
            return [
                "latitude" => $data[0]["lat"],
                "longitude" => $data[0]["lon"]
            ];
        } else {
            return ["latitude" => null, "longitude" => null];
        }
    } catch (Exception $e) {
        echo "Fehler beim Abrufen der Koordinaten: " . $e->getMessage() . "\n";
        return ["latitude" => null, "longitude" => null];
    }
}

// Adressen abrufen, die noch keine Koordinaten haben
$query = "SELECT id, adresse FROM einsaetze WHERE latitude IS NULL OR longitude IS NULL";
$stmt = $pdo->query($query);

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $id = $row['id'];
    $address = $row['adresse'];
    
    echo "Verarbeite Adresse: $address\n";
    $coordinates = fetchCoordinates($address);

    if ($coordinates["latitude"] && $coordinates["longitude"]) {
        // Koordinaten in der Datenbank aktualisieren
        $updateQuery = "UPDATE einsaetze SET latitude = :latitude, longitude = :longitude WHERE id = :id";
        $updateStmt = $pdo->prepare($updateQuery);
        $updateStmt->execute([
            ':latitude' => $coordinates["latitude"],
            ':longitude' => $coordinates["longitude"],
            ':id' => $id
        ]);
        echo "Koordinaten für ID $id aktualisiert: (" . $coordinates["latitude"] . ", " . $coordinates["longitude"] . ")\n";
    } else {
        echo "Keine Koordinaten für Adresse $address gefunden.\n";
    }

    // Throttling: 1 Sekunde Pause zwischen den Anfragen
    sleep(1);
}
?>
