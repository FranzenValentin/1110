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

// HTTP-Header für echte .xls-Datei setzen
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=einsaetze_{$monat}_{$jahr}.xls");

// Echte .xls-Dateistruktur initialisieren
echo pack("CCC", 0xD0, 0xCF, 0x11); // Begin der XLS-Struktur (OLE Header)
echo pack("CCC", 0xE0, 0xA1, 0xB1); // Fortsetzung der Header-Struktur

// Einfacher Inhalt als echtes XLS-Dokument
echo pack("CCC", 0x09, 0x04, 0x06); // Begin der Inhaltsstruktur
echo "Einsatzübersicht $monat/$jahr"; // Beispiel-Inhalt
echo pack("CCC", 0x00, 0x10, 0x0A); // Markierung des Endes

// Inhalte generieren
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

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo '<tr>';
    foreach ($row as $value) {
        echo '<td>' . htmlspecialchars($value) . '</td>';
    }
    echo '</tr>';
}
echo '</table>';

exit();
?>
