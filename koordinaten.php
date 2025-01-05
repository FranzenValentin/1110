<?php
session_start();
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header('Location: login.php');
    exit;
}
require 'db.php';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Alle Adressen aus der Tabelle `einsaetze` abrufen, die noch keine Koordinaten haben
    $stmt = $pdo->query("
        SELECT id, adresse, stadtteil 
        FROM einsaetze
        WHERE latitude IS NULL OR longitude IS NULL
        AND adresse IS NOT NULL AND adresse != '' 
        AND stadtteil IS NOT NULL AND stadtteil != ''
    ");
    $einsaetze = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Funktion zur Geocodierung
    function geocodeAdresse($adresse, $stadtteil) {
        $url = "https://nominatim.openstreetmap.org/search?q=" . urlencode($adresse . ", " . $stadtteil) . "&format=json&limit=1";
        try {
            $response = file_get_contents($url);
            $data = json_decode($response, true);

            if (!empty($data) && isset($data[0]['lat']) && isset($data[0]['lon'])) {
                return [
                    'latitude' => $data[0]['lat'],
                    'longitude' => $data[0]['lon']
                ];
            }
        } catch (Exception $e) {
            // Fehler beim Abrufen der Geodaten
            return null;
        }
        return null;
    }

    // Koordinaten für alle Einsätze aktualisieren
    foreach ($einsaetze as $einsatz) {
        $koordinaten = geocodeAdresse($einsatz['adresse'], $einsatz['stadtteil']);

        if ($koordinaten) {
            // Debugging: Zeige gefundene Koordinaten an
            echo "Gefundene Koordinaten für ID {$einsatz['id']} ({$einsatz['adresse']}, {$einsatz['stadtteil']}): ";
            echo "Lat: {$koordinaten['latitude']}, Lon: {$koordinaten['longitude']}<br>";

            // Koordinaten in die Datenbank speichern
            $updateStmt = $pdo->prepare("
                UPDATE einsaetze
                SET latitude = :latitude, longitude = :longitude
                WHERE id = :id
            ");
            $updateStmt->execute([
                ':latitude' => $koordinaten['latitude'],
                ':longitude' => $koordinaten['longitude'],
                ':id' => $einsatz['id']
            ]);
        } else {
            // Debugging: Keine Koordinaten gefunden
            echo "Keine Koordinaten gefunden für ID {$einsatz['id']} ({$einsatz['adresse']}, {$einsatz['stadtteil']})<br>";
        }

        // Wartezeit von 1 Sekunde, um API-Limitierung zu vermeiden
        sleep(1);
    }

    echo "Geocodierung abgeschlossen.";

} catch (PDOException $e) {
    die("Datenbankverbindung fehlgeschlagen: " . $e->getMessage());
}
?>
