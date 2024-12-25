<?php
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save'])) {
        // Neue Zeile erstellen oder vorhandene mit NULL überschreiben, wenn nötig
        $roles = ['stf', 'ma', 'atf', 'atm', 'wtf', 'wtm', 'prakt'];
        $changes = [];
        $allNull = true;

        foreach ($roles as $role) {
            if (isset($_POST[$role]) && $_POST[$role] !== '') {
                $person_id = $_POST[$role];
                $changes[$role] = $person_id;
                $allNull = false; // Nicht alle Felder sind NULL
            } else {
                $changes[$role] = null;
            }
        }

        if ($allNull) {
            // Prüfen, ob bereits eine Zeile mit nur NULL existiert
            $stmt = $pdo->query("SELECT * FROM Besatzung WHERE stf_id IS NULL AND ma_id IS NULL AND atf_id IS NULL AND atm_id IS NULL AND wtf_id IS NULL AND wtm_id IS NULL AND prakt_id IS NULL LIMIT 1");
            $existingNullRow = $stmt->fetch();

            if (!$existingNullRow) {
                // Neue Zeile mit nur NULL einfügen
                $stmt = $pdo->prepare("INSERT INTO Besatzung (stf_id, ma_id, atf_id, atm_id, wtf_id, wtm_id, prakt_id) VALUES (NULL, NULL, NULL, NULL, NULL, NULL, NULL)");
                $stmt->execute();
            }
        } else {
            // Prüfen, ob die letzte Zeile nur NULL enthält
            $stmt = $pdo->query("SELECT * FROM Besatzung ORDER BY id DESC LIMIT 1");
            $lastRow = $stmt->fetch();
            $lastRowIsNull = ($lastRow && $lastRow['stf_id'] === null && $lastRow['ma_id'] === null && $lastRow['atf_id'] === null && $lastRow['atm_id'] === null && $lastRow['wtf_id'] === null && $lastRow['wtm_id'] === null && $lastRow['prakt_id'] === null);

            if ($lastRowIsNull) {
                // Letzte Zeile überschreiben
                $stmt = $pdo->prepare("UPDATE Besatzung SET stf_id = :stf, ma_id = :ma, atf_id = :atf, atm_id = :atm, wtf_id = :wtf, wtm_id = :wtm, prakt_id = :prakt WHERE id = :id");
                $stmt->execute([
                    ':stf' => $changes['stf'],
                    ':ma' => $changes['ma'],
                    ':atf' => $changes['atf'],
                    ':atm' => $changes['atm'],
                    ':wtf' => $changes['wtf'],
                    ':wtm' => $changes['wtm'],
                    ':prakt' => $changes['prakt'],
                    ':id' => $lastRow['id']
                ]);
            } else {
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
            }
        }

        $message = "Besatzung erfolgreich aktualisiert.";
        header("Location: " . $_SERVER['PHP_SELF']); // Seite neu laden
        exit;
    } elseif (isset($_POST['clear'])) {
        // Alle Rollen auf NULL setzen
        $roles = ['stf', 'ma', 'atf', 'atm', 'wtf', 'wtm', 'prakt'];
        foreach ($roles as $role) {
            $_POST[$role] = '';
        }
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

                        foreach ($roles as $key => $label) {
                            echo "<tr>";
                            echo "<td>$label</td>";

                            // Prüfen, ob eine Person bereits zugewiesen ist
                            $stmt = $pdo->prepare("SELECT p.id, CONCAT(p.vorname, ' ', p.nachname) AS name FROM Personal p JOIN Besatzung b ON p.id = b.{$key}_id LIMIT 1");
                            $stmt->execute();
                            $assigned = $stmt->fetch();

                            if ($assigned) {
                                echo "<td>{$assigned['name']}</td>";
                            } else {
                                echo "<td><em>Keine Zuweisung</em></td>";
                            }

                            // Dropdown zur Auswahl
                            echo "<td><select name='$key'>";
                            echo "<option value=''>Keine Auswahl</option>";
                            $stmt = $pdo->query("SELECT id, CONCAT(vorname, ' ', nachname) AS name FROM Personal");
                            while ($row = $stmt->fetch()) {
                                $selected = ($assigned && $assigned['id'] == $row['id']) ? 'selected' : '';
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
