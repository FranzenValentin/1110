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
                <?php
                try {
                    // Personal aus der Datenbank abrufen
                    $personalStmt = $pdo->query("SELECT id, CONCAT(vorname, ' ', nachname) AS name FROM personal ORDER BY nachname");
                    $personal = $personalStmt->fetchAll(PDO::FETCH_ASSOC);

                    // Debug-Ausgabe, um sicherzustellen, dass Daten vorhanden sind
                    if (empty($personal)) {
                        echo "<option value='' disabled>Keine Personen gefunden</option>";
                    } else {
                        foreach ($personal as $person) {
                            // Dropdown-Optionen erstellen
                            echo "<option value='" . htmlspecialchars($person['id']) . "'";
                            if ($personId == $person['id']) {
                                echo " selected";
                            }
                            echo ">" . htmlspecialchars($person['name']) . "</option>";
                        }
                    }
                } catch (PDOException $e) {
                    echo "<option value='' disabled>Fehler beim Laden: " . htmlspecialchars($e->getMessage()) . "</option>";
                }
                ?>
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
