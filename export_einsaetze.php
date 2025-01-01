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

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=einsaetze_' . $monat . '_' . $jahr . '.csv');

// CSV-Ausgabe starten
$output = fopen('php://output', 'w');

// Spaltenüberschriften schreiben
fputcsv($output, [
    'Interne Einsatznummer',
    'Einsatznummer',
    'Stichwort',
    'Alarmzeit',
    'Zurückzeit',
    'Fahrzeug',
    'Adresse',
    'StF',
    'Ma',
    'AtF',
    'AtM',
    'WtF',
    'WtM',
    'Praktikant'
]);

// Datenbankabfrage für Einsätze im angegebenen Monat und Jahr
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
    WHERE 
        MONTH(STR_TO_DATE(e.alarmuhrzeit, '%d.%m.%y %H:%i')) = :monat 
        AND YEAR(STR_TO_DATE(e.alarmuhrzeit, '%d.%m.%y %H:%i')) = :jahr
    ORDER BY e.id DESC
";


$stmt = $pdo->prepare($query);
$stmt->execute(['monat' => $monat, 'jahr' => $jahr]);

// Daten in CSV schreiben
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    fputcsv($output, $row);
}

// Stream schließen
fclose($output);
exit;
?>
