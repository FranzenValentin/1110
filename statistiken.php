<?php
session_start();
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header('Location: login.php'); // Weiterleitung zur Login-Seite
    exit;
}
require 'db.php';

// Standardwerte für Monat und Jahr (aktueller Monat)
$monat = isset($_GET['monat']) ? $_GET['monat'] : date('m');
$jahr = isset($_GET['jahr']) ? $_GET['jahr'] : date('Y');
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
</header>

<main>
    <!-- Filter für Monat und Jahr -->
    <section id="filter">
        <h2>Monat auswählen</h2>
        <form method="GET" action="statistiken.php" class="filter-form">
            <label for="monat">Monat:</label>
            <select id="monat" name="monat" required>
                <?php
                $monate = [
                    '01' => 'Januar', '02' => 'Februar', '03' => 'März',
                    '04' => 'April', '05' => 'Mai', '06' => 'Juni',
                    '07' => 'Juli', '08' => 'August', '09' => 'September',
                    '10' => 'Oktober', '11' => 'November', '12' => 'Dezember'
                ];
                foreach ($monate as $key => $name) {
                    echo "<option value='$key' " . ($monat == $key ? 'selected' : '') . ">$name</option>";
                }
                ?>
            </select>

            <label for="jahr">Jahr:</label>
            <input type="number" id="jahr" name="jahr" value="<?= $jahr ?>" required>

            <button type="submit">Anzeigen</button>
        </form>
    </section>

    <section id="einsatz-statistik">
        <h2>Statistiken für <?= $monate[$monat] . " $jahr" ?></h2>
        <?php
        try {
            // Gesamtanzahl der Einsätze im gewählten Monat
            $totalStmt = $pdo->prepare("
                SELECT COUNT(*) AS total 
                FROM Einsaetze 
                WHERE MONTH(STR_TO_DATE(alarmuhrzeit, '%d.%m.%y %H:%i')) = :monat 
                  AND YEAR(STR_TO_DATE(alarmuhrzeit, '%d.%m.%y %H:%i')) = :jahr
            ");
            $totalStmt->execute([':monat' => $monat, ':jahr' => $jahr]);
            $totalEinsaetze = $totalStmt->fetch()['total'];

            // Anzahl der Einsätze nach Wochentag
            $wochentagStmt = $pdo->prepare("
                SELECT DAYNAME(STR_TO_DATE(alarmuhrzeit, '%d.%m.%y %H:%i')) AS wochentag, COUNT(*) AS anzahl
                FROM Einsaetze
                WHERE MONTH(STR_TO_DATE(alarmuhrzeit, '%d.%m.%y %H:%i')) = :monat 
                  AND YEAR(STR_TO_DATE(alarmuhrzeit, '%d.%m.%y %H:%i')) = :jahr
                GROUP BY wochentag
                ORDER BY FIELD(DAYNAME(STR_TO_DATE(alarmuhrzeit, '%d.%m.%y %H:%i')), 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday')
            ");
            $wochentagStmt->execute([':monat' => $monat, ':jahr' => $jahr]);
            $wochentage = $wochentagStmt->fetchAll(PDO::FETCH_ASSOC);

            // Durchschnittliche Dauer eines Einsatzes
            $dauerStmt = $pdo->prepare("
                SELECT AVG(TIMESTAMPDIFF(MINUTE, STR_TO_DATE(alarmuhrzeit, '%d.%m.%y %H:%i'), STR_TO_DATE(zurueckzeit, '%d.%m.%y %H:%i'))) AS durchschnittsdauer
                FROM Einsaetze
                WHERE MONTH(STR_TO_DATE(alarmuhrzeit, '%d.%m.%y %H:%i')) = :monat 
                  AND YEAR(STR_TO_DATE(alarmuhrzeit, '%d.%m.%y %H:%i')) = :jahr
            ");
            $dauerStmt->execute([':monat' => $monat, ':jahr' => $jahr]);
            $durchschnittsdauer = $dauerStmt->fetch()['durchschnittsdauer'];

            echo "<p>Gesamtanzahl der Einsätze: <strong>$totalEinsaetze</strong></p>";
            echo "<p>Durchschnittliche Einsatzdauer: <strong>" . round($durchschnittsdauer, 2) . " Minuten</strong></p>";
        } catch (PDOException $e) {
            echo "<p>Fehler beim Laden der Daten: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
        ?>
    </section>

    <!-- Diagramm für Einsätze nach Wochentag -->
    <section id="wochentage">
        <h2>Einsätze nach Wochentag</h2>
        <canvas id="wochentagChart" width="400" height="200"></canvas>
        <script>
            const wochentagLabels = <?= json_encode(array_column($wochentage, 'wochentag')) ?>;
            const wochentagData = <?= json_encode(array_column($wochentage, 'anzahl')) ?>;

            new Chart(document.getElementById('wochentagChart'), {
                type: 'bar',
                data: {
                    labels: wochentagLabels,
                    datasets: [{
                        label: 'Einsätze',
                        data: wochentagData,
                        backgroundColor: 'rgba(75, 192, 192, 0.5)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: { beginAtZero: true }
                    }
                }
            });
        </script>
    </section>

    <!-- Diagramm für häufigste Stichworte -->
    <section id="haeufigste-stichworte">
        <h2>Häufigste Stichworte</h2>
        <canvas id="stichwortChart" width="400" height="200"></canvas>
        <script>
            const stichwortLabels = <?= json_encode(array_column($stichworte, 'stichwort')) ?>;
            const stichwortData = <?= json_encode(array_column($stichworte, 'anzahl')) ?>;

            new Chart(document.getElementById('stichwortChart'), {
                type: 'pie',
                data: {
                    labels: stichwortLabels,
                    datasets: [{
                        label: 'Stichworte',
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
                    responsive: true
                }
            });
        </script>
    </section>
</main>

<footer>
    <p>&copy; 2025 Einsatzverwaltung. Alle Rechte vorbehalten.</p>
</footer>
</body>
</html>
