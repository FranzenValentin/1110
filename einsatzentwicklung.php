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

// Berechnung der kumulierten Einsätze mit Zwischenschritten
$kumuliertAktuellesJahrDetail = [];
$kumuliertVorjahrDetail = [];

// Detaillierte Daten für das aktuelle Jahr
for ($monat = 1; $monat <= 12; $monat++) {
    // Start- und Endwerte für die Interpolation
    $startAktuell = $kumuliertAktuellesJahr[$monat - 1] ?? 0;
    $endAktuell = $kumuliertAktuellesJahr[$monat];

    // Füge Zwischenschritte hinzu (z. B. 30 Schritte für 30 Tage)
    for ($step = 0; $step <= 30; $step++) {
        $kumuliertAktuellesJahrDetail[] = $startAktuell + (($endAktuell - $startAktuell) * ($step / 30));
    }
}

// Detaillierte Daten für das Vorjahr
for ($monat = 1; $monat <= 12; $monat++) {
    $startVorjahr = $kumuliertVorjahr[$monat - 1] ?? 0;
    $endVorjahr = $kumuliertVorjahr[$monat];

    // Füge Zwischenschritte hinzu
    for ($step = 0; $step <= 30; $step++) {
        $kumuliertVorjahrDetail[] = $startVorjahr + (($endVorjahr - $startVorjahr) * ($step / 30));
    }
}

// Berechnung der kumulierten Einsätze pro Woche
$woechentlichAktuellesJahr = [];
$woechentlichVorjahr = [];

// Funktion zur Berechnung der wöchentlichen Einsätze
function berechneWoechentlich($daten, $jahr)
{
    $woechentlich = array_fill(1, 52, 0); // 52 Wochen initialisieren
    $kumuliert = 0;

    foreach ($daten as $row) {
        if ((int)$row['jahr'] === $jahr) {
            $monat = (int)$row['monat'];
            $anzahl = (int)$row['anzahl'];
            $kumuliert += $anzahl;
            $woche = (int)date('W', mktime(0, 0, 0, $monat, 1, $jahr));
            $woechentlich[$woche] = $kumuliert;
        }
    }

    return $woechentlich;
}

// Wöchentliche Einsatzzahlen berechnen
$woechentlichAktuellesJahr = berechneWoechentlich($daten, $jahr);
$woechentlichVorjahr = berechneWoechentlich($daten, $vorjahr);





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
        const woechentlichAktuellesJahr = <?= json_encode(array_values($woechentlichAktuellesJahr)) ?>;
        const woechentlichVorjahr = <?= json_encode(array_values($woechentlichVorjahr)) ?>;
        const wochen = Array.from({ length: 52 }, (_, i) => `Woche ${i + 1}`);

        // Chart erstellen
        const ctx = document.getElementById('einsatzEntwicklungChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: wochen,
                datasets: [
                    {
                        label: 'Kumuliert <?= $vorjahr ?>',
                        data: woechentlichVorjahr,
                        borderColor: 'rgba(54, 162, 235, 1)', // Blau
                        backgroundColor: 'rgba(54, 162, 235, 0.2)',
                        fill: false,
                        tension: 0.4, // Glattere Linie
                        pointStyle: 'circle',
                        pointRadius: 5,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Kumuliert <?= $jahr ?>',
                        data: woechentlichAktuellesJahr,
                        borderColor: 'rgba(255, 99, 132, 1)', // Rot
                        backgroundColor: 'rgba(255, 99, 132, 0.2)',
                        fill: false,
                        tension: 0.4, // Glattere Linie
                        pointStyle: 'circle',
                        pointRadius: 5,
                        yAxisID: 'y'
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
                            text: 'Kumulierte Einsätze'
                        },
                        position: 'left'
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Wochen'
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>
