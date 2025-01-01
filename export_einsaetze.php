<?php
session_start();
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header('Location: login.php');
    exit;
}

require 'db.php'; // Datenbankverbindung
require 'vendor/autoload.php'; // PhpSpreadsheet (Composer) einbinden

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Monat und Jahr aus dem Formular abrufen
$monat = $_POST['monat'];
$jahr = $_POST['jahr'];

// Neues Spreadsheet erstellen
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle("Einsätze");

// Spaltenüberschriften
$sheet->setCellValue('A1', 'Interne Einsatznummer')
      ->setCellValue('B1', 'Einsatznummer')
      ->setCellValue('C1', 'Stichwort')
      ->setCellValue('D1', 'Alarmzeit')
      ->setCellValue('E1', 'Zurückzeit')
      ->setCellValue('F1', 'Fahrzeug')
      ->setCellValue('G1', 'Adresse')
      ->setCellValue('H1', 'StF')
      ->setCellValue('I1', 'Ma')
      ->setCellValue('J1', 'AtF')
      ->setCellValue('K1', 'AtM')
      ->setCellValue('L1', 'WtF')
      ->setCellValue('M1', 'WtM')
      ->setCellValue('N1', 'Praktikant');

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

// Daten in die Excel-Datei schreiben
$rowIndex = 2; // Start bei der zweiten Zeile (nach den Überschriften)
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $sheet->setCellValue("A$rowIndex", $row['interne_einsatznummer'])
          ->setCellValue("B$rowIndex", $row['einsatznummer_lts'])
          ->setCellValue("C$rowIndex", $row['stichwort'])
          ->setCellValue("D$rowIndex", $row['alarmuhrzeit'])
          ->setCellValue("E$rowIndex", $row['zurueckzeit'])
          ->setCellValue("F$rowIndex", $row['fahrzeug_name'])
          ->setCellValue("G$rowIndex", $row['adresse'])
          ->setCellValue("H$rowIndex", $row['stf'])
          ->setCellValue("I$rowIndex", $row['ma'])
          ->setCellValue("J$rowIndex", $row['atf'])
          ->setCellValue("K$rowIndex", $row['atm'])
          ->setCellValue("L$rowIndex", $row['wtf'])
          ->setCellValue("M$rowIndex", $row['wtm'])
          ->setCellValue("N$rowIndex", $row['praktikant']);
    $rowIndex++;
}

// Writer erstellen und Datei streamen
$writer = new Xlsx($spreadsheet);
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=einsaetze_{$monat}_{$jahr}.xlsx");
$writer->save('php://output');
exit;
?>
