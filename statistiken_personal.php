 <?php
session_start();
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header('Location: login.php'); // Weiterleitung zur Login-Seite
    exit;
}
require 'db.php';

// Standardwerte für den Zeitraum (aktuelles Jahr)
$startdatum = isset($_GET['startdatum']) ? $_GET['startdatum'] : date('Y-01-01');
$enddatum = isset($_GET['enddatum']) ? $_GET['enddatum'] : date('Y-m-d');
$personId = isset($_GET['person_id']) ? $_GET['person_id'] : null;

// Personal laden
$personalStmt = $pdo->query("SELECT id, CONCAT(vorname, ' ', nachname) AS name FROM Personal ORDER BY nachname");
$personal = $personalStmt->fetchAll(PDO::FETCH_ASSOC);

// Einsätze abrufen, wenn eine Person ausgewählt ist
$einsaetze = [];
$funktionenVerteilung = [];
if ($personId) {
    $einsaetzeStmt = $pdo->prepare("
        SELECT 
            e.interne_einsatznummer, e.stichwort, e.alarmuhrzeit, e.zurueckzeit, e.fahrzeug_name,
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
        FROM Einsaetze e
        LEFT JOIN Besatzung b ON e.besatzung_id = b.id
        WHERE :personId IN (b.stf_id, b.ma_id, b.atf_id, b.atm_id, b.wtf_id, b.wtm_id, b.prakt_id)
          AND STR_TO_DATE(e.alarmuhrzeit, '%d.%m.%y %H:%i') BETWEEN :startdatum AND :enddatum
        ORDER BY e.alarmuhrzeit DESC
    ");
    $einsaetzeStmt->execute([
        ':personId' => $personId,
        ':startdatum' => $startdatum,
        ':enddatum' => $enddatum
    ]);
    $einsaetze = $einsaetzeStmt->fetchAll(PDO::FETCH_ASSOC);

    // Verteilung der Funktionen abrufen
    $funktionenStmt = $pdo->prepare("
        SELECT 
            CASE
                WHEN b.stf_id = :personId THEN 'Staffel-Führer'
                WHEN b.ma_id = :personId THEN 'Maschinist'
                WHEN b.atf_id = :personId THEN 'Angriffstrupp-Führer'
                WHEN b.atm_id = :personId THEN 'Angriffstrupp-Mann'
                WHEN b.wtf_id = :personId THEN 'Wassertrupp-Führer'
                WHEN b.wtm_id = :personId THEN 'Wassertrupp-Mann'
                WHEN b.prakt_id = :personId THEN 'Praktikant'
                ELSE 'Unbekannt'
            END AS funktion,
            COUNT(*) AS anzahl
        FROM Einsaetze e
        LEFT JOIN Besatzung b ON e.besatzung_id = b.id
        WHERE :personId IN (b.stf_id, b.ma_id, b.atf_id, b.atm_id, b.wtf_id, b.wtm_id, b.prakt_id)
          AND STR_TO_DATE(e.alarmuhrzeit, '%d.%m.%y %H:%i') BETWEEN :startdatum AND :enddatum
        GROUP BY funktion
    ");
    $funktionenStmt->execute([
        ':personId' => $personId,
        ':startdatum' => $startdatum,
        ':enddatum' => $enddatum
    ]);
    $funktionenVerteilung = $funktionenStmt->fetchAll(PDO::FETCH_ASSOC);
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
    <h1>Statistiken für Personal</h1>
    <form method="POST" action="logout.php" class="logout-form">
        <button type="submit">Logout</button>
    </form>
    <form method="POST" action="index.php" class="back-form">
        <button type="submit">Zurück</button>
    </form>
</header>

<main>
    <!-- Zurück-Button -->
    <div class="button-container">
        <button onclick="location.href='index.php'">Zurück zur Übersicht</button>
    </div>

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

    <!-- Einsätze anzeigen -->
    <section id="einsatz-statistik">
        <h2>Einsätze</h2>
        <?php if ($personId): ?>
            <p>Zeitraum: <?= htmlspecialchars($startdatum) ?> bis <?= htmlspecialchars($enddatum) ?></p>
            <?php if (count($einsaetze) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Interne Einsatznummer</th>
                            <th>Stichwort</th>
                            <th>Alarmzeit</th>
                            <th>Zurückzeit</th>
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
                                <td><?= htmlspecialchars($einsatz['zurueckzeit']) ?></td>
                                <td><?= htmlspecialchars($einsatz['fahrzeug_name']) ?></td>
                                <td><?= htmlspecialchars($einsatz['funktion']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>Keine Einsätze für diesen Zeitraum gefunden.</p>
            <?php endif; ?>
        <?php else: ?>
            <p>Bitte wählen Sie eine Person und einen Zeitraum aus, um die Einsätze anzuzeigen.</p>
        <?php endif; ?>
    </section>

    <!-- Verteilung der Funktionen -->
    <section id="funktionen-verteilung">
        <h2>Verteilung der Funktionen</h2>
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
            <p>Keine Daten zur Verteilung der Funktionen verfügbar.</p>
        <?php endif; ?>
    </section>
</main>

</body>
</html>
