<?php
require 'db.php';
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Einsatzverwaltung</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header>
        <h1>Einsatzverwaltungssystem</h1>
    </header>

    <main>
        <!-- Aktuelle Besatzung -->
        <section id="aktuelle-besatzung">
            <h2>Aktuelle Besatzung</h2>
            <table>
                <thead>
                    <tr>
                        <th>Funktion</th>
                        <th>Aktuell zugewiesen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Abfrage für die aktuelle Besatzung
                    $roles = [
                        'stf' => 'Staffel-Führer',
                        'ma' => 'Maschinist',
                        'atf' => 'Atemschutz-Führer',
                        'atm' => 'Atemschutz-Mann',
                        'wtf' => 'Wassertrupp-Führer',
                        'wtm' => 'Wassertrupp-Mann',
                        'prakt' => 'Praktikant'
                    ];

                    $besatzungStmt = $pdo->query("SELECT * FROM Besatzung ORDER BY id DESC LIMIT 1");
                    $latestBesatzung = $besatzungStmt->fetch();

                    foreach ($roles as $key => $label) {
                        echo "<tr><td>$label</td>";
                        if ($latestBesatzung && $latestBesatzung[$key . '_id']) {
                            $personStmt = $pdo->prepare("SELECT CONCAT(vorname, ' ', nachname) AS name FROM Personal WHERE id = :id");
                            $personStmt->execute([':id' => $latestBesatzung[$key . '_id']]);
                            $person = $personStmt->fetch();
                            echo "<td>" . ($person['name'] ?? '<em>NICHT BESETZT</em>') . "</td>";
                        } else {
                            echo "<td><em>NICHT BESETZT</em></td>";
                        }
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>
            <div class="button-container">
                <button onclick="location.href='besatzung.php'">Besatzung ändern</button>
            </div>
        </section>

<!-- Neuer Alarm -->
<section id="neuer-alarm">
    <?php
        try {
            // Fahrzeuge laden
            $fahrzeugeStmt = $pdo->prepare("SELECT id, name FROM Fahrzeuge ORDER BY name");
            $fahrzeugeStmt->execute();
            $fahrzeuge = $fahrzeugeStmt->fetchAll(PDO::FETCH_ASSOC);

            // Stichworte laden, sortiert nach Kategorie und Stichwort
            $stichworteStmt = $pdo->prepare("SELECT id, kategorie, stichwort FROM Stichworte ORDER BY kategorie, stichwort");
            $stichworteStmt->execute();
            $stichworte = $stichworteStmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            die("Fehler beim Laden der Daten: " . $e->getMessage());
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
            try {
                // Werte aus dem Formular abrufen
                $einsatznummer_lts = $_POST['einsatznummer_lts'] ?? null;
                $stichwort_id = $_POST['stichwort_id'] ?? null;
                $alarmuhrzeit = $_POST['alarmuhrzeit'] ?? null;
                $zurueckzeit = $_POST['zurueckzeit'] ?? null;
                $adresse = $_POST['adresse'] ?? null;
                $fahrzeug_id = $_POST['fahrzeug_id'] ?? 1;

                // Format prüfen (dd.mm.yy hh:mm)
                if (!preg_match('/^\d{2}\.\d{2}\.\d{2} \d{2}:\d{2}$/', $alarmuhrzeit) || 
                    !preg_match('/^\d{2}\.\d{2}\.\d{2} \d{2}:\d{2}$/', $zurueckzeit)) {
                    throw new Exception("Die Uhrzeiten müssen im Format dd.mm.yy hh:mm vorliegen.");
                }

                // Fahrzeugname anhand der ID ermitteln
                $fahrzeug_name = null;
                foreach ($fahrzeuge as $fahrzeug) {
                    if ($fahrzeug['id'] === (int)$fahrzeug_id) {
                        $fahrzeug_name = $fahrzeug['name'];
                        break;
                    }
                }

                // Aktuellste Besatzung abrufen
                $stmt = $pdo->prepare("SELECT id FROM Besatzung ORDER BY id DESC LIMIT 1");
                $stmt->execute();
                $besatzung_id = $stmt->fetchColumn();

                if (!$besatzung_id) {
                    throw new Exception("Keine gültige Besatzung gefunden.");
                }

                // SQL-Statement vorbereiten und ausführen
                $sql = "INSERT INTO Einsaetze 
                        (einsatznummer_lts, stichwort_id, alarmuhrzeit, zurueckzeit, adresse, fahrzeug_name, besatzung_id)
                        VALUES (:einsatznummer_lts, :stichwort_id, :alarmuhrzeit, :zurueckzeit, :adresse, :fahrzeug_name, :besatzung_id)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':einsatznummer_lts' => $einsatznummer_lts,
                    ':stichwort_id' => $stichwort_id,
                    ':alarmuhrzeit' => $alarmuhrzeit,
                    ':zurueckzeit' => $zurueckzeit,
                    ':adresse' => $adresse,
                    ':fahrzeug_name' => $fahrzeug_name,
                    ':besatzung_id' => $besatzung_id
                ]);
            } catch (Exception $e) {
                echo "<p style='color: red;'>Fehler: " . $e->getMessage() . "</p>";
            }
        }
    ?>

    <script>
        function setCurrentTime(fieldId) {
            const now = new Date();
            const year = String(now.getFullYear()).slice(-2); // Jahr zweistellig
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            document.getElementById(fieldId).value = `${day}.${month}.${year} ${hours}:${minutes}`;
        }
    </script>

    <h2>Neuen Einsatz eintragen</h2>
    <form method="POST" class="einsatz-form">
        <table class="einsatz-tabelle">
            <tr>
                <td><input type="text" id="einsatznummer_lts" name="einsatznummer_lts" placeholder="Einsatznummer LTS"></td>
                <td>
                    <select id="stichwort_id" name="stichwort_id">
                        <?php foreach ($stichworte as $stichwort): ?>
                            <option value="<?= htmlspecialchars($stichwort['id']) ?>">
                                <?= htmlspecialchars($stichwort['kategorie'] . ' - ' . $stichwort['stichwort']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td>
                    <input type="text" id="alarmuhrzeit" name="alarmuhrzeit" placeholder="Alarmzeit: dd.mm.yy hh:mm">
                    <button type="button" onclick="setCurrentTime('alarmuhrzeit')">Jetzt</button>
                </td>
                <td>
                    <input type="text" id="zurueckzeit" name="zurueckzeit" placeholder="Zurückzeit: dd.mm.yy hh:mm">
                    <button type="button" onclick="setCurrentTime('zurueckzeit')">Jetzt</button>
                </td>
                <td><input type="text" id="adresse" name="adresse" placeholder="Adresse"></td>
                <td>
                    <select id="fahrzeug_id" name="fahrzeug_id">
                        <?php foreach ($fahrzeuge as $fahrzeug): ?>
                            <option value="<?= htmlspecialchars($fahrzeug['id']) ?>" 
                                <?= $fahrzeug['id'] === 1 ? 'selected' : '' ?>>
                                <?= htmlspecialchars($fahrzeug['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td><button type="submit" name="save" class="btn">Speichern</button></td>
            </tr>
        </table>
    </form>
</section>


         


        <!-- Letzte Einsätze -->
        <section id="letzte-einsaetze">
            <h2>Letzte 15 Alarme</h2>
            <table>
                <thead>
                    <tr>
                        <th>Interne Einsatznummer</th>
                        <th>Stichwort</th>
                        <th>Alarmzeit</th>
                        <th>Fahrzeug</th>
                        <th>Personal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // SQL-Abfrage: Abrufen der letzten 15 Einsätze mit Besatzung und Personal
                    $stmt = $pdo->query("
                        SELECT e.interne_einsatznummer, e.alarmuhrzeit, e.fahrzeug_name, s.stichwort,
                            p1.nachname AS stf, p2.nachname AS ma, p3.nachname AS atf,
                            p4.nachname AS atm, p5.nachname AS wtf, p6.nachname AS wtm, p7.nachname AS prakt
                        FROM Einsaetze e
                        LEFT JOIN Stichworte s ON e.stichwort_id = s.id
                        LEFT JOIN Besatzung b ON e.besatzung_id = b.id
                        LEFT JOIN Personal p1 ON b.stf_id = p1.id
                        LEFT JOIN Personal p2 ON b.ma_id = p2.id
                        LEFT JOIN Personal p3 ON b.atf_id = p3.id
                        LEFT JOIN Personal p4 ON b.atm_id = p4.id
                        LEFT JOIN Personal p5 ON b.wtf_id = p5.id
                        LEFT JOIN Personal p6 ON b.wtm_id = p6.id
                        LEFT JOIN Personal p7 ON b.prakt_id = p7.id
                        ORDER BY e.id DESC LIMIT 15 
                    ");

                    // Ergebnisse anzeigen
                    while ($row = $stmt->fetch()) {
                        // Personal zusammenstellen
                        $personal = [];
                        if ($row['stf']) $personal[] = "StF: " . htmlspecialchars($row['stf']);
                        if ($row['ma']) $personal[] = "Ma: " . htmlspecialchars($row['ma']);
                        if ($row['atf']) $personal[] = "AtF: " . htmlspecialchars($row['atf']);
                        if ($row['atm']) $personal[] = "AtM: " . htmlspecialchars($row['atm']);
                        if ($row['wtf']) $personal[] = "WtF: " . htmlspecialchars($row['wtf']);
                        if ($row['wtm']) $personal[] = "WtM: " . htmlspecialchars($row['wtm']);
                        if ($row['prakt']) $personal[] = "Prakt: " . htmlspecialchars($row['prakt']);

                        echo "<tr>
                                <td>" . htmlspecialchars($row['interne_einsatznummer']) . "</td>
                                <td>" . htmlspecialchars($row['stichwort']) . "</td>
                                <td>" . htmlspecialchars($row['alarmuhrzeit']) . "</td>
                                <td>" . htmlspecialchars($row['fahrzeug_name']) . "</td>
                                <td>
                                    <details>
                                        <summary>Details anzeigen</summary>
                                        " . implode('<br>', $personal) . "
                                    </details>
                                </td>
                              </tr>";
                    }
                    ?>
                </tbody>
            </table>
            <div class="button-container">
                <button onclick="location.href='historie.php'">Alle Alarme</button>
            </div>
        </section>

        <!-- Navigation als Buttons -->
        <section id="navigation-buttons">
            <h2>Einstellungen</h2>
            <div class="button-container">
                <button onclick="location.href='neuer_benutzer.php'">Neuer Benutzer</button>
                <button onclick="location.href='stichworte.php'">Stichworte verwalten</button>
            </div>
        </section>
    </main>

    <footer>
        <p>&copy; 2025 Einsatzverwaltung. Alle Rechte vorbehalten.</p>
    </footer>
</body>
</html>
