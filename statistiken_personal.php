<?php
session_start();
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header('Location: login.php'); // Weiterleitung zur Login-Seite
    exit;
}
require 'db.php';

try {
    // Standardwerte für den Zeitraum (aktueller Monat)
    if (!isset($_GET['startdatum']) || !isset($_GET['enddatum'])) {
        $currentDate = new DateTime();
        $startdatum = $currentDate->format('Y-m-01'); // Erster Tag des Monats
        $enddatum = $currentDate->format('Y-m-t');   // Letzter Tag des Monats
    } else {
        $startdatum = $_GET['startdatum'];
        $enddatum = $_GET['enddatum'];
    }
    $personId = isset($_GET['person_id']) ? $_GET['person_id'] : null;

    // Start- und Enddatum in das benötigte Format `%d.%m.%Y %H:%i` umwandeln
    $startdatum = (new DateTime($startdatum))->format('d.m.Y 00:00');
    $enddatum = (new DateTime($enddatum))->format('d.m.Y 23:59');

    // Personal aus der Datenbank abrufen
    $personalStmt = $pdo->query("SELECT id, CONCAT(vorname, ' ', nachname) AS name FROM personal ORDER BY nachname");
    $personal = $personalStmt->fetchAll(PDO::FETCH_ASSOC);


    // Einsätze und Verteilungen nur abrufen, wenn eine Person ausgewählt ist
    $einsaetze = [];
    $funktionenVerteilung = [];
    if ($personId) {
        // Einsätze für die gewählte Person abrufen
        $einsaetzeStmt = $pdo->prepare("
            SELECT 
                e.interne_einsatznummer, e.stichwort, e.alarmuhrzeit, e.fahrzeug_name,
                CASE
                    WHEN b.stf_id = :personId THEN 'Staffel-Führer'
                    WHEN b.ma_id = :personId THEN 'Maschinist'
                    WHEN b.atf_id = :personId THEN 'Angriffstrupp-Führer'
                    WHEN b.atm_id = :personId THEN 'Angriffstrupp-Mann'
                    WHEN b.wtf_id = :personId THEN 'Wassertrupp-Führer'
                    WHEN b.wtm_id = :personId THEN 'Wassertrupp-Mann'
                    WHEN b.prakt_id = :personId THEN 'Praktikant'
                    ELSE 'Unbekannt'
                END AS funktion
            FROM einsaetze e
            LEFT JOIN dienste b ON e.dienst_id = b.id
            WHERE :personId IN (b.stf_id, b.ma_id, b.atf_id, b.atm_id, b.wtf_id, b.wtm_id, b.prakt_id)
            AND STR_TO_DATE(e.alarmuhrzeit, '%d.%m.%Y %H:%i') BETWEEN STR_TO_DATE(:startdatum, '%d.%m.%Y %H:%i') AND STR_TO_DATE(:enddatum, '%d.%m.%Y %H:%i')
            ORDER BY STR_TO_DATE(e.alarmuhrzeit, '%d.%m.%Y %H:%i') DESC
        ");
        $einsaetzeStmt->execute([
            ':personId' => $personId,
            ':startdatum' => $startdatum,
            ':enddatum' => $enddatum
        ]);
        $einsaetze = $einsaetzeStmt->fetchAll(PDO::FETCH_ASSOC);

        // Verteilung der Dienste nach Funktionen abrufen
        $funktionenStmt = $pdo->prepare("
        SELECT 
            CASE
                WHEN b.stf_id = :personId THEN 'Staffel-Führer'
                WHEN b.ma_id = :personId THEN 'Maschinist'
                WHEN b.atf_id = :personId OR b.atm_id = :personId THEN 'Angriffstrupp'
                WHEN b.wtf_id = :personId OR b.wtm_id = :personId THEN 'Wassertrupp'
                WHEN b.prakt_id = :personId THEN 'Praktikant'
                ELSE 'Unbekannt'
            END AS funktion,
            COUNT(*) AS anzahl
        FROM dienste b
        WHERE :personId IN (b.stf_id, b.ma_id, b.atf_id, b.atm_id, b.wtf_id, b.wtm_id, b.prakt_id)
        AND STR_TO_DATE(b.inDienstZeit, '%d.%m.%Y %H:%i') BETWEEN STR_TO_DATE(:startdatum, '%d.%m.%Y %H:%i') AND STR_TO_DATE(:enddatum, '%d.%m.%Y %H:%i')
        GROUP BY funktion
        ");
        $funktionenStmt->execute([
        ':personId' => $personId,
        ':startdatum' => $startdatum,
        ':enddatum' => $enddatum
        ]);
        $funktionenVerteilung = $funktionenStmt->fetchAll(PDO::FETCH_ASSOC);


        // Gesamtanzahl der Einsätze im Zeitraum
        $totalEinsaetzeStmt = $pdo->prepare("
            SELECT COUNT(*) AS total
            FROM einsaetze e
            WHERE STR_TO_DATE(e.alarmuhrzeit, '%d.%m.%Y %H:%i') BETWEEN STR_TO_DATE(:startdatum, '%d.%m.%Y %H:%i') AND STR_TO_DATE(:enddatum, '%d.%m.%Y %H:%i')
        ");
        $totalEinsaetzeStmt->execute([
            ':startdatum' => $startdatum,
            ':enddatum' => $enddatum
        ]);
        $totalEinsaetze = $totalEinsaetzeStmt->fetchColumn();

        // Einsätze der Person im Zeitraum zählen
        $personEinsaetzeStmt = $pdo->prepare("
            SELECT COUNT(*) AS total
            FROM einsaetze e
            LEFT JOIN dienste b ON e.dienst_id = b.id
            WHERE :personId IN (b.stf_id, b.ma_id, b.atf_id, b.atm_id, b.wtf_id, b.wtm_id, b.prakt_id)
            AND STR_TO_DATE(e.alarmuhrzeit, '%d.%m.%Y %H:%i') BETWEEN STR_TO_DATE(:startdatum, '%d.%m.%Y %H:%i') AND STR_TO_DATE(:enddatum, '%d.%m.%Y %H:%i')
        ");
        $personEinsaetzeStmt->execute([
            ':personId' => $personId,
            ':startdatum' => $startdatum,
            ':enddatum' => $enddatum
        ]);
        $personEinsaetze = $personEinsaetzeStmt->fetchColumn();

        // Prozentwert berechnen
        $prozent = $totalEinsaetze > 0 ? round(($personEinsaetze / $totalEinsaetze) * 100, 2) : 0;

        // Gesamtdauer der Einsätze berechnen
        $einsatzdauerStmt = $pdo->prepare("
            SELECT SUM(TIMESTAMPDIFF(MINUTE, 
                STR_TO_DATE(e.alarmuhrzeit, '%d.%m.%Y %H:%i'), 
                STR_TO_DATE(e.zurueckzeit, '%d.%m.%Y %H:%i')
            )) AS gesamtDauer
            FROM einsaetze e
            LEFT JOIN dienste b ON e.dienst_id = b.id
            WHERE :personId IN (b.stf_id, b.ma_id, b.atf_id, b.atm_id, b.wtf_id, b.wtm_id, b.prakt_id)
            AND STR_TO_DATE(e.alarmuhrzeit, '%d.%m.%Y %H:%i') BETWEEN STR_TO_DATE(:startdatum, '%d.%m.%Y %H:%i') AND STR_TO_DATE(:enddatum, '%d.%m.%Y %H:%i')
        ");
        $einsatzdauerStmt->execute([
            ':personId' => $personId,
            ':startdatum' => $startdatum,
            ':enddatum' => $enddatum
        ]);
        $gesamtDauer = $einsatzdauerStmt->fetchColumn();

        // Dauer in Stunden und Minuten umrechnen
        $stunden = floor($gesamtDauer / 60);
        $minuten = $gesamtDauer % 60;
    }
} catch (PDOException $e) {
    die("Datenbankfehler: " . htmlspecialchars($e->getMessage()));
}
?>


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
            <input type="date" id="startdatum" name="startdatum" value="<?= htmlspecialchars((new DateTime($startdatum))->format('Y-m-d')) ?>" required>

            <label for="enddatum">Enddatum:</label>
            <input type="date" id="enddatum" name="enddatum" value="<?= htmlspecialchars((new DateTime($enddatum))->format('Y-m-d')) ?>" required>

            <button type="submit">Anzeigen</button>
        </form>
    </section>

    <!-- Verteilung der Funktionen -->
    <section id="funktionen-verteilung">
        <?php if (count($funktionenVerteilung) > 0): ?>
            <h2>Funktionen von <?= htmlspecialchars(array_column($personal, 'name', 'id')[$personId]) ?></h2>
            <div style="width: 50%; margin: 0 auto;"> <!-- Begrenze die Breite des Diagramms -->
                <canvas id="funktionenChart"></canvas>
            </div>
            <script>
                const funktionenLabels = <?= json_encode(array_column($funktionenVerteilung, 'funktion')) ?>;
                const funktionenData = <?= json_encode(array_column($funktionenVerteilung, 'anzahl')) ?>;

                // Farbzurodnung für die Funktionen
                const farben = {
                    "Angriffstrupp": 'rgba(255, 99, 132, 0.5)', // rot
                    "Wassertrupp": 'rgba(54, 162, 235, 0.5)',  // blau
                    "Staffel-Führer": 'rgba(255, 206, 86, 0.5)', // gelb
                    "Maschinist": 'rgba(153, 102, 255, 0.5)',   // lila
                    "Praktikant": 'rgba(128, 128, 128, 0.5)'    // grau
                };

                const farbenRand = {
                    "Angriffstrupp": 'rgba(255, 99, 132, 1)', 
                    "Wassertrupp": 'rgba(54, 162, 235, 1)',  
                    "Staffel-Führer": 'rgba(255, 206, 86, 1)', 
                    "Maschinist": 'rgba(153, 102, 255, 1)',   
                    "Praktikant": 'rgba(128, 128, 128, 1)'    
                };

                // Farben basierend auf den Labels auswählen
                const backgroundColors = funktionenLabels.map(label => farben[label] || 'rgba(0, 0, 0, 0.5)');
                const borderColors = funktionenLabels.map(label => farbenRand[label] || 'rgba(0, 0, 0, 1)');

                new Chart(document.getElementById('funktionenChart'), {
                    type: 'pie',
                    data: {
                        labels: funktionenLabels,
                        datasets: [{
                            label: 'Anzahl der Dienste',
                            data: funktionenData,
                            backgroundColor: backgroundColors,
                            borderColor: borderColors,
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                position: 'top'
                            },
                            tooltip: {
                                enabled: true
                            }
                        }
                    }
                });
            </script>
        <?php else: ?>
            <p>Keine Daten zur Verteilung der Funktionen verfügbar.</p>
        <?php endif; ?>
    </section>


    <!-- Anzeige der Einsätze -->
    <section id="einsatz-statistik">
        <?php if (count($einsaetze) > 0): ?>
            <h2>Einsätze von <?= htmlspecialchars(array_column($personal, 'name', 'id')[$personId]) ?></h2>
            <p>Von insgesamt <strong><?= htmlspecialchars($totalEinsaetze) ?> Alarmen</strong> war <?= htmlspecialchars(array_column($personal, 'name', 'id')[$personId]) ?>
                bei <strong><?= htmlspecialchars($personEinsaetze) ?> Alarmen</strong> beteiligt. Das entspricht <strong><?= htmlspecialchars($prozent) ?>%</strong>.
                Insgesamt war <?= htmlspecialchars(array_column($personal, 'name', 'id')[$personId]) ?>
                in diesem Zeitraum <strong><?= htmlspecialchars($stunden) ?> Stunden und <?= htmlspecialchars($minuten) ?> Minuten</strong> im Einsatz.</p>

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
        <?php else: ?>
            <p>Keine Einsätze für diesen Zeitraum gefunden.</p>
        <?php endif; ?>
    </section>
</main>
</body>
</html>
