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
                        <th>Funktion</th>
                        <th>Aktuell zugewiesen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Besatzungsrollen definieren
                    $roles = [
                        'stf' => 'Staffel-Führer',
                        'ma' => 'Maschinist',
                        'atf' => 'Atemschutz-Führer',
                        'atm' => 'Atemschutz-Mann',
                        'wtf' => 'Wachtrupp-Führer',
                        'wtm' => 'Wachtrupp-Mann',
                        'prakt' => 'Praktikant'
                    ];

                    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['role'], $_POST['person_id'])) {
                        $role = $_POST['role'];
                        $person_id = $_POST['person_id'];
                        $updateStmt = $pdo->prepare("UPDATE Besatzung SET {$role}_id = :person_id");
                        $updateStmt->execute([':person_id' => $person_id]);
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
                            echo "<td><em>NICHT BESETZT</em></td>";
                        }
                    }
                    ?>
                </tbody>
            </table>
        </section>
        <li><a href="besatzung.php">Besatzung ändern</a></li>
    </main>
    
    <nav>
        <ul>
        <div class="button-container">
    <a href="neuer_benutzer.php" class="button">Neuer Benutzer</a>
    <a href="besatzung.php" class="button">Besatzung verwalten</a>
    <a href="einsatz.php" class="button">Einsätze hinzufügen</a>
    <a href="stichworte.php" class="button">Stichworte verwalten</a>
</div>

        </ul>
    </nav>
    <footer>
        <p>&copy; 2024 Einsatzverwaltung. Alle Rechte vorbehalten.</p>
    </footer>
</body>
</html>
