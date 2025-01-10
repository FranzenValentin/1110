<?php
session_start();
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header('Location: login.php');
    exit;
}

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

// Berechnung der kumulierten Einsätze mit Zwischenschritten (z. B. tägliche Werte)
$kumuliertAktuellesJahrDetail = [];
$kumuliertVorjahrDetail = [];
for ($monat = 1; $monat <= 12; $monat++) {
    if ($monat > 1) {
        $startAktuell = $kumuliertAktuellesJahr[$monat - 1];
        $endAktuell = $kumuliertAktuellesJahr[$monat];
        for ($step = 1; $step <= 30; $step++) {
            $kumuliertAktuellesJahrDetail[] = $startAktuell + (($endAktuell - $startAktuell) * ($step / 30));
        }

        $startVorjahr = $kumuliertVorjahr[$monat - 1];
        $endVorjahr = $kumuliertVorjahr[$monat];
        for ($step = 1; $step <= 30; $step++) {
            $kumuliertVorjahrDetail[] = $startVorjahr + (($endVorjahr - $startVorjahr) * ($step / 30));
        }
    } else {
        $kumuliertAktuellesJahrDetail[] = $kumuliertAktuellesJahr[$monat];
        $kumuliertVorjahrDetail[] = $kumuliertVorjahr[$monat];
    }
}


?>



<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Einsatzentwicklung</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <h1>Einsatzentwicklung</h1>
    <canvas id="einsatzEntwicklungChart" width="800" height="400"></canvas>
    
    <script>
        // Daten aus PHP übertragen
        const datenVorjahr = <?= json_encode(array_values($datenVorjahr)) ?>;
        const datenAktuellesJahr = <?= json_encode(array_values($datenAktuellesJahr)) ?>;
        const kumuliertAktuellesJahrDetail = <?= json_encode(array_values($kumuliertAktuellesJahrDetail)) ?>;
        const kumuliertVorjahrDetail = <?= json_encode(array_values($kumuliertVorjahrDetail)) ?>;
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
                        type: 'bar',
                        yAxisID: 'y'
                    },
                    {
                        label: 'Einsätze je Monat <?= $jahr ?>',
                        data: datenAktuellesJahr,
                        backgroundColor: 'rgba(255, 99, 132, 0.5)', // Rot
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 1,
                        type: 'bar',
                        yAxisID: 'y'
                    },
                    // Liniendiagramme für kumulierte Einsatzzahlen
                    {
                        label: 'Kumuliert <?= $vorjahr ?>',
                        data: kumuliertVorjahrDetail,
                        borderColor: 'rgba(54, 162, 235, 1)', // Blau
                        backgroundColor: 'rgba(54, 162, 235, 0.2)',
                        fill: false,
                        type: 'line',
                        yAxisID: 'y2',
                        tension: 0.4 // Glattere Linie
                    },
                    {
                        label: 'Kumuliert <?= $jahr ?>',
                        data: kumuliertAktuellesJahrDetail,
                        borderColor: 'rgba(255, 99, 132, 1)', // Rot
                        backgroundColor: 'rgba(255, 99, 132, 0.2)',
                        fill: false,
                        type: 'line',
                        yAxisID: 'y2',
                        tension: 0.4 // Glattere Linie
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top' // Position der Legende
                    },
                    tooltip: {
                        enabled: true // Tooltips für zusätzliche Informationen
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Einsätze je Monat'
                        },
                        position: 'left' // Linke Achse
                    },
                    y2: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Kumulierte Einsätze'
                        },
                        position: 'right', // Rechte Achse
                        grid: {
                            drawOnChartArea: false // Keine Gitterlinien für rechte Achse
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
