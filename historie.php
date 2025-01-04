<?php
session_start();
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header('Location: login.php'); // Weiterleitung zur Login-Seite
    exit;
}
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
        <h1>Einsatz Historie</h1>
        <form method="POST" action="logout.php" class="logout-form">
            <button type="submit">Logout</button>
        </form>
        <form method="POST" action="index.php" class="back-form">
            <button type="submit">Zurück</button>
        </form>
    </header>

    <main>
        <!-- Letzte Einsätze -->
        <section id="letzte-einsaetze">           
            <table>
                <thead>
                    <tr>
                        <th>Interne Einsatznummer</th>
                        <th>Einsatznummer</th>
                        <th>Stichwort</th>
                        <th>Alarmzeit</th>
                        <th>Zurückzeit</th>
                        <th>Fahrzeug</th>
                        <th>Straße</th>
                        <th>Stadtteil</th>
                        <th>Personal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // SQL-Abfrage: Abrufen der letzten Einsätze mit allen relevanten Daten
                    $stmt = $pdo->query("
                        SELECT e.interne_einsatznummer, e.einsatznummer_lts, e.alarmuhrzeit, e.zurueckzeit, e.fahrzeug_name, e.adresse, e.stadtteil, e.stichwort,
                            p1.nachname AS stf, p2.nachname AS ma, p3.nachname AS atf,
                            p4.nachname AS atm, p5.nachname AS wtf, p6.nachname AS wtm, p7.nachname AS prakt
                        FROM einsaetze e
                        LEFT JOIN dienst b ON e.dienst_id = b.id
                        LEFT JOIN personal p1 ON b.stf_id = p1.id
                        LEFT JOIN personal p2 ON b.ma_id = p2.id
                        LEFT JOIN personal p3 ON b.atf_id = p3.id
                        LEFT JOIN personal p4 ON b.atm_id = p4.id
                        LEFT JOIN personal p5 ON b.wtf_id = p5.id
                        LEFT JOIN personal p6 ON b.wtm_id = p6.id
                        LEFT JOIN personal p7 ON b.prakt_id = p7.id
                        ORDER BY 
                            CAST(SUBSTRING_INDEX(e.interne_einsatznummer, '_', 1) AS UNSIGNED) DESC,
                            CAST(SUBSTRING_INDEX(e.interne_einsatznummer, '_', -1) AS UNSIGNED) DESC
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
                                <td>" . htmlspecialchars($row['einsatznummer_lts']) . "</td>
                                <td>" . htmlspecialchars($row['stichwort']) . "</td>
                                <td>" . htmlspecialchars($row['alarmuhrzeit']) . "</td>
                                <td>" . htmlspecialchars($row['zurueckzeit']) . "</td>
                                <td>" . htmlspecialchars($row['fahrzeug_name']) . "</td>
                                <td>" . htmlspecialchars($row['adresse']) . "</td>
                                <td>" . htmlspecialchars($row['stadtteil']) . "</td>
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
        </section>
    </main>
</body>
</html>
