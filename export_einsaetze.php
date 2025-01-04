<?php
session_start();
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header('Location: login.php');
    exit;
}

require 'db.php';

// Monat und Jahr aus dem Formular abrufen
$monat = $_POST['monat'];
$jahr = $_POST['jahr'];

// Header für den Excel-Export
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=FF1110_einsaetze_{$monat}_{$jahr}.xls");

// Start der Excel-Datei (HTML-basierte Tabelle)
echo '<html xmlns:x="urn:schemas-microsoft-com:office:excel">';
echo '<head>';
echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
echo '<style>
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid black; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
      </style>';
echo '</head>';
echo '<body>';
echo "<h1>FF 1110 - Einsatzübersicht - $monat/$jahr</h1>";
echo '<table>';
echo '<tr>
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
      </tr>';

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
    LEFT JOIN besatzung b ON e.dienst_id = b.id
    LEFT JOIN personal p1 ON b.stf_id = p1.id
    LEFT JOIN personal p2 ON b.ma_id = p2.id
    LEFT JOIN personal p3 ON b.atf_id = p3.id
    LEFT JOIN personal p4 ON b.atm_id = p4.id
    LEFT JOIN personal p5 ON b.wtf_id = p5.id
    LEFT JOIN personal p6 ON b.wtm_id = p6.id
    LEFT JOIN personal p7 ON b.prakt_id = p7.id
    WHERE 
        MONTH(STR_TO_DATE(e.alarmuhrzeit, '%d.%m.%y %H:%i')) = :monat 
        AND YEAR(STR_TO_DATE(e.alarmuhrzeit, '%d.%m.%y %H:%i')) = :jahr
    ORDER BY e.id DESC
";

$stmt = $pdo->prepare($query);
$stmt->execute(['monat' => $monat, 'jahr' => $jahr]);

// Datenzeilen einfügen
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

echo '</table>';
echo '</body>';
echo '</html>';
