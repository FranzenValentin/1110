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

    // Nur Adressen ohne Koordinaten abrufen
    $stmt = $pdo->query("
        SELECT id, adresse, stadtteil 
        FROM einsaetze
        WHERE (latitude IS NULL OR longitude IS NULL)
        AND adresse IS NOT NULL AND adresse != '' 
        AND stadtteil IS NOT NULL AND stadtteil != ''
        LIMIT 50
    ");
    $einsaetze = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Datenbankverbindung fehlgeschlagen: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Geocodierung der Einsätze</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <h2>Geocodierung der Einsätze</h2>
    <p id="status">Starte Geocodierung...</p>
    <ul id="results"></ul>

    <script>
        const einsaetze = <?= json_encode($einsaetze) ?>;

        // Funktion, um einen Einsatz zu geocodieren
        async function geocodeEinsatz(einsatz) {
            try {
                const response = await $.post('geocode_process.php', einsatz);
                const result = JSON.parse(response);

                if (result.latitude && result.longitude) {
                    $('#results').append(`<li>Erfolgreich: ID ${result.id} - Lat: ${result.latitude}, Lon: ${result.longitude}</li>`);
                } else {
                    $('#results').append(`<li>Fehler: ID ${result.id} - ${result.error}</li>`);
                }
            } catch (error) {
                $('#results').append(`<li>Netzwerkfehler: ID ${einsatz.id}</li>`);
                console.error(error);
            }
        }

        // Geocodiere alle Einsätze nacheinander
        async function startGeocoding() {
            for (const einsatz of einsaetze) {
                $('#status').text(`Verarbeite ID ${einsatz.id}...`);
                await geocodeEinsatz(einsatz);
                await new Promise(resolve => setTimeout(resolve, 1000)); // 1 Sekunde warten
            }
            $('#status').text('Geocodierung abgeschlossen.');
        }

        // Starte den Geocodierungsprozess
        startGeocoding();
    </script>
</body>
</html>
