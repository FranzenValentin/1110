<?php
session_start();
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header('Location: login.php');
    exit;
}
require 'db.php';

// Standardwerte für Zeitraum
if (!isset($_GET['startdatum']) || !isset($_GET['enddatum'])) {
    $currentDate = new DateTime();
    $startdatum = $currentDate->format('Y-m-01');
    $enddatum = $currentDate->format('Y-m-t');
} else {
    $startdatum = $_GET['startdatum'];
    $enddatum = $_GET['enddatum'];
}
$personId = isset($_GET['person_id']) ? $_GET['person_id'] : null;

// Datumsformat für SQL-Abfragen anpassen
$startdatum = (new DateTime($startdatum))->format('d.m.Y 00:00');
$enddatum = (new DateTime($enddatum))->format('d.m.Y 23:59');

// Debugging
echo "<pre>";
echo "Startdatum: $startdatum\n";
echo "Enddatum: $enddatum\n";
echo "Person ID: $personId\n";
echo "</pre>";

// Personal laden
$personalStmt = $pdo->query("SELECT id, CONCAT(vorname, ' ', nachname) AS name FROM personal ORDER BY nachname");
$personal = $personalStmt->fetchAll(PDO::FETCH_ASSOC);

// Einsätze und Funktionen laden
$einsaetze = [];
$funktionenVerteilung = [];
$totalEinsaetze = 0;
$personEinsaetze = 0;
$gesamtDauer = 0;

