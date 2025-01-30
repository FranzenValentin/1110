<?php
require_once 'parts/session_check.php';
require 'parts/db.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Variablen initialisieren
$totalEinsaetze = 0;
$durchschnittsdauer = null;
$stichworte = [];
$error = null;

// Standardwerte für Start- und Enddatum setzen
if (!isset($_GET['startdatum']) || !isset($_GET['enddatum'])) {
    $currentDate = new DateTime();
    $startdatum = $currentDate->format('Y-m-01'); // Erster Tag des aktuellen Monats
    $enddatum = $currentDate->format('Y-m-t');    // Letzter Tag des aktuellen Monats
} else {
    $startdatum = $_GET['startdatum'];
    $enddatum = $_GET['enddatum'];
}

// Start- und Enddatum ins richtige Format umwandeln
try {
    $startdatum = (new DateTime($startdatum))->format('Y-m-d 00:00:00');
    $enddatum = (new DateTime($enddatum))->format('Y-m-d 23:59:59');
} catch (Exception $e) {
    $error = "Ungültiges Datum: " . htmlspecialchars($e->getMessage());
    $startdatum = null;
    $enddatum = null;
}

try {
    // Gesamtanzahl der Einsätze
    $totalStmt = $pdo->prepare("
    SELECT COUNT(*) AS total 
    FROM einsaetze 
    WHERE STR_TO_DATE(alarmuhrzeit, '%d.%m.%Y %H:%i') 
          BETWEEN STR_TO_DATE(:startdatum, '%Y-%m-%d %H:%i:%s') 
              AND STR_TO_DATE(:enddatum, '%Y-%m-%d %H:%i:%s')
    ");

    $totalStmt->execute([':startdatum' => $startdatum, ':enddatum' => $enddatum]);
    $totalEinsaetze = $totalStmt->fetch()['total'];

    // Durchschnittliche Einsatzdauer
    $dauerStmt = $pdo->prepare("
    SELECT AVG(TIMESTAMPDIFF(MINUTE, 
        STR_TO_DATE(alarmuhrzeit, '%d.%m.%Y %H:%i'), 
        STR_TO_DATE(zurueckzeit, '%d.%m.%Y %H:%i')
    )) AS durchschnittsdauer
    FROM einsaetze
    WHERE STR_TO_DATE(alarmuhrzeit, '%d.%m.%Y %H:%i') 
          BETWEEN STR_TO_DATE(:startdatum, '%Y-%m-%d %H:%i:%s') 
              AND STR_TO_DATE(:enddatum, '%Y-%m-%d %H:%i:%s')
    ");

    $dauerStmt->execute([':startdatum' => $startdatum, ':enddatum' => $enddatum]);
    $durchschnittsdauer = $dauerStmt->fetch()['durchschnittsdauer'];

    // Häufigste Stichworte
    $stichwortStmt = $pdo->prepare("
    SELECT stichwort, COUNT(*) AS anzahl 
    FROM einsaetze 
    WHERE STR_TO_DATE(alarmuhrzeit, '%d.%m.%Y %H:%i') 
          BETWEEN STR_TO_DATE(:startdatum, '%Y-%m-%d %H:%i:%s') 
              AND STR_TO_DATE(:enddatum, '%Y-%m-%d %H:%i:%s')
    GROUP BY stichwort 
    ORDER BY anzahl DESC
    LIMIT 8
    ");

    $stichwortStmt->execute([':startdatum' => $startdatum, ':enddatum' => $enddatum]);
    $stichworte = $stichwortStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error = "Fehler beim Laden der Daten: " . htmlspecialchars($e->getMessage());
}

try {
    // Einsätze nach Kategorie summieren
    $categoryStmt = $pdo->prepare("
    SELECT s.kategorie, COUNT(*) AS anzahl 
    FROM einsaetze e
    JOIN stichworte s ON e.stichwort = s.stichwort
    WHERE STR_TO_DATE(e.alarmuhrzeit, '%d.%m.%Y %H:%i') 
          BETWEEN STR_TO_DATE(:startdatum, '%Y-%m-%d %H:%i:%s') 
              AND STR_TO_DATE(:enddatum, '%Y-%m-%d %H:%i:%s')
    GROUP BY s.kategorie
    ORDER BY anzahl DESC
    ");

    $categoryStmt->execute([':startdatum' => $startdatum, ':enddatum' => $enddatum]);
    $einsaetzeNachKategorie = $categoryStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Fehler beim Laden der Daten nach Kategorien: " . htmlspecialchars($e->getMessage());
}




// Heatmap-Daten aus der Datenbank abrufen
try {
    $pinQuery = $pdo->prepare("
    SELECT latitude, longitude, alarmuhrzeit, stichwort 
    FROM einsaetze 
    WHERE latitude IS NOT NULL 
      AND longitude IS NOT NULL 
      AND STR_TO_DATE(alarmuhrzeit, '%d.%m.%Y %H:%i') 
          BETWEEN STR_TO_DATE(:startdatum, '%Y-%m-%d %H:%i:%s') 
          AND STR_TO_DATE(:enddatum, '%Y-%m-%d %H:%i:%s')
    ");
    $pinQuery->execute([':startdatum' => $startdatum, ':enddatum' => $enddatum]);
    $pinData = $pinQuery->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Fehler beim Laden der Daten: " . htmlspecialchars($e->getMessage());
}


?>


<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistiken</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster/dist/MarkerCluster.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster/dist/MarkerCluster.Default.css" />
    <script src="https://unpkg.com/leaflet.markercluster/dist/leaflet.markercluster.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>




</head>
<body>
<header>
    <h1>Einsatzstatistiken</h1>
    <?php include 'parts/menue.php'; ?>
</header>

<main>
    <!-- Filter für Start- und Enddatum -->
    <section id="filter">
        <h2>Zeitraum auswählen</h2>
        <form method="GET" action="statistiken.php" class="filter-form">
            <label for="startdatum">Startdatum:</label>
            <input type="date" id="startdatum" name="startdatum" 
                value="<?= htmlspecialchars((new DateTime($startdatum ?? 'now'))->format('Y-m-d')) ?>" 
                required>

            <label for="enddatum">Enddatum:</label>
            <input type="date" id="enddatum" name="enddatum" 
                value="<?= htmlspecialchars((new DateTime($enddatum ?? 'now'))->format('Y-m-d')) ?>" 
                required>

            <button type="submit">Anzeigen</button>
        </form>
    </section>


    <!-- Anzeige der Statistiken -->
    <section id="einsatz-statistik">
    <h2>Statistiken für den Zeitraum 
        <?php 
        try {
            // Startdatum und Enddatum in DateTime-Objekte umwandeln
            $startdatumObj = new DateTime($startdatum);
            $enddatumObj = new DateTime($enddatum);

            // Zeitraum korrekt formatieren und anzeigen
            echo htmlspecialchars($startdatumObj->format('d.m.Y')) . " bis " . htmlspecialchars($enddatumObj->format('d.m.Y'));
        } catch (Exception $e) {
            echo "Ungültiger Zeitraum"; // Fallback-Anzeige bei Fehlern
        }
        ?>
    </h2>
    <?php if (isset($error)): ?>
        <p><?= htmlspecialchars($error) ?></p>
    <?php else: ?>
        <p>Gesamtanzahl der Einsätze: <strong><?= htmlspecialchars($totalEinsaetze) ?></strong></p>

            <?php if (!empty($einsaetzeNachKategorie)): ?>
                    <?php foreach ($einsaetzeNachKategorie as $kategorie): ?>
                            <strong><?= htmlspecialchars($kategorie['kategorie']) ?>:</strong> 
                            <?= htmlspecialchars($kategorie['anzahl']) ?>
                            </br>
                    <?php endforeach; ?>
            <?php endif; ?>

        <?php if ($totalEinsaetze != 0): ?>
            <p>Durchschnittliche Einsatzdauer: <strong><?= htmlspecialchars(round($durchschnittsdauer, 2)) ?> Minuten</strong></p>
        <?php endif; ?>
    <?php endif; ?>
</section>

    <!-- Diagramm für häufigste Stichworte -->
        <?php if ($totalEinsaetze != 0): ?>
        <h2>Häufigste Stichworte</h2>    
        <?php endif; ?>
        
        <canvas id="stichwortChart" ></canvas>
        <script>
            const stichwortLabels = <?= json_encode(array_column($stichworte, 'stichwort')) ?>;
            const stichwortData = <?= json_encode(array_column($stichworte, 'anzahl')) ?>;

            new Chart(document.getElementById('stichwortChart'), {
                type: 'bar',
                data: {
                    labels: stichwortLabels,
                    datasets: [{
                        label: 'Häufigkeit der Stichworte',
                        data: stichwortData,
                        backgroundColor: [
                            'rgba(255, 99, 132, 0.5)',
                            'rgba(54, 162, 235, 0.5)',
                            'rgba(255, 206, 86, 0.5)',
                            'rgba(75, 192, 192, 0.5)',
                            'rgba(153, 102, 255, 0.5)',
                            'rgba(255, 159, 64, 0.5)',
                            'rgba(199, 199, 199, 0.5)',
                            'rgba(83, 102, 255, 0.5)',
                            'rgba(128, 159, 64, 0.5)',
                            'rgba(183, 109, 192, 0.5)'
                        ],
                        borderColor: [
                            'rgba(255, 99, 132, 1)',
                            'rgba(54, 162, 235, 1)',
                            'rgba(255, 206, 86, 1)',
                            'rgba(75, 192, 192, 1)',
                            'rgba(153, 102, 255, 1)',
                            'rgba(255, 159, 64, 1)',
                            'rgba(199, 199, 199, 1)',
                            'rgba(83, 102, 255, 1)',
                            'rgba(128, 159, 64, 1)',
                            'rgba(183, 109, 192, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    scales: {
                        x: {
                            title: {
                                display: true,
                                text: 'Stichworte'
                            }
                        },
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1,
                                callback: function(value) {
                                    return Number.isInteger(value) ? value : '';
                                }
                            },
                            title: {
                                display: true,
                                text: 'Anzahl'
                            }
                        }
                    }
                }
            });
        </script>

<section>
    <h2>Heatmap</h2>
    <div id="map" style="width: 100%; height: 700px;"></div>
    <script>
    // Karte initialisieren
    const map = L.map('map').setView([52.5200, 13.4050], 11); // Berlin-Zentrum

    // Tile Layer hinzufügen (OpenStreetMap)
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);

    // Cluster-Gruppe erstellen
    const markers = L.markerClusterGroup({
        disableClusteringAtZoom: 15 // Gruppierung wird ab Zoom-Level 15 aufgehoben
    });

    // Pin-Daten aus der Datenbank
    const pinData = <?= json_encode($pinData) ?>;

    // Koordinaten gruppieren
    const groupedPins = {};
    pinData.forEach(pin => {
        const key = `${pin.latitude},${pin.longitude}`;
        if (!groupedPins[key]) {
            groupedPins[key] = { coords: [parseFloat(pin.latitude), parseFloat(pin.longitude)], details: [] };
        }
        groupedPins[key].details.push(pin);
    });

    // Punkte verarbeiten
    Object.values(groupedPins).forEach(group => {
        const { coords, details } = group;

        // Sicherstellen, dass die Koordinaten valide sind
        if (!coords || coords.some(coord => isNaN(coord))) {
            return; // Überspringt Einsätze ohne gültige Koordinaten
        }

        if (details.length === 1) {
            // Einzelner Punkt
            const pin = details[0];
            const redIcon = L.divIcon({
                className: 'custom-div-icon',
                html: "<div style='background-color: red; width: 10px; height: 10px; border-radius: 50%;'></div>",
                iconSize: [10, 10],
                iconAnchor: [5, 5] // Zentriert den Marker
            });
            const marker = L.marker(coords, { icon: redIcon });

            // Popup-Inhalt
            marker.bindPopup(`
                <strong>Stichwort:</strong> ${pin.stichwort}<br>
                <strong>Datum:</strong> ${pin.alarmuhrzeit}
            `);

            // Marker zur Cluster-Gruppe hinzufügen
            markers.addLayer(marker);

        } else {
            // Gruppierte Punkte
            const count = details.length;
            const markerSize = 15 + count; // Größe anpassen

            const groupIcon = L.divIcon({
                className: 'custom-div-icon',
                html: `<div style='
                    background-color: red;
                    width: ${markerSize}px;
                    height: ${markerSize}px;
                    border-radius: 50%;
                    color: white;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 12px;
                    font-weight: bold;
                '>${count}</div>`,
                iconSize: [markerSize, markerSize],
                iconAnchor: [markerSize / 2, markerSize / 2] // Zentriert den Marker
            });

            const marker = L.marker(coords, { icon: groupIcon });

            // Popup-Inhalt für Gruppierungen
            const popupContent = `
                <strong>Einsatzdetails (${count}):</strong>
                <ul>
                    ${details
                        .map(
                            detail =>
                                `<li>
                                    <strong>Stichwort:</strong> ${detail.stichwort}<br>
                                    <strong>Datum:</strong> ${detail.alarmuhrzeit}<br>
                                </li>`
                        )
                        .join('')}
                </ul>
            `;
            marker.bindPopup(popupContent);

            // Marker zur Cluster-Gruppe hinzufügen
            markers.addLayer(marker);
        }
    });


    // Cluster-Gruppe zur Karte hinzufügen
    map.addLayer(markers);
</script>







</section>


</main>
</body>
</html>
