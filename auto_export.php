<?php
session_start();
require 'parts/db.php';

try {
    loadEnv(__DIR__ . '/../config.env');
} catch (Exception $e) {
    echo "Fehler: " . $e->getMessage();
}

// Mail password aus ENV laden
$mailpassword = $_ENV['mail.password']; // Mail Password

// Autoload für PhpSpreadsheet und PHPMailer
$autoloadPath = "../vendor/autoload.php";
if (!file_exists($autoloadPath)) {
    die("Autoload.php wurde nicht gefunden: $autoloadPath");
}
require $autoloadPath;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Debugging aktivieren
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Monat und Jahr definieren (Cronjob: Automatisch aktuelles Datum)
$monat = date('m', strtotime('last day of last month'));
$jahr = date('Y', strtotime('last day of last month'));

// Neues Spreadsheet erstellen
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setCellValue('A1', "FF 1110 - Einsatzübersicht - $monat/$jahr");
$sheet->mergeCells('A1:O1');
$sheet->getStyle('A1')->applyFromArray([
    'font' => ['bold' => true, 'size' => 16, 'color' => ['argb' => '000000']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
]);

// Header für die Tabelle
$headers = [
    'A2' => 'Interne Einsatznummer', 'B2' => 'Einsatznummer LTS', 'C2' => 'Stichwort',
    'D2' => 'Alarmzeit', 'E2' => 'Zurückzeit', 'F2' => 'Fahrzeug', 'G2' => 'Adresse',
    'H2' => 'Stadtteil', 'I2' => 'StF', 'J2' => 'Ma', 'K2' => 'AtF', 'L2' => 'AtM',
    'M2' => 'WtF', 'N2' => 'WtM', 'O2' => 'Praktikant',
];

foreach ($headers as $cell => $headerText) {
    $sheet->setCellValue($cell, $headerText);
}

// Tabellenüberschrift designen
$sheet->getStyle('A2:O2')->applyFromArray([
    'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFF']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => '4CAF50']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
]);

// Datenbankabfrage
$query = "
    SELECT 
        e.interne_einsatznummer, e.einsatznummer_lts, e.stichwort, 
        e.alarmuhrzeit, e.zurueckzeit, e.fahrzeug_name, e.adresse, e.stadtteil,
        p1.nachname AS stf, p2.nachname AS ma, p3.nachname AS atf, p4.nachname AS atm,
        p5.nachname AS wtf, p6.nachname AS wtm, p7.nachname AS praktikant
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
$stmt->execute(['monat' => $monat, 'jahr' => $jahr]);

if ($stmt->rowCount() === 0) {
    die("Keine Daten gefunden für Monat: $monat und Jahr: $jahr.");
}

$rowIndex = 3;
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $sheet->fromArray(array_values($row), NULL, "A$rowIndex");
    $rowIndex++;
}

// Datei lokal speichern
$exportPath = __DIR__ . "/FF1110_einsaetze_{$monat}_{$jahr}.xlsx";
$writer = new Xlsx($spreadsheet);
$writer->save($exportPath);

// E-Mail mit PHPMailer versenden
$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host = 'smtp.ffmitte.de'; // SMTP-Server
    $mail->SMTPAuth = true;
    $mail->Username = 'portal@einsaetze.ffmitte.de'; // Benutzername
    $mail->Password = $mailpassword;    // Passwort
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port = 465;

    $mail->setFrom('portal@einsaetze.ffmitte.de', 'Feuerwehr Einsätze');
    $mail->addAddress('valentinfranzen@web.de');
    //$mail->addAddress('recipient2@example.com'); // Weitere Empfänger

    $mail->isHTML(true);
    $mail->Subject = "Automatischer Export - Einsätze $monat/$jahr";
    $mail->Body = "Im Anhang finden Sie den Export der Einsätze für $monat/$jahr.";
    $mail->addAttachment($exportPath);

    $mail->send();
    echo "E-Mail erfolgreich gesendet.";
} catch (Exception $e) {
    echo "E-Mail konnte nicht gesendet werden. Fehler: {$mail->ErrorInfo}";
}

// Temporäre Datei löschen
unlink($exportPath);
?>
