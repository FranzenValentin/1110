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

function holtWinters($data, $alpha, $beta, $gamma, $seasonLength, $forecastPeriods) {
    $n = count($data);
    if ($n < $seasonLength * 2) {
        return []; // Nicht genug Daten für Saisonalität
    }

    // Initialisierung
    $level = [];
    $trend = [];
    $seasonal = [];
    $forecast = [];

    // Initiale Werte für Level, Trend und Saisonalität
    $level[0] = $data[0];
    $trend[0] = 0;

    for ($i = 0; $i < $seasonLength; $i++) {
        $seasonal[$i] = $data[$i] - $level[0];
    }

    // Berechnung der geglätteten Werte
    for ($i = 1; $i < $n; $i++) {
        $level[$i] = $alpha * ($data[$i] - $seasonal[$i % $seasonLength]) + (1 - $alpha) * ($level[$i - 1] + $trend[$i - 1]);
        $trend[$i] = $beta * ($level[$i] - $level[$i - 1]) + (1 - $beta) * $trend[$i - 1];
        $seasonal[$i % $seasonLength] = $gamma * ($data[$i] - $level[$i]) + (1 - $gamma) * $seasonal[$i % $seasonLength];
    }

    // Prognose für zukünftige Perioden
    for ($i = $n; $i < $n + $forecastPeriods; $i++) {
        $forecast[$i - $n] = $level[$n - 1] + $trend[$n - 1] * ($i - $n + 1) + $seasonal[$i % $seasonLength];
    }

    return $forecast;
}

// Entferne Null-Werte aus den kumulierten Einsätzen (Null-Werte nach heute)
$kumuliertBisHeute = array_filter($kumuliertAktuellesJahr, function ($v) {
    return $v !== null;
});

// Parameter für die Holt-Winters-Prognose
$alpha = 0.3; // Erhöht die Reaktionsgeschwindigkeit auf Veränderungen (standard: 0.2)
$beta = 0.15; // Leichter erhöhter Trend-Anteil (standard: 0.1)
$gamma = 0.2; // Saisonale Effekte stärker betonen (standard: 0.1)
$seasonLength = 30; // Monatliche Zyklen statt 12 Monate (besser für tägliche Daten)
$forecastPeriods = 90; // Kürzere Vorhersage (3 Monate statt 1 Jahr)

