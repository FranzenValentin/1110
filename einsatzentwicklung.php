<?php
require_once 'session_check.php';
require 'db.php';

// Aktuelles Jahr und Vorjahr berechnen
$jahr = date('Y');
$vorjahr = $jahr - 1;

// SQL-Abfrage: Einsatzzahlen für das aktuelle Jahr und das Vorjahr
$query = "
    SELECT 
        YEAR(STR_TO_DATE(e.alarmuhrzeit, '%d.%m.%Y %H:%i')) AS jahr,
        MONTH(STR_TO_DATE(e.alarmuhrzeit, '%d.%m.%Y %H:%i')) AS monat,
        COUNT(*) AS anzahl
    FROM einsaetze e
    WHERE YEAR(STR_TO_DATE(e.alarmuhrzeit, '%d.%m.%Y %H:%i')) IN (:jahr, :vorjahr)
    GROUP BY jahr, monat
    ORDER BY jahr, monat
";

$stmt = $pdo->prepare($query);
$stmt->execute([':jahr' => $jahr, ':vorjahr' => $vorjahr]);

// Daten aufbereiten
$daten = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Initialisiere Arrays für beide Jahre
$datenAktuellesJahr = array_fill(1, 12, 0); // 12 Monate mit 0 initialisieren
$datenVorjahr = array_fill(1, 12, 0);
$kumuliertAktuellesJahr = array_fill(1, 12, 0);
$kumuliertVorjahr = array_fill(1, 12, 0);

foreach ($daten as $row) {
    $monat = (int)$row['monat'];
    $anzahl = (int)$row['anzahl'];
    if ($row['jahr'] == $jahr) {
        $datenAktuellesJahr[$monat] = $anzahl;
        $kumuliertAktuellesJahr[$monat] = ($kumuliertAktuellesJahr[$monat - 1] ?? 0) + $anzahl;
    } elseif ($row['jahr'] == $vorjahr) {
        $datenVorjahr[$monat] = $anzahl;
        $kumuliertVorjahr[$monat] = ($kumuliertVorjahr[$monat - 1] ?? 0) + $anzahl;
    }
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
        <button type="submit">Logout</button>
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
        const ctx = document.getElementById('einsatzEntwicklungChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: monate,
                datasets: [
                    // Balkendiagramme für Einsatzzahlen je Monat
                    {
                        label: 'Einsätze je Monat <?= $vorjahr ?>',
                        data: datenVorjahr,
                        backgroundColor: 'rgba(54, 162, 235, 0.5)', // Blau
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Einsätze je Monat <?= $jahr ?>',
                        data: datenAktuellesJahr,
                        backgroundColor: 'rgba(255, 99, 132, 0.5)', // Rot
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 1,
                        yAxisID: 'y'
                    },
                    // Liniendiagramme für kumulierte Einsatzzahlen
                    {
                        label: 'Kumuliert <?= $vorjahr ?>',
                        data: kumuliertVorjahr,
                        borderColor: 'rgba(54, 162, 235, 1)', // Blau
                        backgroundColor: 'rgba(54, 162, 235, 0.2)',
                        fill: false,
                        type: 'line',
                        yAxisID: 'y2'
                    },
                    {
                        label: 'Kumuliert <?= $jahr ?>',
                        data: kumuliertAktuellesJahr,
                        borderColor: 'rgba(255, 99, 132, 1)', // Rot
                        backgroundColor: 'rgba(255, 99, 132, 0.2)',
                        fill: false,
                        type: 'line',
                        yAxisID: 'y2'
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
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Einsätze je Monat'
                        },
                        position: 'left'
                    },
                    y2: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Kumulierte Einsätze'
                        },
                        position: 'right',
                        grid: {
                            drawOnChartArea: false
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Monate'
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>
