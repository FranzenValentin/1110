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

foreach ($daten as $row) {
    if ($row['jahr'] == $jahr) {
        $datenAktuellesJahr[(int)$row['monat']] = (int)$row['anzahl'];
    } elseif ($row['jahr'] == $vorjahr) {
        $datenVorjahr[(int)$row['monat']] = (int)$row['anzahl'];
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
        // PHP-Daten in JavaScript übertragen
        const datenAktuellesJahr = <?= json_encode(array_values($datenAktuellesJahr)) ?>;
        const datenVorjahr = <?= json_encode(array_values($datenVorjahr)) ?>;
        const monate = ['Jan', 'Feb', 'Mär', 'Apr', 'Mai', 'Jun', 'Jul', 'Aug', 'Sep', 'Okt', 'Nov', 'Dez'];

        // Liniendiagramm erstellen
        const ctx = document.getElementById('einsatzEntwicklungChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: monate,
                datasets: [
                    {
                        label: '<?= $vorjahr ?>',
                        data: datenVorjahr,
                        borderColor: 'rgba(54, 162, 235, 1)', // Blau
                        backgroundColor: 'rgba(54, 162, 235, 0.2)',
                        fill: true
                    },
                    {
                        label: '<?= $jahr ?>',
                        data: datenAktuellesJahr,
                        borderColor: 'rgba(255, 99, 132, 1)', // Rot
                        backgroundColor: 'rgba(255, 99, 132, 0.2)',
                        fill: true
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
                            text: 'Einsatzzahlen'
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