// Sicherstellen, dass genug Daten für die Prognose vorhanden sind
if (count($kumuliertBisHeute) < $seasonLength * 2) {
    $prognoseAktuellesJahr = array_fill(0, 365, null); // Keine Prognose möglich
} else {
    // Holt-Winters-Prognose berechnen
    $prognoseAktuellesJahr = holtWinters(array_values($kumuliertBisHeute), $alpha, $beta, $gamma, $seasonLength, $forecastPeriods);

    // Sicherstellen, dass die Prognose 365 Werte hat
    $prognoseAktuellesJahr = array_merge($kumuliertBisHeute, $prognoseAktuellesJahr);
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

// Debugging: Prüfen, ob die Daten aus PHP korrekt übernommen wurden
console.log("Tage Aktuelles Jahr:", <?= json_encode($tageAktuellesJahr) ?>);
console.log("Kumulierte Einsätze Aktuelles Jahr:", <?= json_encode($kumuliertAktuellesJahr) ?>);
console.log("Prognose Einsätze Aktuelles Jahr:", <?= json_encode($prognoseAktuellesJahr) ?>);

// Daten aus PHP übertragen
const tageAktuellesJahr = <?= json_encode($tageAktuellesJahr) ?>;
const kumuliertAktuellesJahr = <?= json_encode($kumuliertAktuellesJahr) ?>;
const tageVorjahr = <?= json_encode($tageVorjahr) ?>;
const kumuliertVorjahr = <?= json_encode($kumuliertVorjahr) ?>;
const prognoseAktuellesJahr = <?= json_encode($prognoseAktuellesJahr) ?>;

// Prüfen, ob Prognose-Daten korrekt geladen wurden
if (!prognoseAktuellesJahr || prognoseAktuellesJahr.length === 0) {
    console.warn("⚠️ WARNUNG: Keine Prognose-Werte erhalten!");
}

// Aktuelles Datum von PHP
const aktuellesDatum = new Date(<?= json_encode(date('Y-m-d')) ?>);
const aktuellesDatumIndex = tageAktuellesJahr.indexOf(aktuellesDatum.toISOString().split('T')[0]);

const ctx = document.getElementById('einsatzEntwicklungChart').getContext('2d');


function createGradient(ctx, x, y, radius) {
    const gradient = ctx.createRadialGradient(x, y, radius * 0.1, x, y, radius);
    gradient.addColorStop(0, 'rgba(255, 99, 132, 1)');
    gradient.addColorStop(1, 'rgba(255, 99, 132, 0)');
    return gradient;
}

const chart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: tageAktuellesJahr,
        datasets: [
            {
                label: 'Kumuliert <?= $jahr ?>',
                data: kumuliertAktuellesJahr,
                borderColor: (context) => {
                    const index = context.dataIndex;
                    return kumuliertAktuellesJahr[index] > kumuliertAktuellesJahr[index - 1] ? 'rgba(75, 192, 192, 1)' : 'rgba(255, 99, 132, 1)';
                },
                backgroundColor: 'rgba(255, 99, 132, 0.2)',
                fill: false,
                tension: 0.4,
                pointRadius: (context) => {
                    const index = context.dataIndex;
                    return index === aktuellesDatumIndex ? 10 : 0;
                },
                pointHoverRadius: 10,
                pointBackgroundColor: (context) => {
                    const index = context.dataIndex;
                    if (index === aktuellesDatumIndex) {
                        const x = context.chart.scales.x.getPixelForValue(tageAktuellesJahr[index]);
                        const y = context.chart.scales.y.getPixelForValue(kumuliertAktuellesJahr[index]);
                        return createGradient(ctx, x, y, 10);
                    }
                    return 'rgba(255, 99, 132, 1)';
                },
                pointBorderColor: 'rgba(255, 99, 132, 0)',
                pointBorderWidth: (context) => {
                    const index = context.dataIndex;
                    return index === aktuellesDatumIndex ? 3 : 1;
                },
            },
            {
                label: 'Kumuliert <?= $vorjahr ?>',
                data: kumuliertVorjahr,
                borderColor: 'rgba(54, 162, 235, 1)',
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                fill: false,
                tension: 0.4,
                pointRadius: 0,
                pointHoverRadius: 0,
            },
            {
                label: 'Prognose <?= $jahr ?>',
                data: prognoseAktuellesJahr,
                borderColor: 'rgba(255, 159, 64, 1)',
                backgroundColor: 'rgba(255, 159, 64, 0.2)',
                fill: false,
                tension: 0.4,
                borderDash: [5, 5],
                pointRadius: 0,
                pointHoverRadius: 0,
            },
        ],
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'top',
                onClick: (e, legendItem) => {
                    const index = legendItem.datasetIndex;
                    const meta = chart.getDatasetMeta(index);
                    meta.hidden = !meta.hidden;
                    chart.update();
                },
            },
            tooltip: {
                enabled: true,
                callbacks: {
                    label: function (context) {
                        const total = kumuliertAktuellesJahr[context.dataIndex];
                        const previousTotal = kumuliertVorjahr[context.dataIndex] || 0;
                        const diff = total - previousTotal;
                        const percentChange = previousTotal > 0 ? ((diff / previousTotal) * 100).toFixed(1) : 0;
                        return [
                            `${context.dataset.label}: ${context.raw} Einsätze`,
                            `Differenz zum Vorjahr: ${diff} (${percentChange}%)`,
                        ];
                    },
                },
            },
        },
        scales: {
            x: {
                type: 'category',
                title: {
                    display: true,
                    text: 'Monate',
                },
                ticks: {
                    callback: function (value, index) {
                        const date = new Date(tageAktuellesJahr[index]);
                        const monthNames = ['Jan', 'Feb', 'Mär', 'Apr', 'Mai', 'Jun', 'Jul', 'Aug', 'Sep', 'Okt', 'Nov', 'Dez'];
                        return monthNames[date.getMonth()];
                    },
                    maxTicksLimit: 12,
                    autoSkip: true,
                },
                grid: {
                    display: true,
                },
            },
            y: {
                beginAtZero: true,
                title: {
                    display: true,
                    text: 'Kumulierte Einsätze',
                },
                grid: {
                    color: 'rgba(200, 200, 200, 0.3)',
                },
            },
        },
        animations: {
            radius: {
                duration: 1500,
                easing: 'easeIn',
                loop: true,
                from: (context) => {
                    const index = context.dataIndex;
                    return index === aktuellesDatumIndex ? 3 : 0;
                },
                to: (context) => {
                    const index = context.dataIndex;
                    return index === aktuellesDatumIndex ? 10 : 0;
                },
            },
        },
    },
});
</script>
<script src="parts/session_timeout.js"></script>
</body>
</html>