<?php
session_start();
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header('Location: login.php'); // Weiterleitung zur Login-Seite
    exit;
}
require 'db.php';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=einsaetze_export.csv');

// CSV-Ausgabe starten
$output = fopen('php://output', 'w');

// Spaltenüberschriften schreiben
fputcsv($output, ['Interne Einsatznummer', 'Einsatznummer', 'Stichwort', 'Alarmzeit', 'Zurückzeit', 'Fahrzeug', 'Adresse', 'Personal']);

// Datenbankabfrage für Einsätze
$query = "
    SELECT e.interne_einsatznummer, e.einsatznummer_lts, e.stichwort, e.alarmuhrzeit, e.zurueckzeit, e.fahrzeug_name, e.adresse,
        CONCAT_WS('; ',
            CONCAT('StF: ', COALESCE(p1.nachname, '')),
            CONCAT('Ma: ', COALESCE(p2.nachname, '')),
            CONCAT('AtF: ', COALESCE(p3.nachname, '')),
            CONCAT('AtM: ', COALESCE(p4.nachname, '')),
            CONCAT('WtF: ', COALESCE(p5.nachname, '')),
            CONCAT('WtM: ', COALESCE(p6.nachname, '')),
            CONCAT('Prakt: ', COALESCE(p7.nachname, ''))
        ) AS personal
    FROM Einsaetze e
    LEFT JOIN Besatzung b ON e.besatzung_id = b.id
    LEFT JOIN Personal p1 ON b.stf_id = p1.id
    LEFT JOIN Personal p2 ON b.ma_id = p2.id
    LEFT JOIN Personal p3 ON b.atf_id = p3.id
    LEFT JOIN Personal p4 ON b.atm_id = p4.id
    LEFT JOIN Personal p5 ON b.wtf_id = p5.id
    LEFT JOIN Personal p6 ON b.wtm_id = p6.id
    LEFT JOIN Personal p7 ON b.prakt_id = p7.id
    ORDER BY e.id DESC
";
$stmt = $pdo->query($query);

// Daten in CSV schreiben
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    fputcsv($output, $row);
}

// Stream schließen
fclose($output);
exit;
?>
