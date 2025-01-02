<?php
session_start();
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header('Location: login.php');
    exit;
}
require 'db.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Standardwerte für den Zeitraum
$currentDate = new DateTime();
$startdatum = $_GET['startdatum'] ?? $currentDate->format('Y-m-01');
$enddatum = $_GET['enddatum'] ?? $currentDate->format('Y-m-t');
$personId = $_GET['person_id'] ?? null;

// Variablen initialisieren
$personal = [];
$einsaetze = [];
$funktionenVerteilung = [];
$kategorien = [];
$gesamtAnzahl = 0;

// Personal laden
try {
    $personalStmt = $pdo->query("SELECT id, CONCAT(vorname, ' ', nachname) AS name FROM Personal ORDER BY nachname");
    $personal = $personalStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Fehler beim Laden des Personals: " . $e->getMessage());
}

// Abfragen nur bei ausgewählter Person ausführen
if ($personId) {
    try {
        // Einsätze laden
        $einsaetzeStmt = $pdo->prepare("
            SELECT e.interne_einsatznummer, e.stichwort, e.alarmuhrzeit, e.fahrzeug_name,
            CASE
                WHEN b.stf_id = :personId THEN 'Staffel-Führer'
                WHEN b.ma_id = :personId THEN 'Maschinist'
                WHEN b.atf_id = :personId OR b.atm_id = :personId THEN 'Angriffstrupp'
                WHEN b.wtf_id = :personId OR b.wtm_id = :personId THEN 'Wassertrupp'
                WHEN b.prakt_id = :personId THEN 'Praktikant'
                ELSE 'Unbekannt'
            END AS funktion
            FROM Einsaetze e
            LEFT JOIN Besatzung b ON e.besatzung_id = b.id
            WHERE :personId IN (b.stf_id, b.ma_id, b.atf_id, b.atm_id, b.wtf_id, b.wtm_id, b.prakt_id)
              AND e.alarmuhrzeit BETWEEN :startdatum AND :enddatum
            ORDER BY e.alarmuhrzeit DESC
        ");
        $einsaetzeStmt->execute([':personId' => $personId, ':startdatum' => $startdatum, ':enddatum' => $enddatum]);
        $einsaetze = $einsaetzeStmt->fetchAll(PDO::FETCH_ASSOC);

        // Funktionenverteilung laden
        $funktionenStmt = $pdo->prepare("
            SELECT CASE
                WHEN b.stf_id = :personId THEN 'Staffel-Führer'
                WHEN b.ma_id = :personId THEN 'Maschinist'
                WHEN b.atf_id = :personId OR b.atm_id = :personId THEN 'Angriffstrupp'
                WHEN b.wtf_id = :personId OR b.wtm_id = :personId THEN 'Wassertrupp'
                WHEN b.prakt_id = :personId THEN 'Praktikant'
                ELSE 'Unbekannt'
            END AS funktion, COUNT(*) AS anzahl
            FROM Einsaetze e
            LEFT JOIN Besatzung b ON e.besatzung_id = b.id
            WHERE :personId IN (b.stf_id, b.ma_id, b.atf_id, b.atm_id, b.wtf_id, b.wtm_id, b.prakt_id)
              AND e.alarmuhrzeit BETWEEN :startdatum AND :enddatum
            GROUP BY funktion
        ");
        $funktionenStmt->execute([':personId' => $personId, ':startdatum' => $startdatum, ':enddatum' => $enddatum]);
        $funktionenVerteilung = $funktionenStmt->fetchAll(PDO::FETCH_ASSOC);

        // Kategorien laden
        $kategorienStmt = $pdo->prepare("
            SELECT e.stichwort, COUNT(*) AS anzahl
            FROM Einsaetze e
            LEFT JOIN Besatzung b ON e.besatzung_id = b.id
            WHERE :personId IN (b.stf_id, b.ma_id, b.atf_id, b.atm_id, b.wtf_id, b.wtm_id, b.prakt_id)
              AND e.alarmuhrzeit BETWEEN :startdatum AND :enddatum
            GROUP BY e.stichwort
        ");
        $kategorienStmt->execute([':personId' => $personId, ':startdatum' => $startdatum, ':enddatum' => $enddatum]);
        $kategorien = $kategorienStmt->fetchAll(PDO::FETCH_ASSOC);

        // Gesamtanzahl laden
        $gesamtAnzahlStmt = $pdo->prepare("
            SELECT COUNT(*) AS gesamtanzahl
            FROM Einsaetze e
            LEFT JOIN Besatzung b ON e.besatzung_id = b.id
            WHERE :personId IN (b.stf_id, b.ma_id, b.atf_id, b.atm_id, b.wtf_id, b.wtm_id, b.prakt_id)
              AND e.alarmuhrzeit BETWEEN :startdatum AND :enddatum
        ");
        $gesamtAnzahlStmt->execute([':personId' => $personId, ':startdatum' => $startdatum, ':enddatum' => $enddatum]);
        $gesamtAnzahl = $gesamtAnzahlStmt->fetchColumn();
    } catch (PDOException $e) {
        die("Fehler bei den Abfragen: " . $e->getMessage());
    }
}

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistiken - Personal</title>
    <link rel="stylesheet" href="styles.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script> <!-- Für Diagramme -->
</head>
<body>
<header>
    <h1>
            <?php if ($personId): ?>
                Statistik von <?= htmlspecialchars(array_column($personal, 'name', 'id')[$personId]) ?> 
            <?php else: ?>
                Statistiken für Personal
            <?php endif; ?>
    </h1>
    <form method="POST" action="logout.php" class="logout-form">
        <button type="submit">Logout</button>
    </form>
    <form method="POST" action="index.php" class="back-form">
        <button type="submit">Zurück</button>
    </form>
</header>

<main>
    <!-- Filter für Person und Zeitraum -->
    <section id="filter">
        <h2>Filter</h2>
        <form method="GET" action="statistiken_personal.php" class="filter-form">
            <label for="person_id">Person:</label>
            <select id="person_id" name="person_id" required>
                <option value="">-- Wähle eine Person --</option>
                <?php foreach ($personal as $person): ?>
                    <option value="<?= htmlspecialchars($person['id']) ?>" <?= $personId == $person['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($person['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="startdatum">Startdatum:</label>
            <input type="date" id="startdatum" name="startdatum" value="<?= $startdatum ?>" required>

            <label for="enddatum">Enddatum:</label>
            <input type="date" id="enddatum" name="enddatum" value="<?= $enddatum ?>" required>

            <button type="submit">Anzeigen</button>
        </form>
    </section>


    <!-- Verteilung der Funktionen -->
    <section id="funktionen-verteilung">
        <h2>
            <?php if ($personId): ?>
                Funktionen von <?= htmlspecialchars(array_column($personal, 'name', 'id')[$personId]) ?> 
            <?php endif; ?>
        </h2>
        <?php if (count($funktionenVerteilung) > 0): ?>
            <canvas id="funktionenChart" width="400" height="200"></canvas>
            <script>
                const funktionenLabels = <?= json_encode(array_column($funktionenVerteilung, 'funktion')) ?>;
                const funktionenData = <?= json_encode(array_column($funktionenVerteilung, 'anzahl')) ?>;

                new Chart(document.getElementById('funktionenChart'), {
                    type: 'bar',
                    data: {
                        labels: funktionenLabels,
                        datasets: [{
                            label: 'Anzahl der Einsätze',
                            data: funktionenData,
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
        <?php else: ?>
                <p>Keine Funktionen für diesen Zeitraum gefunden.</p>
            <?php endif; ?>
    </section>

    <section id="einsatz-statistik">
        <h2>
            <?php if ($personId): ?>
                Einsätze von <?= htmlspecialchars(array_column($personal, 'name', 'id')[$personId]) ?> 
            <?php endif; ?>
        </h2>
        <?php if ($personId): ?>
            <?php if (count($einsaetze) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Interne Einsatznummer</th>
                            <th>Stichwort</th>
                            <th>Alarmzeit</th>
                            <th>Fahrzeug</th>
                            <th>Funktion</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($einsaetze as $einsatz): ?>
                            <tr>
                                <td><?= htmlspecialchars($einsatz['interne_einsatznummer']) ?></td>
                                <td><?= htmlspecialchars($einsatz['stichwort']) ?></td>
                                <td><?= htmlspecialchars($einsatz['alarmuhrzeit']) ?></td>
                                <td><?= htmlspecialchars($einsatz['fahrzeug_name']) ?></td>
                                <td><?= htmlspecialchars($einsatz['funktion']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <!-- Anzeige der Gesamtanzahl -->
                <p>Gesamtanzahl der Einsätze: <strong><?= htmlspecialchars($gesamtAnzahl) ?></strong></p>
                <!-- Anzeige der Einsätze nach Kategorien -->
                <h4>Einsätze nach Kategorien:</h4>
                <ul>
                    <?php foreach ($kategorien as $kategorie): ?>
                        <li><?= htmlspecialchars($kategorie['stichwort']) ?>: <?= htmlspecialchars($kategorie['anzahl']) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p>Keine Einsätze für diesen Zeitraum gefunden.</p>
            <?php endif; ?>
    </section>



</main>

</body>
</html>