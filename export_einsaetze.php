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

// Header für den Download setzen
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=einsaetze_{$monat}_{$jahr}.xls");

// XML-Daten beginnen
echo '<?xml version="1.0"?>';
echo '<?mso-application progid="Excel.Sheet"?>';
echo '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
               xmlns:x="urn:schemas-microsoft-com:office:excel"
               xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">';

echo '<Worksheet ss:Name="Einsätze">';
echo '<Table>';

// Spaltenüberschriften
echo '<Row>';
echo '<Cell><Data ss:Type="String">Interne Einsatznummer</Data></Cell>';
echo '<Cell><Data ss:Type="String">Einsatznummer</Data></Cell>';
echo '<Cell><Data ss:Type="String">Stichwort</Data></Cell>';
echo '<Cell><Data ss:Type="String">Alarmzeit</Data></Cell>';
echo '<Cell><Data ss:Type="String">Zurückzeit</Data></Cell>';
echo '<Cell><Data ss:Type="String">Fahrzeug</Data></Cell>';
echo '<Cell><Data ss:Type="String">Adresse</Data></Cell>';
echo '<Cell><Data ss:Type="String">StF</Data></Cell>';
echo '<Cell><Data ss:Type="String">Ma</Data></Cell>';
echo '<Cell><Data ss:Type="String">AtF</Data></Cell>';
echo '<Cell><Data ss:Type="String">AtM</Data></Cell>';
echo '<Cell><Data ss:Type="String">WtF</Data></Cell>';
echo '<Cell><Data ss:Type="String">WtM</Data></Cell>';
echo '<Cell><Data ss:Type="String">Praktikant</Data></Cell>';
echo '</Row>';

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

// Datenzeilen schreiben
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo '<Row>';
    foreach ($row as $cell) {
        echo '<Cell><Data ss:Type="String">' . htmlspecialchars($cell) . '</Data></Cell>';
    }
    echo '</Row>';
}

// XML-Daten beenden
echo '</Table>';
echo '</Worksheet>';
echo '</Workbook>';
exit;
?>