if ($personId) {
    // Einsätze abrufen
    $einsaetzeStmt = $pdo->prepare("
        SELECT 
            e.interne_einsatznummer, e.stichwort, e.alarmuhrzeit, e.fahrzeug_name,
            CASE
                WHEN b.stf_id = :personId THEN 'Staffel-Führer'
                WHEN b.ma_id = :personId THEN 'Maschinist'
                WHEN b.atf_id = :personId THEN 'Angriffstrupp-Führer'
                WHEN b.atm_id = :personId THEN 'Angriffstrupp-Mann'
                WHEN b.wtf_id = :personId THEN 'Wassertrupp-Führer'
                WHEN b.wtm_id = :personId THEN 'Wassertrupp-Mann'
                WHEN b.prakt_id = :personId THEN 'Praktikant'
                ELSE 'Unbekannt'
            END AS funktion
        FROM einsaetze e
        LEFT JOIN dienste b ON e.dienst_id = b.id
        WHERE :personId IN (b.stf_id, b.ma_id, b.atf_id, b.atm_id, b.wtf_id, b.wtm_id, b.prakt_id)
        AND STR_TO_DATE(e.alarmuhrzeit, '%d.%m.%Y %H:%i') BETWEEN STR_TO_DATE(:startdatum, '%d.%m.%Y %H:%i') AND STR_TO_DATE(:enddatum, '%d.%m.%Y %H:%i')
        ORDER BY STR_TO_DATE(e.alarmuhrzeit, '%d.%m.%Y %H:%i') DESC
    ");

    $einsaetzeStmt->execute([
        ':personId' => $personId,
        ':startdatum' => $startdatum,
        ':enddatum' => $enddatum
    ]);
    $einsaetze = $einsaetzeStmt->fetchAll(PDO::FETCH_ASSOC);

    // Verteilung der Funktionen abrufen
    $funktionenStmt = $pdo->prepare("
        SELECT 
            CASE
                WHEN b.stf_id = :personId THEN 'Staffel-Führer'
                WHEN b.ma_id = :personId THEN 'Maschinist'
                WHEN b.atf_id = :personId OR b.atm_id = :personId THEN 'Angriffstrupp'
                WHEN b.wtf_id = :personId OR b.wtm_id = :personId THEN 'Wassertrupp'
                WHEN b.prakt_id = :personId THEN 'Praktikant'
                ELSE 'Unbekannt'
            END AS funktion,
            COUNT(*) AS anzahl
        FROM einsaetze e
        LEFT JOIN dienste b ON e.dienst_id = b.id
        WHERE :personId IN (b.stf_id, b.ma_id, b.atf_id, b.atm_id, b.wtf_id, b.wtm_id, b.prakt_id)
        AND STR_TO_DATE(e.alarmuhrzeit, '%d.%m.%Y %H:%i') BETWEEN STR_TO_DATE(:startdatum, '%d.%m.%Y %H:%i') AND STR_TO_DATE(:enddatum, '%d.%m.%Y %H:%i')
        GROUP BY funktion
    ");

    $funktionenStmt->execute([
        ':personId' => $personId,
        ':startdatum' => $startdatum,
        ':enddatum' => $enddatum
    ]);
    $funktionenVerteilung = $funktionenStmt->fetchAll(PDO::FETCH_ASSOC);

    // Gesamtanzahl der Einsätze abrufen
    $totalEinsaetzeStmt = $pdo->prepare("
        SELECT COUNT(*) AS total
        FROM einsaetze e
        WHERE STR_TO_DATE(e.alarmuhrzeit, '%d.%m.%Y %H:%i') BETWEEN STR_TO_DATE(:startdatum, '%d.%m.%Y %H:%i') AND STR_TO_DATE(:enddatum, '%d.%m.%Y %H:%i')
    ");

    $totalEinsaetzeStmt->execute([
        ':startdatum' => $startdatum,
        ':enddatum' => $enddatum
    ]);
    $totalEinsaetze = $totalEinsaetzeStmt->fetchColumn();

    // Einsätze der Person zählen
    $personEinsaetzeStmt = $pdo->prepare("
        SELECT COUNT(*) AS total
        FROM einsaetze e
        LEFT JOIN dienste b ON e.dienst_id = b.id
        WHERE :personId IN (b.stf_id, b.ma_id, b.atf_id, b.atm_id, b.wtf_id, b.wtm_id, b.prakt_id)
        AND STR_TO_DATE(e.alarmuhrzeit, '%d.%m.%Y %H:%i') BETWEEN STR_TO_DATE(:startdatum, '%d.%m.%Y %H:%i') AND STR_TO_DATE(:enddatum, '%d.%m.%Y %H:%i')
    ");

    $personEinsaetzeStmt->execute([
        ':personId' => $personId,
        ':startdatum' => $startdatum,
        ':enddatum' => $enddatum
    ]);
    $personEinsaetze = $personEinsaetzeStmt->fetchColumn();

    // Gesamtdauer der Einsätze berechnen
    $einsatzdauerStmt = $pdo->prepare("
        SELECT SUM(TIMESTAMPDIFF(MINUTE, 
            STR_TO_DATE(e.alarmuhrzeit, '%d.%m.%Y %H:%i'), 
            STR_TO_DATE(e.zurueckzeit, '%d.%m.%Y %H:%i')
        )) AS gesamtDauer
        FROM einsaetze e
        LEFT JOIN dienste b ON e.dienst_id = b.id
        WHERE :personId IN (b.stf_id, b.ma_id, b.atf_id, b.atm_id, b.wtf_id, b.wtm_id, b.prakt_id)
        AND STR_TO_DATE(e.alarmuhrzeit, '%d.%m.%Y %H:%i') BETWEEN STR_TO_DATE(:startdatum, '%d.%m.%Y %H:%i') AND STR_TO_DATE(:enddatum, '%d.%m.%Y %H:%i')
    ");

    $einsatzdauerStmt->execute([
        ':personId' => $personId,
        ':startdatum' => $startdatum,
        ':enddatum' => $enddatum
    ]);
    $gesamtDauer = $einsatzdauerStmt->fetchColumn();
    $stunden = floor($gesamtDauer / 60);
    $minuten = $gesamtDauer % 60;
}

// Debugging-Ausgabe
echo "<pre>";
print_r($einsaetze);
echo "</pre>";
