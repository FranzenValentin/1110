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

                        // Die neueste Besatzungszeile abrufen
                        $stmt = $pdo->query("SELECT * FROM Besatzung ORDER BY id DESC LIMIT 1");
                        $latestBesatzung = $stmt->fetch();

                        if ($latestBesatzung && $latestBesatzung[$key . '_id']) {
                            // Die zugewiesene Person abrufen
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
        <button type="button" onclick="window.location.href='besatzung.php';">Besatzung ändern</button>
    </main>
    
    <nav>
        <ul>
            <button type="button" onclick="window.location.href='neuer_benutzer.php';">Neuer Benutzer</button>
            <button type="button" onclick="window.location.href='stichworte.php';">Stichworte verwalten</button>
        </ul>
    </nav>
    <footer>
        <p>&copy; 2024 Einsatzverwaltung. Alle Rechte vorbehalten.</p>
    </footer>
</body>
</html>
