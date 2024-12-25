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

    <nav>
        <ul>
            <li><a href="neuer_benutzer.php">Neuer Benutzer</a></li>
            <li><a href="besatzung.php">Besatzung verwalten</a></li>
            <li><a href="einsatz.php">Einsätze hinzufügen</a></li>
            <li><a href="stichworte.php">Stichworte verwalten</a></li>
        </ul>
    </nav>

    <main>
        <!-- Aktuelle Besatzung -->
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
                    // Abfrage für die aktuelle Besatzung
                    $roles = [
                        'stf' => 'Staffel-Führer',
                        'ma' => 'Maschinist',
                        'atf' => 'Atemschutz-Führer',
                        'atm' => 'Atemschutz-Mann',
                        'wtf' => 'Wachtrupp-Führer',
                        'wtm' => 'Wachtrupp-Mann',
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
        </section>

<section id="letzte-einsaetze">
    <h2>Letzte Alarme</h2>
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
            // SQL-Abfrage: Abrufen der letzten Einsätze
            $stmt = $pdo->query("
                SELECT e.interne_einsatznummer, e.alarmuhrzeit, e.fahrzeug_name, s.stichwort,
                    p1.nachname AS stf, p2.nachname AS ma, p3.nachname AS atf,
                    p4.nachname AS atm, p5.nachname AS wtf, p6.nachname AS wtm, p7.nachname AS prakt
                FROM Einsaetze e
                LEFT JOIN Stichworte s ON e.stichwort_id = s.id
                LEFT JOIN Personal p1 ON e.stf_id = p1.id
                LEFT JOIN Personal p2 ON e.ma_id = p2.id
                LEFT JOIN Personal p3 ON e.atf_id = p3.id
                LEFT JOIN Personal p4 ON e.atm_id = p4.id
                LEFT JOIN Personal p5 ON e.wtf_id = p5.id
                LEFT JOIN Personal p6 ON e.wtm_id = p6.id
                LEFT JOIN Personal p7 ON e.prakt_id = p7.id
                ORDER BY e.id DESC LIMIT 10
            ");

            // Ergebnisse anzeigen
            while ($row = $stmt->fetch()) {
                // Personal zusammenstellen
                $personal = [];
                if ($row['stf']) $personal[] = "Staffel-Führer: " . htmlspecialchars($row['stf']);
                if ($row['ma']) $personal[] = "Maschinist: " . htmlspecialchars($row['ma']);
                if ($row['atf']) $personal[] = "Atemschutz-Führer: " . htmlspecialchars($row['atf']);
                if ($row['atm']) $personal[] = "Atemschutz-Mann: " . htmlspecialchars($row['atm']);
                if ($row['wtf']) $personal[] = "Wachtrupp-Führer: " . htmlspecialchars($row['wtf']);
                if ($row['wtm']) $personal[] = "Wachtrupp-Mann: " . htmlspecialchars($row['wtm']);
                if ($row['prakt']) $personal[] = "Praktikant: " . htmlspecialchars($row['prakt']);

                echo "<tr>
                        <td>" . htmlspecialchars($row['interne_einsatznummer']) . "</td>
                        <td>" . htmlspecialchars($row['stichwort']) . "</td>
                        <td>" . htmlspecialchars($row['alarmuhrzeit']) . "</td>
                        <td>" . htmlspecialchars($row['fahrzeug_name']) . "</td>
                        <td>" . implode('<br>', $personal) . "</td>
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
