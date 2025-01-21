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


$stmt = $pdo->prepare($query);
$stmt->execute([':jahr' => $jahr, ':vorjahr' => $vorjahr]);

// Daten aufbereiten
$daten = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Daten für das aktuelle und das vorherige Jahr initialisieren
$datenAktuellesJahr = [];
$datenVorjahr = [];
$kumuliertAktuellesJahr = [];
$kumuliertVorjahr = [];

// Liste aller Tage des Jahres erzeugen
$alleTageAktuellesJahr = [];
$alleTageVorjahr = [];
for ($d = 1; $d <= 365; $d++) {
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
        // Daten aus PHP übertragen
        const datenAktuellesJahr = <?= json_encode(array_values($datenAktuellesJahr)) ?>;
        const datenVorjahr = <?= json_encode(array_values($datenVorjahr)) ?>;
        const kumuliertAktuellesJahr = <?= json_encode(array_values($kumuliertAktuellesJahr)) ?>;
        const kumuliertVorjahr = <?= json_encode(array_values($kumuliertVorjahr)) ?>;
        const monate = ['Jan', 'Feb', 'Mär', 'Apr', 'Mai', 'Jun', 'Jul', 'Aug', 'Sep', 'Okt', 'Nov', 'Dez'];

        // Chart erstellen
        const tageAktuellesJahr = <?= json_encode(array_keys($alleTageAktuellesJahr)) ?>;
const tageVorjahr = <?= json_encode(array_keys($alleTageVorjahr)) ?>;
const kumuliertAktuellesJahr = <?= json_encode(array_values($kumuliertAktuellesJahr)) ?>;
const kumuliertVorjahr = <?= json_encode(array_values($kumuliertVorjahr)) ?>;

const ctx = document.getElementById('einsatzEntwicklungChart').getContext('2d');
new Chart(ctx, {
    type: 'line', // Using line chart for better visualization of cumulative values
    data: {
        labels: tageAktuellesJahr, // Tageslabels für die X-Achse
        datasets: [
            {
                label: 'Kumuliert <?= $vorjahr ?>', // Cumulative data for the previous year
                data: kumuliertVorjahr, // Daily cumulative data for the previous year
                borderColor: 'rgba(54, 162, 235, 1)', // Blue
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                fill: false, // No fill under the line
                tension: 0.1 // Smooth lines
            },
            {
                label: 'Kumuliert <?= $jahr ?>', // Cumulative data for the current year
                data: kumuliertAktuellesJahr, // Daily cumulative data for the current year
                borderColor: 'rgba(255, 99, 132, 1)', // Red
                backgroundColor: 'rgba(255, 99, 132, 0.2)',
                fill: false, // No fill under the line
                tension: 0.1 // Smooth lines
            }
        ]
    },
    options: {
        responsive: true, // Ensures the chart adapts to the screen size
        plugins: {
            legend: {
                position: 'top' // Positions the legend at the top
            },
            tooltip: {
                enabled: true, // Enables tooltips
                callbacks: {
                    label: function(tooltipItem) {
                        const dataset = tooltipItem.dataset;
                        const value = tooltipItem.raw;
                        return `${dataset.label}: ${value}`;
                    }
                }
            }
        },
        scales: {
            x: {
                type: 'time', // Time-based X-axis
                time: {
                    unit: 'day', // Display each day on the X-axis
                    displayFormats: {
                        day: 'dd.MM.yyyy' // Format for days
                    },
                    tooltipFormat: 'dd.MM.yyyy' // Tooltip format
                },
                title: {
                    display: true,
                    text: 'Tage' // Label for X-axis
                }
            },
            y: {
                beginAtZero: true, // Y-axis starts at zero
                title: {
                    display: true,
                    text: 'Kumulierte Einsätze' // Label for Y-axis
                }
            }
        }
    }
});



    </script>
</body>
</html>
