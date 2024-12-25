<?php
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save'])) {
        // Besatzung speichern
        $roles = ['stf', 'ma', 'atf', 'atm', 'wtf', 'wtm', 'prakt'];
        $changes = [];

        foreach ($roles as $role) {
            if (isset($_POST[$role]) && $_POST[$role] !== '') {
                $person_id = $_POST[$role];
                $changes[$role] = $person_id;
            } else {
                $changes[$role] = null;
            }
        }

        // Neue Zeile mit den Änderungen erstellen
        $stmt = $pdo->prepare("INSERT INTO Besatzung (stf_id, ma_id, atf_id, atm_id, wtf_id, wtm_id, prakt_id) VALUES (:stf, :ma, :atf, :atm, :wtf, :wtm, :prakt)");
        $stmt->execute([
            ':stf' => $changes['stf'],
            ':ma' => $changes['ma'],
            ':atf' => $changes['atf'],
            ':atm' => $changes['atm'],
            ':wtf' => $changes['wtf'],
            ':wtm' => $changes['wtm'],
            ':prakt' => $changes['prakt']
        ]);

        $message = "Besatzung erfolgreich aktualisiert.";
        header("Location: " . $_SERVER['PHP_SELF']); // Seite neu laden
        exit;
    } elseif (isset($_POST['clear'])) {
        // Neue Zeile mit nur NULL einfügen
        $stmt = $pdo->prepare("INSERT INTO Besatzung (stf_id, ma_id, atf_id, atm_id, wtf_id, wtm_id, prakt_id) VALUES (NULL, NULL, NULL, NULL, NULL, NULL, NULL)");
        $stmt->execute();

        $message = "Auswahl zurückgesetzt. Bitte speichern, um Änderungen zu übernehmen.";
    }
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Besatzung verwalten</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header>
        <h1>Besatzung verwalten</h1>
    </header>
    <main>
        <section id="aktuelle-besatzung">
            <h2>Besatzungsrollen und Zuweisungen</h2>
            <?php if (isset($message)) { echo "<p>$message</p>"; } ?>
            <form method="POST">
                <table>
                    <thead>
                        <tr>
                            <th>Funktion</th>
                            <th>Aktuell zugewiesen</th>
                            <th>Neue Auswahl</th>
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

                        // Die letzte Besatzungszeile abrufen
                        $stmt = $pdo->query("SELECT * FROM Besatzung ORDER BY id DESC LIMIT 1");
                        $latestBesatzung = $stmt->fetch();

                        foreach ($roles as $key => $label) {
                            echo "<tr>";
                            echo "<td>$label</td>";

                            // Prüfen, ob eine Person bereits zugewiesen ist
                            if ($latestBesatzung && $latestBesatzung[$key . '_id']) {
                                $personStmt = $pdo->prepare("SELECT CONCAT(vorname, ' ', nachname) AS name FROM Personal WHERE id = :id");
                                $personStmt->execute([':id' => $latestBesatzung[$key . '_id']]);
                                $person = $personStmt->fetch();
                                echo "<td>" . ($person['name'] ?? '<em>Keine Zuweisung</em>') . "</td>";
                            } else {
                                echo "<td><em>Keine Zuweisung</em></td>";
                            }

                            // Dropdown zur Auswahl
                            echo "<td><select name='$key'>";
                            echo "<option value=''>Keine Auswahl</option>";
                            $stmt = $pdo->query("SELECT id, CONCAT(vorname, ' ', nachname) AS name FROM Personal");
                            while ($row = $stmt->fetch()) {
                                $selected = ($latestBesatzung && $latestBesatzung[$key . '_id'] == $row['id']) ? 'selected' : '';
                                echo "<option value='{$row['id']}' $selected>{$row['name']}</option>";
                            }
                            echo "</select></td>";
                            echo "</tr>";
                        }
                        ?>
                    </tbody>
                </table>
                <div>
                    <button type="submit" name="save">Speichern</button>
                    <button type="submit" name="clear">Alle löschen</button>
                    <button type="button" onclick="window.location.href='index.php';">Zurück zur Startseite</button>
                </div>
            </form>
        </section>
    </main>
</body>
</html>
