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
    LEFT JOIN dienste d ON e.dienst_id = d.id
    LEFT JOIN personal p1 ON d.stf_id = p1.id
    LEFT JOIN personal p2 ON d.ma_id = p2.id
    LEFT JOIN personal p3 ON d.atf_id = p3.id
    LEFT JOIN personal p4 ON d.atm_id = p4.id
    LEFT JOIN personal p5 ON d.wtf_id = p5.id
    LEFT JOIN personal p6 ON d.wtm_id = p6.id
    LEFT JOIN personal p7 ON d.prakt_id = p7.id
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
    $sheet->setCellValue("I$rowIndex", $row['stf'] ?? 'N/A');
    $sheet->setCellValue("J$rowIndex", $row['ma'] ?? 'N/A');
    $sheet->setCellValue("K$rowIndex", $row['atf'] ?? 'N/A');
    $sheet->setCellValue("L$rowIndex", $row['atm'] ?? 'N/A');
    $sheet->setCellValue("M$rowIndex", $row['wtf'] ?? 'N/A');
    $sheet->setCellValue("N$rowIndex", $row['wtm'] ?? 'N/A');
    $sheet->setCellValue("O$rowIndex", $row['praktikant'] ?? 'N/A');
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

// Spaltenbreite automatisch anpassen
foreach (range('A', 'O') as $columnID) {
    $sheet->getColumnDimension($columnID)->setAutoSize(true);
}

// Überschrift zentrieren und fett machen
$sheet->mergeCells('A1:O1');
$sheet->getStyle('A1')->applyFromArray([
    'font' => [
        'bold' => true,
        'size' => 16,
    ],
    'alignment' => [
        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
    ],
]);

// Tabellenüberschrift formatieren
$sheet->getStyle('A2:O2')->applyFromArray([
    'font' => [
        'bold' => true,
        'color' => ['argb' => 'FFFFFF'], // Weiß
    ],
    'fill' => [
        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
        'startColor' => ['argb' => '4CAF50'], // Grün
    ],
    'alignment' => [
        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
    ],
]);

// Rahmen hinzufügen
$sheet->getStyle('A2:O100')->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

?>
