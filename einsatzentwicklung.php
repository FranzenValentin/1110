<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'parts/session_check.php';
require 'parts/db.php';

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
$kumuliertheuteVorjahr = [];

// Alle Tage des vorherigen Jahres initialisieren
for ($d = 0; $d < 365; $d++) {
    $tagVorjahr = date('Y-m-d', strtotime("$vorjahr-01-01 +$d days"));
    $alleTageVorjahr[$tagVorjahr] = 0;
}

// Alle Tage des aktuellen Jahres initialisieren
for ($d = 0; $d < 365; $d++) {
    $tagAktuellesJahr = date('Y-m-d', strtotime("$jahr-01-01 +$d days"));
    $alleTageAktuellesJahr[$tagAktuellesJahr] = 0;
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
$heute = date('Y-m-d');
foreach ($alleTageAktuellesJahr as $tag => $anzahl) {
    if ($tag > $heute) {
        $kumuliertAktuellesJahr[] = null; // Null-Werte für Tage nach heute
    } else {
        $summeAktuellesJahr += $anzahl;
        $kumuliertAktuellesJahr[] = $summeAktuellesJahr;
    }
}

// Einsätze Vorjahr komplett
foreach ($alleTageVorjahr as $anzahl) {
    $summeVorjahr += $anzahl;
    $kumuliertVorjahr[] = $summeVorjahr;
}

// Einsätze Vorjahr bis heute berechnen
$heuteVorjahrDatum = date('Y-m-d', strtotime('-1 year'));
$heute_Vorjahr = 0;
foreach ($alleTageVorjahr as $tag => $anzahl) {
    if ($tag > $heuteVorjahrDatum) {
        break; // Schleife beenden, wenn das Datum nach heute liegt
    } else {
        $heute_Vorjahr += $anzahl;
        $kumuliertheuteVorjahr[] = $heute_Vorjahr;
    }
}

// Entwicklung berechnen
$differenz = $summeAktuellesJahr - $heute_Vorjahr;
$prozentualeVeränderung = $heute_Vorjahr > 0 ? round(($differenz / $heute_Vorjahr) * 100, 1) : 0;
$farbe = $differenz >= 0 ? "green" : "red";

function linearRegression($x, $y) {
    $n = count($x);
    $sumX = array_sum($x);
    $sumY = array_sum($y);
    $sumXY = 0;
    $sumX2 = 0;

    for ($i = 0; $i < $n; $i++) {
        $sumXY += $x[$i] * $y[$i];
        $sumX2 += $x[$i] * $x[$i];
    }

    // Steigung (m) und y-Achsenabschnitt (b) berechnen
    $m = ($n * $sumXY - $sumX * $sumY) / ($n * $sumX2 - $sumX * $sumX);
    //$b = ($sumY - $m * $sumX) / $n;
    $b = 0;
    return ['m' => $m, 'b' => $b];
}

// Entferne Null-Werte aus den kumulierten Einsätzen (Null-Werte nach heute)
$kumuliertBisHeute = array_filter($kumuliertAktuellesJahr, function ($v) {
    return $v !== null;
});

// X-Werte (Tage) und Y-Werte (kumulierte Einsätze)
$x = range(1, count($kumuliertBisHeute)); // Tage als numerische Werte
$y = array_values($kumuliertBisHeute); // Kumulierte Einsätze

// Lineare Regression berechnen
$regression = linearRegression($x, $y);
$m = $regression['m']; // Steigung
$b = $regression['b']; // y-Achsenabschnitt

// Prognose für das gesamte Jahr berechnen
$prognoseAktuellesJahr = [];
for ($i = 1; $i <= 365; $i++) {
    $prognoseAktuellesJahr[] = $m * $i + $b;
}

// Labels für die X-Achse (alle Tage)
$tageAktuellesJahr = array_keys($alleTageAktuellesJahr);
$tageVorjahr = array_keys($alleTageVorjahr);


// Funktion zur monatlichen Aggregation der Daten
function aggregateMonthly($data, $year) {
    $monthlyData = array_fill(1, 12, 0); // Array für 12 Monate, initialisiert mit 0

    foreach ($data as $tag => $anzahl) {
        $date = new DateTime($tag);
        if ($date->format('Y') == $year) {
            $month = (int)$date->format('n'); // Monat als Zahl (1-12)
            $monthlyData[$month] += $anzahl;
        }
    }

    return $monthlyData;
}

// Monatliche Daten für das aktuelle Jahr und das Vorjahr berechnen
$monthlyAktuellesJahr = aggregateMonthly($alleTageAktuellesJahr, $jahr);
$monthlyVorjahr = aggregateMonthly($alleTageVorjahr, $vorjahr);

// Monatsnamen für die X-Achse
$monthNames = ['Jan', 'Feb', 'Mär', 'Apr', 'Mai', 'Jun', 'Jul', 'Aug', 'Sep', 'Okt', 'Nov', 'Dez'];

// Daten für das Balkendiagramm
$labels = $monthNames; // X-Achsen-Labels
$dataAktuellesJahr = array_values($monthlyAktuellesJahr); // Einsätze aktuelles Jahr
$dataVorjahr = array_values($monthlyVorjahr); // Einsätze Vorjahr

?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Einsatzentwicklung</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-zoom"></script>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header>
        <h1>Einsatzentwicklung</h1>
        <?php include 'parts/menue.php'; ?>
    </header>
    <main>

    <!-- 📊 Entwicklung zum Vorjahr -->
    <section id="box">
        <h2>Entwicklung zum Vorjahr</h2>
            <strong>Dieses Jahr:</strong> <?= $summeAktuellesJahr ?> Einsätze<br>
            <strong>Letztes Jahr bis heute:</strong> <?= $heute_Vorjahr ?> Einsätze<br>
            <strong>Veränderung:</strong> <span style="color: <?= $farbe ?>;">
                <?= ($differenz >= 0 ? "+" : "") . $differenz ?> (<?= ($differenz >= 0 ? "+" : "") . $prozentualeVeränderung ?>%)
            </span>
    </section>

    <section id="box">
        <canvas id="einsatzEntwicklungChart" width="800" height="400"></canvas>
    </section>
    </main>
    <script>

// Daten aus PHP übertragen
const labels = <?= json_encode($labels) ?>; // Monatsnamen
const tageAktuellesJahr = <?= json_encode($tageAktuellesJahr) ?>; // Tägliche Daten (für Liniendiagramm)
const dataAktuellesJahr = <?= json_encode($dataAktuellesJahr) ?>; // Einsätze aktuelles Jahr
const dataVorjahr = <?= json_encode($dataVorjahr) ?>; // Einsätze Vorjahr
const kumuliertAktuellesJahr = <?= json_encode($kumuliertAktuellesJahr) ?>; // Kumulierte Einsätze aktuelles Jahr
const kumuliertVorjahr = <?= json_encode($kumuliertVorjahr) ?>; // Kumulierte Einsätze Vorjahr
const prognoseAktuellesJahr = <?= json_encode($prognoseAktuellesJahr) ?>;

const ctx = document.getElementById('einsatzEntwicklungChart').getContext('2d');

const chart = new Chart(ctx, {
    type: 'bar', // Standardtyp ist Balkendiagramm
    data: {
        labels: labels,
        datasets: [
            {
                label: 'Kumuliert <?= $jahr ?> (Linie)',
                data: kumuliertAktuellesJahr,
                borderColor: 'rgba(255, 99, 132, 1)',
                backgroundColor: 'rgba(255, 99, 132, 0.2)',
                fill: false,
                tension: 0.4,
                type: 'line', // Liniendiagramm
                yAxisID: 'y-left', // Linke Y-Achse
                xAxisID: 'x-line', // Separate X-Achse für das Liniendiagramm
                pointRadius: 0,
                pointHoverRadius: 0,
            },
            {
                label: 'Kumuliert <?= $vorjahr ?> (Linie)',
                data: kumuliertVorjahr,
                borderColor: 'rgba(255, 159, 64, 1)',
                backgroundColor: 'rgba(255, 159, 64, 0.2)',
                fill: false,
                tension: 0.4,
                type: 'line', // Liniendiagramm
                yAxisID: 'y-left', // Linke Y-Achse
                xAxisID: 'x-line', // Separate X-Achse für das Liniendiagramm
                pointRadius: 0,
                pointHoverRadius: 0,
            },
            {
                label: 'Prognose <?= $jahr ?>',
                data: prognoseAktuellesJahr,
                borderColor: 'rgba(255, 159, 64, 1)',
                backgroundColor: 'rgba(255, 159, 64, 0.2)',
                fill: false,
                type: 'line', // Liniendiagramm
                yAxisID: 'y-left', // Linke Y-Achse
                xAxisID: 'x-line', // Separate X-Achse für das Liniendiagramm
                borderDash: [5, 5],
                pointRadius: 0,
                pointHoverRadius: 0,
            },
            {
                label: 'Einsätze <?= $jahr ?> (Balken)',
                data: dataAktuellesJahr,
                backgroundColor: 'rgba(255, 159, 64, 0,6)',
                borderWidth: 1,
                yAxisID: 'y-right', // Rechte Y-Achse
            },
            {
                label: 'Einsätze <?= $vorjahr ?> (Balken)',
                data: dataVorjahr,
                backgroundColor: 'rgba(255, 99, 133, 0.6)',
                borderWidth: 1,
                yAxisID: 'y-right', // Rechte Y-Achse
            },
        ],
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'top',
            },
            tooltip: {
                enabled: true,
                callbacks: {
                    label: function (context) {
                        return `${context.dataset.label}: ${context.raw} Einsätze`;
                    },
                },
            },
        },
        scales: {
            x: {
                title: {
                    display: true,
                    text: 'Monate',
                },
                grid: {
                    display: false,
                },
            },
            'x-line': { // X-Achse für das Liniendiagramm (Tage)
                type: 'category',
                labels: tageAktuellesJahr, // Tägliche Labels
                display: false, // Diese Achse nicht anzeigen
            },
            'y-left': { // Linke Y-Achse
                position: 'left',
                beginAtZero: true,
                title: {
                    display: true,
                    text: 'Kumulierte Einsätze',
                },
                grid: {
                    color: 'rgba(200, 200, 200, 0.3)',
                },
            },
            'y-right': { // Rechte Y-Achse
                position: 'right',
                beginAtZero: true,
                title: {
                    display: true,
                    text: 'Monatliche Einsätze',
                },
                grid: {
                    display: false, // Keine Gitterlinien für die rechte Y-Achse
                },
            },
        },
    },
});
</script>
<script src="parts/session_timeout.js"></script>
</body>
</html>