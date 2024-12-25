<?php
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save'])) {
        // Besatzung speichern
        $roles = ['stf', 'ma', 'atf', 'atm', 'wtf', 'wtm', 'prakt'];
        foreach ($roles as $role) {
            if (isset($_POST[$role]) && $_POST[$role] !== '') { // Sicherstellen, dass die Auswahl existiert und nicht leer ist
                $person_id = $_POST[$role];
                $stmt = $pdo->prepare("UPDATE Besatzung SET {$role}_id = :person_id");
                $stmt->execute([':person_id' => $person_id]);
            } else {
                // Wenn keine Person ausgewählt wurde, setzen wir NULL für die Rolle
                $stmt = $pdo->prepare("UPDATE Besatzung SET {$role}_id = NULL");
                $stmt->execute();
            }
        }
        $message = "Besatzung erfolgreich aktualisiert.";
        header("Location: " . $_SERVER['PHP_SELF']); // Seite neu laden
        exit;
    } elseif (isset($_POST['clear'])) {
        // Alle Zuordnungen löschen
        $roles = ['stf', 'ma', 'atf', 'atm', 'wtf', 'wtm', 'prakt'];
        foreach ($roles as $role) {
            $stmt = $pdo->prepare("UPDATE Besatzung SET {$role}_id = NULL");
            $stmt->execute();
        }
        $message = "Alle Zuordnungen wurden gelöscht.";
        header("Location: " . $_SERVER['PHP_SELF']); // Seite neu laden
        exit;
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
                                echo "<td><em>NICHT BESETZT</em></td>";
                            }

                            // Dropdown zur Auswahl
                            echo "<td><select name='$key'>";
                            echo "<option value=''>NICHT BESETZT</option>";
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
