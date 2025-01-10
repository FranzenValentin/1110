<?php
session_start();
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header('Location: login.php');
    exit;
}

require 'db.php';

$autoloadPath = "/../vendor/autoload.php"; // Anpassen des Pfades bei Bedarf
if (!file_exists($autoloadPath)) {
    die("Autoload.php nicht gefunden unter: $autoloadPath");
}
require $autoloadPath;


use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Debugging aktivieren
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Monat und Jahr aus dem Formular abrufen
$monat = $_POST['monat'] ?? 10; // Fallback für Testzwecke
$jahr = $_POST['jahr'] ?? 2024;

// Neues Spreadsheet erstellen
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Titelzeile setzen
$sheet->setCellValue('A1', "FF 1110 - Einsatzübersicht - $monat/$jahr");

// Header für die Tabelle
$headers = [
    'A2' => 'Interne Einsatznummer',
    'B2' => 'Einsatznummer',
    'C2' => 'Stichwort',
    'D2' => 'Alarmzeit',
    'E2' => 'Zurückzeit',
    'F2' => 'Fahrzeug',
    'G2' => 'Adresse',
    'H2' => 'Stadtteil',
    'I2' => 'StF',
    'J2' => 'Ma',
    'K2' => 'AtF',
    'L2' => 'AtM',
    'M2' => 'WtF',
    'N2' => 'WtM',
    'O2' => 'Praktikant',
];

foreach ($headers as $cell => $headerText) {
    $sheet->setCellValue($cell, $headerText);
}

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
        e.stadtteil,
        p1.nachname AS stf,
        p2.nachname AS ma,
        p3.nachname AS atf,
        p4.nachname AS atm,
        p5.nachname AS wtf,
        p6.nachname AS wtm,
        p7.nachname AS praktikant
    FROM einsaetze e
    LEFT JOIN dienste b ON e.dienst_id = b.id
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
$rowIndex = 3; // Startreihe nach Header
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $sheet->setCellValue("A$rowIndex", $row['interne_einsatznummer']);
    $sheet->setCellValue("B$rowIndex", $row['einsatznummer_lts']);
    $sheet->setCellValue("C$rowIndex", $row['stichwort']);
    $sheet->setCellValue("D$rowIndex", $row['alarmuhrzeit']);
    $sheet->setCellValue("E$rowIndex", $row['zurueckzeit']);
    $sheet->setCellValue("F$rowIndex", $row['fahrzeug_name']);
    $sheet->setCellValue("G$rowIndex", $row['adresse']);
    $sheet->setCellValue("H$rowIndex", $row['stadtteil']);
    $sheet->setCellValue("I$rowIndex", $row['stf']);
    $sheet->setCellValue("J$rowIndex", $row['ma']);
    $sheet->setCellValue("K$rowIndex", $row['atf']);
    $sheet->setCellValue("L$rowIndex", $row['atm']);
    $sheet->setCellValue("M$rowIndex", $row['wtf']);
    $sheet->setCellValue("N$rowIndex", $row['wtm']);
    $sheet->setCellValue("O$rowIndex", $row['praktikant']);
    $rowIndex++;
}

// Kopfzeile für den Download
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=FF1110_einsaetze_{$monat}_{$jahr}.xlsx");
header('Cache-Control: max-age=0');

// Datei schreiben und ausgeben
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>
