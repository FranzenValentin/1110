<?php
session_start();
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header('Location: login.php');
    exit;
}
require 'db.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Variablen initialisieren
$totalEinsaetze = 0;
$durchschnittsdauer = null;
$stichworte = [];
$error = null;
$pinData = [];

// Standardwerte für Start- und Enddatum setzen
if (!isset($_GET['startdatum']) || !isset($_GET['enddatum'])) {
    $currentDate = new DateTime();
    $startdatum = $currentDate->format('Y-m-01'); // Erster Tag des aktuellen Monats
    $enddatum = $currentDate->format('Y-m-t');    // Letzter Tag des aktuellen Monats
} else {
    $startdatum = $_GET['startdatum'];
    $enddatum = $_GET['enddatum'];
}

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
    $totalStmt = $pdo->prepare("SELECT COUNT(*) AS total FROM einsaetze WHERE STR_TO_DATE(alarmuhrzeit, '%d.%m.%Y %H:%i') BETWEEN STR_TO_DATE(:startdatum, '%Y-%m-%d %H:%i:%s') AND STR_TO_DATE(:enddatum, '%Y-%m-%d %H:%i:%s')");
    $totalStmt->execute([':startdatum' => $startdatum, ':enddatum' => $enddatum]);
    $totalEinsaetze = $totalStmt->fetch()['total'];

    // Durchschnittliche Einsatzdauer
    $dauerStmt = $pdo->prepare("SELECT AVG(TIMESTAMPDIFF(MINUTE, STR_TO_DATE(alarmuhrzeit, '%d.%m.%Y %H:%i'), STR_TO_DATE(zurueckzeit, '%d.%m.%Y %H:%i'))) AS durchschnittsdauer FROM einsaetze WHERE STR_TO_DATE(alarmuhrzeit, '%d.%m.%Y %H:%i') BETWEEN STR_TO_DATE(:startdatum, '%Y-%m-%d %H:%i:%s') AND STR_TO_DATE(:enddatum, '%Y-%m-%d %H:%i:%s')");
    $dauerStmt->execute([':startdatum' => $startdatum, ':enddatum' => $enddatum]);
    $durchschnittsdauer = $dauerStmt->fetch()['durchschnittsdauer'];

    // Häufigste Stichworte
    $stichwortStmt = $pdo->prepare("SELECT stichwort, COUNT(*) AS anzahl FROM einsaetze WHERE STR_TO_DATE(alarmuhrzeit, '%d.%m.%Y %H:%i') BETWEEN STR_TO_DATE(:startdatum, '%Y-%m-%d %H:%i:%s') AND STR_TO_DATE(:enddatum, '%Y-%m-%d %H:%i:%s') GROUP BY stichwort ORDER BY anzahl DESC LIMIT 8");
    $stichwortStmt->execute([':startdatum' => $startdatum, ':enddatum' => $enddatum]);
    $stichworte = $stichwortStmt->fetchAll(PDO::FETCH_ASSOC);

    // Heatmap-Daten aus der Datenbank abrufen
    $pinQuery = $pdo->prepare("SELECT latitude, longitude FROM einsaetze WHERE STR_TO_DATE(alarmuhrzeit, '%d.%m.%Y %H:%i') BETWEEN STR_TO_DATE(:startdatum, '%Y-%m-%d %H:%i:%s') AND STR_TO_DATE(:enddatum, '%Y-%m-%d %H:%i:%s')");
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
    <form method="POST" action="logout.php" class="logout-form">
        <button type="submit">Logout</button>
    </form>
</header>

<main>
    <section id="filter">
        <h2>Zeitraum auswählen</h2>
        <form method="GET" action="statistiken.php" class="filter-form">
            <label for="startdatum">Startdatum:</label>
            <input type="date" id="startdatum" name="startdatum" value="<?= htmlspecialchars((new DateTime($startdatum ?? 'now'))->format('Y-m-d')) ?>" required>

            <label for="enddatum">Enddatum:</label>
            <input type="date" id="enddatum" name="enddatum" value="<?= htmlspecialchars((new DateTime($enddatum ?? 'now'))->format('Y-m-d')) ?>" required>

            <button type="submit">Anzeigen</button>
        </form>
    </section>

    <section id="einsatz-statistik">
        <h2>Statistiken für den Zeitraum
            <?= htmlspecialchars($startdatumObj->format('d.m.Y')) ?> bis <?= htmlspecialchars($enddatumObj->format('d.m.Y')) ?>
        </h2>
        <p>Gesamtanzahl der Einsätze: <strong><?= htmlspecialchars($totalEinsaetze) ?></strong></p>
        <?php if ($totalEinsaetze != 0): ?>
            <p>Durchschnittliche Einsatzdauer: <strong><?= htmlspecialchars(round($durchschnittsdauer, 2)) ?> Minuten</strong></p>
        <?php endif; ?>
    </section>

    <section>
        <h2>Karte mit Einsatz-Pins</h2>
        <div id="map" style="width: 100%; height: 500px;"></div>
        <script>
            const pinData = <?= json_encode($pinData) ?>;

            const map = L.map('map').setView([52.5200, 13.4050], 11);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(map);

            const markers = L.markerClusterGroup();

            pinData.forEach(pin => {
                if (pin.latitude && pin.longitude) {
                    const redIcon = L.divIcon({
                        className: 'custom-div-icon',
                        html: "<div style='background-color: red; width: 10px; height: 10px; border-radius: 50%;'></div>",
                        iconSize: [10, 10]
                    });

                    const marker = L.marker([pin.latitude, pin.longitude], { icon: redIcon });
                    markers.addLayer(marker);
                }
            });

            map.addLayer(markers);
        </script>
    </section>
</main>
</body>
</html>
