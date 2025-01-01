<?php
session_start();
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header('Location: login.php');
    exit;
}

require 'db.php';

// Monat und Jahr abrufen
$monat = $_POST['monat'];
$jahr = $_POST['jahr'];

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Einsatzübersicht - <?= htmlspecialchars($monat) ?>/<?= htmlspecialchars($jahr) ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        h1 {
            text-align: center;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        table, th, td {
            border: 1px solid #000;
        }
        th, td {
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        @media print {
            button {
                display: none;
            }
        }
    </style>
</head>
<body>
    <h1>Einsatzübersicht - <?= htmlspecialchars($monat) ?>/<?= htmlspecialchars($jahr) ?></h1>
    <button onclick="window.print()">PDF Speichern/Drucken</button>
    <table>
        <thead>
            <tr>
                <th>Interne Einsatznummer</th>
                <th>Einsatznummer</th>
                <th>Stichwort</th>
                <th>Alarmzeit</th>
                <th>Zurückzeit</th>
                <th>Fahrzeug</th>
                <th>Adresse</th>
                <th>StF</th>
                <th>Ma</th>
                <th>AtF</th>
                <th>AtM</th>
                <th>WtF</th>
                <th>WtM</th>
                <th>Praktikant</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // Datenbankabfrage
            $query = "
                SELECT 
                    e.interne_einsatznummer,
                    e.einsatznummer_lts,
                    e.stichwort,
                    e.alarmuhrzeit,
                    e.zurueckzeit,
                    e.fahrzeug_name,
                    e.adresse,
                    p1.nachname AS stf,
                    p2.nachname AS ma,
                    p3.nachname AS atf,
                    p4.nachname AS atm,
                    p5.nachname AS wtf,
                    p6.nachname AS wtm,
                    p7.nachname AS praktikant
                FROM Einsaetze e
                LEFT JOIN Besatzung b ON e.besatzung_id = b.id
                LEFT JOIN Personal p1 ON b.stf_id = p1.id
                LEFT JOIN Personal p2 ON b.ma_id = p2.id
                LEFT JOIN Personal p3 ON b.atf_id = p3.id
                LEFT JOIN Personal p4 ON b.atm_id = p4.id
                LEFT JOIN Personal p5 ON b.wtf_id = p5.id
                LEFT JOIN Personal p6 ON b.wtm_id = p6.id
                LEFT JOIN Personal p7 ON b.prakt_id = p7.id
                WHERE MONTH(STR_TO_DATE(e.alarmuhrzeit, '%d.%m.%y %H:%i')) = :monat 
                  AND YEAR(STR_TO_DATE(e.alarmuhrzeit, '%d.%m.%y %H:%i')) = :jahr
                ORDER BY e.id DESC
            ";
            $stmt = $pdo->prepare($query);
            $stmt->execute(['monat' => $monat, 'jahr' => $jahr]);

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($row['interne_einsatznummer']) . '</td>';
                echo '<td>' . htmlspecialchars($row['einsatznummer_lts']) . '</td>';
                echo '<td>' . htmlspecialchars($row['stichwort']) . '</td>';
                echo '<td>' . htmlspecialchars($row['alarmuhrzeit']) . '</td>';
                echo '<td>' . htmlspecialchars($row['zurueckzeit']) . '</td>';
                echo '<td>' . htmlspecialchars($row['fahrzeug_name']) . '</td>';
                echo '<td>' . htmlspecialchars($row['adresse']) . '</td>';
                echo '<td>' . htmlspecialchars($row['stf']) . '</td>';
                echo '<td>' . htmlspecialchars($row['ma']) . '</td>';
                echo '<td>' . htmlspecialchars($row['atf']) . '</td>';
                echo '<td>' . htmlspecialchars($row['atm']) . '</td>';
                echo '<td>' . htmlspecialchars($row['wtf']) . '</td>';
                echo '<td>' . htmlspecialchars($row['wtm']) . '</td>';
                echo '<td>' . htmlspecialchars($row['praktikant']) . '</td>';
                echo '</tr>';
            }
            ?>
        </tbody>
    </table>
</body>
</html>
