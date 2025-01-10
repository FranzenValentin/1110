<?php
session_start();
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header('Location: login.php');
    exit;
}

require 'db.php';

$autoloadPath = "../vendor/autoload.php";
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
$monat = $_POST['monat'];
$jahr = $_POST['jahr'];

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
        e.stadtteil
    FROM einsaetze e
    WHERE 
        MONTH(STR_TO_DATE(e.alarmuhrzeit, '%d.%m.%Y %H:%i')) = :monat 
        AND YEAR(STR_TO_DATE(e.alarmuhrzeit, '%d.%m.%Y %H:%i')) = :jahr
";

$stmt = $pdo->prepare($query);
$params = ['monat' => (int)$monat, 'jahr' => (int)$jahr];
$stmt->execute($params);

if ($stmt->rowCount() === 0) {
    die("Keine Daten gefunden für Monat: $monat und Jahr: $jahr.");
}

// Datenzeilen einfügen
$rowIndex = 3;
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $sheet->setCellValue("A$rowIndex", $row['interne_einsatznummer']);
    $sheet->setCellValue("B$rowIndex", $row['einsatznummer_lts']);
    $sheet->setCellValue("C$rowIndex", $row['stichwort']);
    $sheet->setCellValue("D$rowIndex", $row['alarmuhrzeit']);
    $sheet->setCellValue("E$rowIndex", $row['zurueckzeit'] ?? 'N/A');
    $sheet->setCellValue("F$rowIndex", $row['fahrzeug_name'] ?? 'N/A');
    $sheet->setCellValue("G$rowIndex", $row['adresse'] ?? 'N/A');
    $sheet->setCellValue("H$rowIndex", $row['stadtteil'] ?? 'N/A');
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
