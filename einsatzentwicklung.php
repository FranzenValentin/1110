<?php
require_once 'session_check.php';
require 'db.php';

// Aktuelles Jahr und Vorjahr berechnen
$jahr = date('Y');
$vorjahr = $jahr - 1;

// SQL-Abfrage: Einsatzzahlen für das aktuelle Jahr und das Vorjahr tagesgenau abrufen
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
$daten = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Initialisierung der Arrays für beide Jahre
$alleTageAktuellesJahr = [];
$alleTageVorjahr = [];
$kumuliertAktuellesJahr = [];
$kumuliertVorjahr = [];

// Alle Tage des aktuellen und des vorherigen Jahres initialisieren
for ($d = 0; $d < 365; $d++) {
    $tagAktuellesJahr = date('Y-m-d', strtotime("$jahr-01-01 +$d days"));
    $tagVorjahr = date('Y-m-d', strtotime("$vorjahr-01-01 +$d days"));

    $alleTageAktuellesJahr[$tagAktuellesJahr] = 0;
    $alleTageVorjahr[$tagVorjahr] = 0;
}

// Daten aus der Datenbank in die Arrays einfügen
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
foreach ($alleTageAktuellesJahr as $anzahl) {
    $summeAktuellesJahr += $anzahl;
    $kumuliertAktuellesJahr[] = $summeAktuellesJahr;
}
foreach ($alleTageVorjahr as $anzahl) {
    $summeVorjahr += $anzahl;
    $kumuliertVorjahr[] = $summeVorjahr;
}

// Labels für die X-Achse (alle Tage)
$tageAktuellesJahr = array_keys($alleTageAktuellesJahr);
$tageVorjahr = array_keys($alleTageVorjahr);
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
    <canvas id="einsatzEntwicklungChart" width="800" height="400"></canvas>

    <script>
    // Daten aus PHP übertragen
    const tageAktuellesJahr = <?= json_encode($tageAktuellesJahr) ?>;
    const kumuliertAktuellesJahr = <?= json_encode($kumuliertAktuellesJahr) ?>;
    const tageVorjahr = <?= json_encode($tageVorjahr) ?>;
    const kumuliertVorjahr = <?= json_encode($kumuliertVorjahr) ?>;

    // Chart.js-Diagramm erstellen
    const ctx = document.getElementById('einsatzEntwicklungChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: tageAktuellesJahr, // Alle Tage als Labels für die X-Achse
            datasets: [
                {
                    label: 'Kumuliert <?= $vorjahr ?>',
                    data: kumuliertVorjahr,
                    borderColor: 'rgba(54, 162, 235, 1)',
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    fill: false,
                    tension: 0.4,
                    pointRadius: 0, // Keine Punkte
                    pointHoverRadius: 0 // Keine Hover-Punkte
                },
                {
                    label: 'Kumuliert <?= $jahr ?>',
                    data: kumuliertAktuellesJahr,
                    borderColor: 'rgba(255, 99, 132, 1)',
                    backgroundColor: 'rgba(255, 99, 132, 0.2)',
                    fill: false,
                    tension: 0.4,
                    pointRadius: 0,
                    pointHoverRadius: 0
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
                    enabled: true,
                    callbacks: {
                        label: function(context) {
                            return `${context.dataset.label}: ${context.raw} Einsätze`;
                        }
                    }
                }
            },
            scales: {
                x: {
                    type: 'category', // X-Achse mit Kategorie-Typ
                    title: {
                        display: true,
                        text: 'Monate'
                    },
                    ticks: {
                        callback: function(value, index, ticks) {
                            // Formatieren des Labels: Nur Monat anzeigen
                            const date = new Date(tageAktuellesJahr[index]);
                            const monthNames = ['Jan', 'Feb', 'Mär', 'Apr', 'Mai', 'Jun', 'Jul', 'Aug', 'Sep', 'Okt', 'Nov', 'Dez'];
                            return monthNames[date.getMonth()];
                        },
                        maxRotation: 0, // Verhindert rotierte Labels
                        minRotation: 0
                    },
                    grid: {
                        display: false // Gitterlinien deaktivieren
                    }
                },
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Kumulierte Einsätze'
                    },
                    grid: {
                        color: 'rgba(200, 200, 200, 0.3)' // Gitterlinienfarbe ändern
                    }
                }
            }
        }
    });
</script>

</body>
</html>
