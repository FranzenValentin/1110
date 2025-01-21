<?php
require_once 'session_check.php';
require 'db.php';

// Aktuelles Jahr und Vorjahr berechnen
$jahr = date('Y');
$vorjahr = $jahr - 1;

// SQL-Abfrage: Einsatzzahlen für das aktuelle Jahr und das Vorjahr
$query = "
    SELECT 
        DATE(STR_TO_DATE(e.alarmuhrzeit, '%d.%m.%Y %H:%i')) AS tag,
        YEAR(STR_TO_DATE(e.alarmuhrzeit, '%d.%m.%Y %H:%i')) AS jahr,
        COUNT(*) AS anzahl
    FROM einsaetze e
    WHERE YEAR(STR_TO_DATE(e.alarmuhrzeit, '%d.%m.%Y %H:%i')) IN (:jahr, :vorjahr)
    GROUP BY jahr, tag
    ORDER BY jahr, tag
";

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute([':jahr' => $jahr, ':vorjahr' => $vorjahr]);
    $daten = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Datenbankfehler: " . $e->getMessage());
}

// Debugging: Rohdaten aus der Datenbank ausgeben
header('Content-Type: application/json');
echo json_encode($daten, JSON_PRETTY_PRINT);
exit;

// Daten für das aktuelle und das vorherige Jahr initialisieren
$datenAktuellesJahr = [];
$datenVorjahr = [];
$kumuliertAktuellesJahr = [];
$kumuliertVorjahr = [];

// Liste aller Tage des Jahres erzeugen
$alleTageAktuellesJahr = [];
$alleTageVorjahr = [];
for ($d = 0; $d < 365; $d++) {
    $tagAktuellesJahr = date('Y-m-d', strtotime("$jahr-01-01 +$d days"));
    $tagVorjahr = date('Y-m-d', strtotime("$vorjahr-01-01 +$d days"));
    $alleTageAktuellesJahr[$tagAktuellesJahr] = 0;
    $alleTageVorjahr[$tagVorjahr] = 0;
}

// Datenbankergebnisse verarbeiten
foreach ($daten as $row) {
    $tag = $row['tag'];
    $anzahl = (int)$row['anzahl'];
    if ($row['jahr'] == $jahr) {
        $alleTageAktuellesJahr[$tag] = $anzahl;
    } elseif ($row['jahr'] == $vorjahr) {
        $alleTageVorjahr[$tag] = $anzahl;
    }
}

// Debugging: Verarbeitete Daten für beide Jahre ausgeben
echo json_encode([
    'alleTageAktuellesJahr' => $alleTageAktuellesJahr,
    'alleTageVorjahr' => $alleTageVorjahr,
], JSON_PRETTY_PRINT);
exit;

// Kumulierte Werte berechnen
$summeAktuellesJahr = 0;
$summeVorjahr = 0;
foreach ($alleTageAktuellesJahr as $tag => $anzahl) {
    $summeAktuellesJahr += $anzahl;
    $kumuliertAktuellesJahr[] = $summeAktuellesJahr;
}
foreach ($alleTageVorjahr as $tag => $anzahl) {
    $summeVorjahr += $anzahl;
    $kumuliertVorjahr[] = $summeVorjahr;
}

// Debugging: Kumulierte Werte ausgeben
echo json_encode([
    'kumuliertAktuellesJahr' => $kumuliertAktuellesJahr,
    'kumuliertVorjahr' => $kumuliertVorjahr,
], JSON_PRETTY_PRINT);
exit;
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Einsatzentwicklung</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header>
    <h1>Einsatzentwicklung</h1>
    <form method="POST" action="logout.php" class="logout-form">
        <button type="submit">Logout<?= $firstName ? " - " . htmlspecialchars($firstName) : "" ?></button>
    </form>
    <form method="POST" action="index.php" class="back-form">
        <button type="submit">Zurück</button>
    </form>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    </header>

    <canvas id="einsatzEntwicklungChart" width="800" height="400"></canvas>
    
    <script>
    // Daten aus PHP
    const tageAktuellesJahr = <?= json_encode(array_keys($alleTageAktuellesJahr)) ?>;
    const kumuliertAktuellesJahr = <?= json_encode(array_values($kumuliertAktuellesJahr)) ?>;
    const tageVorjahr = <?= json_encode(array_keys($alleTageVorjahr)) ?>;
    const kumuliertVorjahr = <?= json_encode(array_values($kumuliertVorjahr)) ?>;

    const ctx = document.getElementById('einsatzEntwicklungChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: tageAktuellesJahr,
            datasets: [
                {
                    label: 'Kumuliert <?= $vorjahr ?>',
                    data: kumuliertVorjahr,
                    borderColor: 'rgba(54, 162, 235, 1)', // Blau
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    fill: false,
                    tension: 0.1
                },
                {
                    label: 'Kumuliert <?= $jahr ?>',
                    data: kumuliertAktuellesJahr,
                    borderColor: 'rgba(255, 99, 132, 1)', // Rot
                    backgroundColor: 'rgba(255, 99, 132, 0.2)',
                    fill: false,
                    tension: 0.1
                }
            ]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top'
                },
                tooltip: {
                    enabled: true
                }
            },
            scales: {
                x: {
                    type: 'time',
                    time: {
                        unit: 'day',
                        displayFormats: {
                            day: 'dd.MM.yyyy'
                        },
                        tooltipFormat: 'dd.MM.yyyy'
                    },
                    title: {
                        display: true,
                        text: 'Tage'
                    }
                },
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Kumulierte Einsätze'
                    }
                }
            }
        }
    });
</script>

</body>
</html>
