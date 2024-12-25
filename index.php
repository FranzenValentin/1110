<?php
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_einsatz'])) {
    // Eingabedaten erfassen
    $einsatznummer = $_POST['einsatznummer'];
    $alarmzeit = $_POST['alarmzeit'];
    $zurueckzeit = $_POST['zurueckzeit'];
    $stichwort = $_POST['stichwort'];
    $adresse = $_POST['adresse'];

    // Die aktuellste Besatzung abrufen
    $stmt = $pdo->query("SELECT * FROM Besatzung ORDER BY id DESC LIMIT 1");
    $currentBesatzung = $stmt->fetch();

    if ($currentBesatzung) {
        // Einsatz in die Datenbank einfügen
        $stmt = $pdo->prepare("
            INSERT INTO Einsaetze (einsatznummer, alarmzeit, zurueckzeit, stichwort, adresse, stf_id, ma_id, atf_id, atm_id, wtf_id, wtm_id, prakt_id)
            VALUES (:einsatznummer, :alarmzeit, :zurueckzeit, :stichwort, :adresse, :stf_id, :ma_id, :atf_id, :atm_id, :wtf_id, :wtm_id, :prakt_id)
        ");
        $stmt->execute([
            ':einsatznummer' => $einsatznummer,
            ':alarmzeit' => $alarmzeit,
            ':zurueckzeit' => $zurueckzeit,
            ':stichwort' => $stichwort,
            ':adresse' => $adresse,
            ':stf_id' => $currentBesatzung['stf_id'],
            ':ma_id' => $currentBesatzung['ma_id'],
            ':atf_id' => $currentBesatzung['atf_id'],
            ':atm_id' => $currentBesatzung['atm_id'],
            ':wtf_id' => $currentBesatzung['wtf_id'],
            ':wtm_id' => $currentBesatzung['wtm_id'],
            ':prakt_id' => $currentBesatzung['prakt_id']
        ]);
        $message = "Einsatz erfolgreich hinzugefügt.";
    } else {
        $message = "Keine Besatzung vorhanden. Der Einsatz konnte nicht gespeichert werden.";
    }
}
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

    <nav>
        <ul>
            <li><a href="neuer_benutzer.php">Neuer Benutzer</a></li>
            <li><a href="besatzung.php">Besatzung verwalten</a></li>
            <li><a href="einsatz.php">Einsätze hinzufügen</a></li>
            <li><a href="stichworte.php">Stichworte verwalten</a></li>
        </ul>
    </nav>

    <main>
        <section id="aktuelle-besatzung">
            <h2>Besatzungsrollen und Zuweisungen</h2>
            <table>
                <thead>
                    <tr>
                        <th>Funktion</th>
                        <th>Aktuell zugewiesen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $roles = [
                        'stf' => 'Staffel-Führer',
                        'ma' => 'Maschinist',
                        'atf' => 'Atemschutz-Führer',
                        'atm' => 'Atemschutz-Mann',
                        'wtf' => 'Wachtrupp-Führer',
                        'wtm' => 'Wachtrupp-Mann',
                        'prakt' => 'Praktikant'
                    ];
                    $stmt = $pdo->query("SELECT * FROM Besatzung ORDER BY id DESC LIMIT 1");
                    $latestBesatzung = $stmt->fetch();

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
        </section>

        <section id="letzte-einsaetze">
            <h2>Letzte Alarme</h2>
            <table>
                <thead>
                    <tr>
                        <th>Interne Einsatznummer</th>
                        <th>Stichwort</th>
                        <th>Besatzung</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stmt = $pdo->query("
                        SELECT e.einsatznummer, e.stichwort,
                               CONCAT_WS(', ',
                                   p1.vorname, p1.nachname,
                                   p2.vorname, p2.nachname,
                                   p3.vorname, p3.nachname,
                                   p4.vorname, p4.nachname,
                                   p5.vorname, p5.nachname,
                                   p6.vorname, p6.nachname,
                                   p7.vorname, p7.nachname
                               ) AS besatzung
                        FROM Einsaetze e
                        LEFT JOIN Personal p1 ON e.stf_id = p1.id
                        LEFT JOIN Personal p2 ON e.ma_id = p2.id
                        LEFT JOIN Personal p3 ON e.atf_id = p3.id
                        LEFT JOIN Personal p4 ON e.atm_id = p4.id
                        LEFT JOIN Personal p5 ON e.wtf_id = p5.id
                        LEFT JOIN Personal p6 ON e.wtm_id = p6.id
                        LEFT JOIN Personal p7 ON e.prakt_id = p7.id
                        ORDER BY e.id DESC LIMIT 10
                    ");
                    while ($row = $stmt->fetch()) {
                        echo "<tr>
                                <td>" . htmlspecialchars($row['einsatznummer']) . "</td>
                                <td>" . htmlspecialchars($row['stichwort']) . "</td>
                                <td>" . htmlspecialchars($row['besatzung'] ?: '<em>Keine Besatzung</em>') . "</td>
                              </tr>";
                    }
                    ?>
                </tbody>
            </table>
        </section>
    </main>

    <footer>
        <p>&copy; 2025 Einsatzverwaltung. Alle Rechte vorbehalten.</p>
    </footer>
</body>
</html>
