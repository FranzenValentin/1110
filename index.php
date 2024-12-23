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
        <section id="aktuelle-besatzung">
            <h2>Besatzungsrollen und Zuweisungen</h2>
            <table>
                <thead>
                    <tr>
                        <th>Rolle</th>
                        <th>Aktuell zugewiesen</th>
                        <th>Aktion</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Besatzungsrollen definieren
                    $roles = [
                        'stf' => 'Staffel-Führer (StF)',
                        'ma' => 'Maschinist (MA)',
                        'atf' => 'Atemschutz-Führer (AtF)',
                        'atm' => 'Atemschutz-Mann (AtM)',
                        'wtf' => 'Wachtrupp-Führer (WtF)',
                        'wtm' => 'Wachtrupp-Mann (WtM)',
                        'prakt' => 'Praktikant (Prakt)'
                    ];

                    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['role'], $_POST['person_id'])) {
                        $role = $_POST['role'];
                        $person_id = $_POST['person_id'];
                        $updateStmt = $pdo->prepare("UPDATE Besatzung SET {$role}_id = :person_id");
                        $updateStmt->execute([':person_id' => $person_id]);
                        echo "<p>Die Besatzung wurde erfolgreich aktualisiert.</p>";
                    }

                    foreach ($roles as $key => $label) {
                        echo "<tr>";
                        echo "<td>$label</td>";

                        // Prüfen, ob eine Person bereits zugewiesen ist
                        $stmt = $pdo->prepare("SELECT p.id, CONCAT(p.vorname, ' ', p.nachname) AS name FROM Personal p JOIN Besatzung b ON p.id = b.{$key}_id LIMIT 1");
                        $stmt->execute();
                        $assigned = $stmt->fetch();

                        if ($assigned) {
                            // Wenn jemand zugewiesen ist, wird der Name angezeigt
                            echo "<td>{$assigned['name']}</td>";
                        } else {
                            // Wenn niemand zugewiesen ist, Hinweis anzeigen
                            echo "<td><em>Keine Zuweisung</em></td>";
                        }

                        // Dropdown zur Änderung der Zuweisung
                        echo "<td><form method='POST'>";
                        echo "<input type='hidden' name='role' value='$key'>";
                        echo "<select name='person_id'>";
                        $stmt = $pdo->query("SELECT id, CONCAT(vorname, ' ', nachname) AS name FROM Personal");
                        while ($row = $stmt->fetch()) {
                            echo "<option value='{$row['id']}'>{$row['name']}</option>";
                        }
                        echo "</select>";
                        echo "<button type='submit'>Ändern</button>";
                        echo "</form></td>";
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>
        </section>
    </main>
    
    <nav>
        <ul>
            <li><a href="neuer_Benutzer.php">Neuer Benutzer</a></li>
            <li><a href="besatzung.php">Besatzung verwalten</a></li>
            <li><a href="einsatz.php">Einsätze hinzufügen</a></li>
            <li><a href="stichworte.php">Stichworte verwalten</a></li>
        </ul>
    </nav>
    <footer>
        <p>&copy; 2024 Einsatzverwaltung. Alle Rechte vorbehalten.</p>
    </footer>
</body>
</html>
