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
                <?php for ($i = 1; $i <= 12; $i++): ?>
                    <option value="<?= str_pad($i, 2, '0', STR_PAD_LEFT) ?>" <?= $monat == $i ? 'selected' : '' ?>>
                        <?= date('F', mktime(0, 0, 0, $i, 1)) ?>
                    </option>
                <?php endfor; ?>
            </select>

            <label for="jahr">Jahr:</label>
            <input type="number" id="jahr" name="jahr" value="<?= $jahr ?>" required>

            <button type="submit">Anzeigen</button>
        </form>
    </section>

    <section id="einsatz-statistik">
        <h2>Statistiken für <?= date('F Y', mktime(0, 0, 0, $monat, 1, $jahr)) ?></h2>
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

            // Meist genutzte Fahrzeuge im gewählten Monat
            $fahrzeugStmt = $pdo->prepare("
                SELECT fahrzeug_name, COUNT(*) AS anzahl 
                FROM Einsaetze 
                WHERE MONTH(STR_TO_DATE(alarmuhrzeit, '%d.%m.%y %H:%i')) = :monat 
                  AND YEAR(STR_TO_DATE(alarmuhrzeit, '%d.%m.%y %H:%i')) = :jahr
                GROUP BY fahrzeug_name 
                ORDER BY anzahl DESC
                LIMIT 5
            ");
            $fahrzeugStmt->execute([':monat' => $monat, ':jahr' => $jahr]);
            $fahrzeuge = $fahrzeugStmt->fetchAll(PDO::FETCH_ASSOC);

            // Häufigste Stichworte im gewählten Monat
            $stichwortStmt = $pdo->prepare("
                SELECT stichwort, COUNT(*) AS anzahl 
                FROM Einsaetze 
                WHERE MONTH(STR_TO_DATE(alarmuhrzeit, '%d.%m.%y %H:%i')) = :monat 
                  AND YEAR(STR_TO_DATE(alarmuhrzeit, '%d.%m.%y %H:%i')) = :jahr
                GROUP BY stichwort 
                ORDER BY anzahl DESC
                LIMIT 5
            ");
            $stichwortStmt->execute([':monat' => $monat, ':jahr' => $jahr]);
            $stichworte = $stichwortStmt->fetchAll(PDO::FETCH_ASSOC);

            echo "<p>Gesamtanzahl der Einsätze: <strong>$totalEinsaetze</strong></p>";
        } catch (PDOException $e) {
            echo "<p>Fehler beim Laden der Daten: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
        ?>
    </section>

    <!-- Diagramm für Fahrzeuge -->
    <section id="meist-genutzte-fahrzeuge">
        <h2>Meist genutzte Fahrzeuge</h2>
        <canvas id="fahrzeugChart" width="400" height="200"></canvas>
        <script>
            const fahrzeugLabels = <?= json_encode(array_column($fahrzeuge, 'fahrzeug_name')) ?>;
            const fahrzeugData = <?= json_encode(array_column($fahrzeuge, 'anzahl')) ?>;

            new Chart(document.getElementById('fahrzeugChart'), {
                type: 'bar',
                data: {
                    labels: fahrzeugLabels,
                    datasets: [{
                        label: 'Einsätze',
                        data: fahrzeugData,
                        backgroundColor: 'rgba(54, 162, 235, 0.5)',
                        borderColor: 'rgba(54, 162, 235, 1)',
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

    <!-- Diagramm für Stichworte -->
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
