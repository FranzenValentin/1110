<?php
session_start();
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header('Location: login.php');
    exit;
}

require 'db.php';

$autoloadPath = "../vendor/autoload.php"; // Anpassen des Pfades bei Bedarf
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
        e.alarmuhrzeit
    FROM einsaetze e
    WHERE 
        MONTH(STR_TO_DATE(e.alarmuhrzeit, '%d.%m.%Y %H:%i')) = :monat 
        AND YEAR(STR_TO_DATE(e.alarmuhrzeit, '%d.%m.%Y %H:%i')) = :jahr
";

$stmt = $pdo->prepare($query);

// Stellen Sie sicher, dass beide Parameter übergeben werden
$params = ['monat' => (int)$monat, 'jahr' => (int)$jahr];
$stmt->execute($params);


if ($stmt->rowCount() === 0) {
    die("Keine Daten gefunden für Monat: $monat und Jahr: $jahr.");
}

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    print_r($row); // Debugging: Prüfe die Ergebnisse
}

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

echo "SQL Query: $query<br>";
echo "Parameter: " . json_encode($params) . "<br>";

if ($stmt->rowCount() === 0) {
    die("Keine Daten gefunden für Monat $monat und Jahr $jahr.");
}

?>
