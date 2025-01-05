<?php
session_start();
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header('Location: login.php');
    exit("Zugriff verweigert. Bitte melden Sie sich an.");
}
require 'db.php';
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alle Dienste</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<header>
    <h1>Alle Dienste</h1>
    <form method="POST" action="logout.php" class="logout-form">
        <button type="submit">Logout</button>
    </form>
    <form method="POST" action="index.php" class="back-form">
        <button type="submit">Zurück</button>
    </form>
</header>
<main>
    <section id="alle-dienste">
        <table>
            <thead>
                <tr>
                    <th>Fahrzeug</th>
                    <th>Zeitraum</th>
                    <th>Dienst Dauer</th>
                    <th>Alarmanzahl</th>
                    <th>Alarme (Stichworte)</th>
                    <th>Personal</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $dienstStmt = $pdo->prepare("
                    SELECT d.id, f.name AS fahrzeug_name, d.inDienstZeit, d.ausserDienstZeit,
                           TIMESTAMPDIFF(MINUTE, STR_TO_DATE(d.inDienstZeit, '%d.%m.%Y %H:%i'), 
                           STR_TO_DATE(d.ausserDienstZeit, '%d.%m.%Y %H:%i')) AS dauer_minuten,
                           COUNT(e.id) AS alarmanzahl
                    FROM dienste d
                    JOIN fahrzeuge f ON f.id = d.fahrzeug_id
                    LEFT JOIN einsaetze e ON e.dienst_id = d.id
                    GROUP BY d.id
                    ORDER BY STR_TO_DATE(d.inDienstZeit, '%d.%m.%Y %H:%i') DESC
                ");
                $dienstStmt->execute();
                $dienste = $dienstStmt->fetchAll(PDO::FETCH_ASSOC);

                foreach ($dienste as $dienst) {
                    $personalStmt = $pdo->prepare("
                        SELECT 
                            CASE 
                                WHEN p.id = d.stf_id THEN 'StF'
                                WHEN p.id = d.ma_id THEN 'Ma'
                                WHEN p.id = d.atf_id THEN 'AtF'
                                WHEN p.id = d.atm_id THEN 'AtM'
                                WHEN p.id = d.wtf_id THEN 'WtF'
                                WHEN p.id = d.wtm_id THEN 'WtM'
                                WHEN p.id = d.prakt_id THEN 'Prakt'
                            END AS funktion,
                            CONCAT(p.vorname, ' ', p.nachname) AS name
                        FROM personal p
                        JOIN dienste d ON p.id IN (
                            d.stf_id, d.ma_id, d.atf_id, d.atm_id, d.wtf_id, d.wtm_id, d.prakt_id
                        )
                        WHERE d.id = :dienst_id
                    ");
                    $personalStmt->execute([':dienst_id' => $dienst['id']]);
                    $personalList = $personalStmt->fetchAll(PDO::FETCH_ASSOC);

                    $alarmeStmt = $pdo->prepare("
                        SELECT stichwort
                        FROM einsaetze
                        WHERE dienst_id = :dienst_id
                    ");
                    $alarmeStmt->execute([':dienst_id' => $dienst['id']]);
                    $alarmeList = $alarmeStmt->fetchAll(PDO::FETCH_COLUMN);

                    $dauer_stunden = floor($dienst['dauer_minuten'] / 60);
                    $dauer_minuten = $dienst['dauer_minuten'] % 60;
                    $dauer_formatiert = sprintf('%02d:%02d Stunden', $dauer_stunden, $dauer_minuten);

                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($dienst['fahrzeug_name']) . "</td>";
                    echo "<td>" . htmlspecialchars($dienst['inDienstZeit']) . " - " . htmlspecialchars($dienst['ausserDienstZeit']) . "</td>";
                    echo "<td>" . htmlspecialchars($dauer_formatiert) . "</td>";
                    echo "<td>" . htmlspecialchars($dienst['alarmanzahl']) . "</td>";
                    echo "<td><details><summary>Details anzeigen</summary><ul>";
                    foreach ($alarmeList as $alarm) {
                        echo "<li>" . htmlspecialchars($alarm) . "</li>";
                    }
                    echo "</ul></details></td>";
                    echo "<td><details><summary>Details anzeigen</summary><ul>";
                    foreach ($personalList as $person) {
                        echo "<li>" . htmlspecialchars($person['funktion'] . ': ' . $person['name']) . "</li>";
                    }
                    echo "</ul></details></td>";
                    echo "</tr>";
                }
                ?>
            </tbody>
        </table>
    </sect
