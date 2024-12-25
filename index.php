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
        <!-- Letzte Alarme -->
        <section id="letzte-einsaetze">
            <h2>Letzte Alarme</h2>
            <table>
                <thead>
                    <tr>
                        <th>Interne Einsatznummer</th>
                        <th>Stichwort</th>
                        <th>Alarmzeit</th>
                        <th>Rückkehrzeit</th>
                        <th>Adresse</th>
                        <th>Fahrzeug</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // SQL-Abfrage: Abrufen der letzten Einsätze
                    $stmt = $pdo->query("
                        SELECT e.interne_einsatznummer, e.alarmuhrzeit, e.zurueckzeit, e.adresse, e.fahrzeug_name, s.stichwort
                        FROM Einsaetze e
                        LEFT JOIN Stichworte s ON e.stichwort_id = s.id
                        ORDER BY e.id DESC LIMIT 10
                    ");

                    // Ergebnisse anzeigen
                    while ($row = $stmt->fetch()) {
                        echo "<tr>
                                <td>" . htmlspecialchars($row['interne_einsatznummer']) . "</td>
                                <td>" . htmlspecialchars($row['stichwort']) . "</td>
                                <td>" . htmlspecialchars($row['alarmuhrzeit']) . "</td>
                                <td>" . htmlspecialchars($row['zurueckzeit']) . "</td>
                                <td>" . htmlspecialchars($row['adresse']) . "</td>
                                <td>" . htmlspecialchars($row['fahrzeug_name']) . "</td>
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
