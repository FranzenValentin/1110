<?php
session_start();
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header('Location: login.php'); // Weiterleitung zur Login-Seite
    exit;
}
require 'db.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Standardwerte für Start- und Enddatum
if (!isset($_GET['startdatum']) || !isset($_GET['enddatum'])) {
    $currentDate = new DateTime();
    $startdatum = $currentDate->format('Y-m-01'); // Erster Tag des aktuellen Monats
    $enddatum = $currentDate->format('Y-m-t');   // Letzter Tag des aktuellen Monats
} else {
    $startdatum = $_GET['startdatum'];
    $enddatum = $_GET['enddatum'];
}

try {
    // Gesamtanzahl der Einsätze im gewählten Zeitraum
    $totalStmt = $pdo->prepare("
        SELECT COUNT(*) AS total 
        FROM Einsaetze 
        WHERE STR_TO_DATE(alarmuhrzeit, '%d.%m.%y %H:%i') BETWEEN :startdatum AND :enddatum
    ");
    $totalStmt->execute([':startdatum' => $startdatum, ':enddatum' => $enddatum]);
    $totalEinsaetze = $totalStmt->fetch()['total'];

    // Durchschnittliche Dauer eines Einsatzes
    $dauerStmt = $pdo->prepare("
        SELECT AVG(TIMESTAMPDIFF(MINUTE, STR_TO_DATE(alarmuhrzeit, '%d.%m.%y %H:%i'), STR_TO_DATE(zurueckzeit, '%d.%m.%y %H:%i'))) AS durchschnittsdauer
        FROM Einsaetze
        WHERE STR_TO_DATE(alarmuhrzeit, '%d.%m.%y %H:%i') BETWEEN :startdatum AND :enddatum
    ");
    $dauerStmt->execute([':startdatum' => $startdatum, ':enddatum' => $enddatum]);
    $durchschnittsdauer = $dauerStmt->fetch()['durchschnittsdauer'];

    // Häufigste Stichworte im gewählten Zeitraum
    $stichwortStmt = $pdo->prepare("
        SELECT stichwort, COUNT(*) AS anzahl 
        FROM Einsaetze 
        WHERE STR_TO_DATE(alarmuhrzeit, '%d.%m.%y %H:%i') BETWEEN :startdatum AND :enddatum
        GROUP BY stichwort 
        ORDER BY anzahl DESC
        LIMIT 5
    ");
    $stichwortStmt->execute([':startdatum' => $startdatum, ':enddatum' => $enddatum]);
    $stichworte = $stichwortStmt->fetchAll(PDO::FETCH_ASSOC);
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
    <link rel="stylesheet" href="styles.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script> <!-- Für Diagramme -->
</head>
<body>
<header>
    <h1>Einsatzstatistiken</h1>
    <form method="POST" action="logout.php" class="logout-form">
        <button type="submit">Logout</button>
    </form>
    <form method="POST" action="index.php" class="back-form">
        <button type="submit">Zurück</button>
    </form>
</header>

<main>
    <!-- Filter für Start- und Enddatum -->
    <section id="filter">
        <h2>Zeitraum auswählen</h2>
        <form method="GET" action="statistiken.php" class="filter-form">
            <label for="startdatum">Startdatum:</label>
            <input type="date" id="startdatum" name="startdatum" value="<?= htmlspecialchars($startdatum) ?>" required>

            <label for="enddatum">Enddatum:</label>
            <input type="date" id="enddatum" name="enddatum" value="<?= htmlspecialchars($enddatum) ?>" required>

            <button type="submit">Anzeigen</button>
        </form>
    </section>

    <!-- Anzeige der Statistiken -->
    <section id="einsatz-statistik">
    <h2>Statistiken für den Zeitraum 
        <?= htmlspecialchars(DateTime::createFromFormat('Y-m-d', $startdatum)->format('d.m.Y')) ?> 
        bis 
        <?= htmlspecialchars(DateTime::createFromFormat('Y-m-d', $enddatum)->format('d.m.Y')) ?>
    </h2>
    <?php if (isset($error)): ?>
        <p><?= htmlspecialchars($error) ?></p>
    <?php else: ?>
        <p>Gesamtanzahl der Einsätze: <strong><?= htmlspecialchars($totalEinsaetze) ?></strong></p>

        <?php if ($totalEinsaetze != 0): ?>
            <p>Durchschnittliche Einsatzdauer: <strong><?= htmlspecialchars(round($durchschnittsdauer, 2)) ?> Minuten</strong></p>
        <?php endif; ?>
    <?php endif; ?>

    </section>

    <!-- Diagramm für häufigste Stichworte -->
    <section id="haeufigste-stichworte">
        <?php if ($totalEinsaetze != 0): ?>
        <h2>Häufigste Stichworte</h2>    
        <?php endif; ?>
        
        <canvas id="stichwortChart" ></canvas>
<script>
    const stichwortLabels = <?= json_encode(array_column($stichworte, 'stichwort')) ?>;
    const stichwortData = <?= json_encode(array_column($stichworte, 'anzahl')) ?>;

    new Chart(document.getElementById('stichwortChart'), {
        type: 'bar', // Typ: Balkendiagramm
        data: {
            labels: stichwortLabels,
            datasets: [{
                label: 'Häufigkeit der Stichworte',
                data: stichwortData,
                backgroundColor: [
                    'rgba(255, 99, 132, 0.5)',
                    'rgba(54, 162, 235, 0.5)',
                    'rgba(255, 206, 86, 0.5)',
                    'rgba(75, 192, 192, 0.5)',
                    'rgba(153, 102, 255, 0.5)'
                ],
                borderColor: [
                    'rgba(255, 99, 132, 1)',
                    'rgba(54, 162, 235, 1)',
                    'rgba(255, 206, 86, 1)',
                    'rgba(75, 192, 192, 1)',
                    'rgba(153, 102, 255, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true, // Seitenverhältnis beibehalten
            scales: {
                x: { // Einstellungen für die x-Achse
                    title: {
                        display: true,
                        text: 'Stichworte'
                    }
                },
                y: { // Einstellungen für die y-Achse
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1 // Nur ganze Zahlen anzeigen
                    },
                    title: {
                        display: true,
                        text: 'Anzahl'
                    }
                }
            }
        }
    });
</script>



    </section>
</main>
</body>
</html>
