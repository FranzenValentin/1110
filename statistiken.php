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
    <form method="POST" action="index.php" class="back-form">
        <button type="submit">Zurück</button>
    </form>
</header>

<main>
    <!-- Filter für Monat und Jahr -->
    <section id="filter">
        <h2>Monat auswählen</h2>
        <form method="GET" action="statistiken.php" class="filter-form">
            <label for="startdatum">Startdatum:</label>
            <input type="date" id="startdatum" name="startdatum" value="<?= htmlspecialchars($startdatum) ?>" required>

            <label for="enddatum">Enddatum:</label>
            <input type="date" id="enddatum" name="enddatum" value="<?= htmlspecialchars($enddatum) ?>" required>

            <button type="submit">Anzeigen</button>
        </form>

    </section>

    <section id="einsatz-statistik">
        <h2>Statistiken für den Zeitraum <?= htmlspecialchars($startdatum) ?> bis <?= htmlspecialchars($enddatum) ?></h2>
        <?php
        echo "<p>Gesamtanzahl der Einsätze: <strong>$totalEinsaetze</strong></p>";
        echo "<p>Durchschnittliche Einsatzdauer: <strong>" . round($durchschnittsdauer, 2) . " Minuten</strong></p>";
        ?>
    </section>


            // Gesamtanzahl der Einsätze im gewählten Monat
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


            // Häufigste Stichworte im gewählten Monat
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


            echo "<p>Gesamtanzahl der Einsätze: <strong>$totalEinsaetze</strong></p>";
            echo "<p>Durchschnittliche Einsatzdauer: <strong>" . round($durchschnittsdauer, 2) . " Minuten</strong></p>";
        } catch (PDOException $e) {
            echo "<p>Fehler beim Laden der Daten: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
        ?>
    </section>

    <!-- Diagramm für häufigste Stichworte -->
    <section id="haeufigste-stichworte">
        <h2>Häufigste Stichworte</h2>
        <canvas id="stichwortChart" width="200" height="100"></canvas>
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
</body>
</html>
