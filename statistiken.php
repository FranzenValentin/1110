<?php
session_start();
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header('Location: login.php'); // Weiterleitung zur Login-Seite
    exit;
}
require 'db.php';
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
    <section id="einsatz-statistik">
        <h2>Gesamtübersicht</h2>
        <?php
        try {
            // Gesamtanzahl der Einsätze
            $totalStmt = $pdo->query("SELECT COUNT(*) AS total FROM Einsaetze");
            $totalEinsaetze = $totalStmt->fetch()['total'];

            // Meist genutzte Fahrzeuge
            $fahrzeugStmt = $pdo->query("
                SELECT fahrzeug_name, COUNT(*) AS anzahl 
                FROM Einsaetze 
                GROUP BY fahrzeug_name 
                ORDER BY anzahl DESC
                LIMIT 5
            ");
            $fahrzeuge = $fahrzeugStmt->fetchAll(PDO::FETCH_ASSOC);

            // Häufigste Stichworte
            $stichwortStmt = $pdo->query("
                SELECT stichwort, COUNT(*) AS anzahl 
                FROM Einsaetze 
                GROUP BY stichwort 
                ORDER BY anzahl DESC
                LIMIT 5
            ");
            $stichworte = $stichwortStmt->fetchAll(PDO::FETCH_ASSOC);

            echo "<p>Gesamtanzahl der Einsätze: <strong>$totalEinsaetze</strong></p>";
        } catch (PDOException $e) {
            echo "<p>Fehler beim Laden der Daten: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
        ?>
    </section>

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
