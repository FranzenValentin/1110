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

    // Abfrage von Adresse und Stadtteil aus der Tabelle `einsaetze`
    $stmt = $pdo->query("
        SELECT adresse, stadtteil 
        FROM einsaetze
    ");
    $einsaetze = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Debugging: Daten aus der Datenbank prüfen
    echo '<pre>';
    print_r($einsaetze);
    echo '</pre>';
    exit; // Zum Testen: Beende das Skript hier, um die Ausgabe zu prüfen.

} catch (PDOException $e) {
    die("Datenbankverbindung fehlgeschlagen: " . $e->getMessage());
}
?>



<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Einsätze Heatmap</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
</head>
<body>
    <h2>Heatmap der Einsätze</h2>
    <div id="map" style="width: 100%; height: 500px;"></div>

    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet.heat/dist/leaflet-heat.js"></script>

    <script>
    // Daten aus der PHP-Abfrage
    const einsaetze = <?= json_encode($einsaetze) ?>;

    // Debugging: Überprüfen, ob die Daten korrekt im JavaScript ankommen
    console.log("Einsaetze-Daten aus PHP:", einsaetze);

    // Karte initialisieren
    const map = L.map('map').setView([52.5200, 13.4050], 11); // Berlin-Zentrum

    // OpenStreetMap-Layer hinzufügen
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(map);

    // Heatmap-Daten
    const heatData = [];

    // Funktion zur Geocodierung (Adresse zu Koordinaten umwandeln)
    async function geocodeAdresse(adresse, bezirk) {
        const url = `https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(adresse)}, ${encodeURIComponent(bezirk)}&format=json&limit=1`;
        
        // Debugging: API-URL überprüfen
        console.log("Geocoding-URL:", url);

        try {
            const response = await fetch(url);
            const data = await response.json();

            // Debugging: Antwort der API prüfen
            console.log(`Antwort für ${adresse}, ${bezirk}:`, data);

            if (data.length > 0) {
                const latitude = parseFloat(data[0].lat);
                const longitude = parseFloat(data[0].lon);

                // Debugging: Koordinaten prüfen
                console.log(`Koordinaten für ${adresse}, ${bezirk}:`, latitude, longitude);

                heatData.push([latitude, longitude, 1]); // Gewichtung hier statisch als 1
            } else {
                console.warn(`Keine Koordinaten für: ${adresse}, ${bezirk}`);
            }
        } catch (error) {
            console.error(`Fehler beim Geocodieren: ${error}`);
        }
    }

    // Hauptfunktion zum Verarbeiten der Adressdaten
    async function erstelleHeatmap() {
        for (const einsatz of einsaetze) {
            // Debugging: Aktuellen Einsatz ausgeben
            console.log("Verarbeite Einsatz:", einsatz);
            await geocodeAdresse(einsatz.adresse, einsatz.stadtteil);
        }

        // Debugging: Heatmap-Daten prüfen
        console.log("Heatmap-Daten:", heatData);

        // Heatmap hinzufügen
        L.heatLayer(heatData, {
            radius: 25, // Radius der Punkte
            blur: 15,   // Weichzeichnung
            maxZoom: 17 // Maximale Zoomstufe
        }).addTo(map);
    }

    // Heatmap erstellen
    erstelleHeatmap();
</script>

</body>
</html>
