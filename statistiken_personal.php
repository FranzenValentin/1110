<?php
session_start();
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header('Location: login.php');
    exit;
}
require 'db.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Standardwerte für den Zeitraum
$currentDate = new DateTime();
$startdatum = $_GET['startdatum'] ?? $currentDate->format('Y-m-01');
$enddatum = $_GET['enddatum'] ?? $currentDate->format('Y-m-t');
$personId = $_GET['person_id'] ?? null;

// Variablen initialisieren
$personal = [];
$einsaetze = [];
$funktionenVerteilung = [];
$kategorien = [];
$gesamtAnzahl = 0;

// Personal laden
try {
    $personalStmt = $pdo->query("SELECT id, CONCAT(vorname, ' ', nachname) AS name FROM Personal ORDER BY nachname");
    $personal = $personalStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Fehler beim Laden des Personals: " . $e->getMessage());
}

// Abfragen nur bei ausgewählter Person ausführen
if ($personId) {
    try {
        // Einsätze laden
        $einsaetzeStmt = $pdo->prepare("
            SELECT e.interne_einsatznummer, e.stichwort, e.alarmuhrzeit, e.fahrzeug_name,
            CASE
                WHEN b.stf_id = :personId THEN 'Staffel-Führer'
                WHEN b.ma_id = :personId THEN 'Maschinist'
                WHEN b.atf_id = :personId OR b.atm_id = :personId THEN 'Angriffstrupp'
                WHEN b.wtf_id = :personId OR b.wtm_id = :personId THEN 'Wassertrupp'
                WHEN b.prakt_id = :personId THEN 'Praktikant'
                ELSE 'Unbekannt'
            END AS funktion
            FROM Einsaetze e
            LEFT JOIN Besatzung b ON e.besatzung_id = b.id
            WHERE :personId IN (b.stf_id, b.ma_id, b.atf_id, b.atm_id, b.wtf_id, b.wtm_id, b.prakt_id)
              AND e.alarmuhrzeit BETWEEN :startdatum AND :enddatum
            ORDER BY e.alarmuhrzeit DESC
        ");
        $einsaetzeStmt->execute([':personId' => $personId, ':startdatum' => $startdatum, ':enddatum' => $enddatum]);
        $einsaetze = $einsaetzeStmt->fetchAll(PDO::FETCH_ASSOC);

        // Funktionenverteilung laden
        $funktionenStmt = $pdo->prepare("
            SELECT CASE
                WHEN b.stf_id = :personId THEN 'Staffel-Führer'
                WHEN b.ma_id = :personId THEN 'Maschinist'
                WHEN b.atf_id = :personId OR b.atm_id = :personId THEN 'Angriffstrupp'
                WHEN b.wtf_id = :personId OR b.wtm_id = :personId THEN 'Wassertrupp'
                WHEN b.prakt_id = :personId THEN 'Praktikant'
                ELSE 'Unbekannt'
            END AS funktion, COUNT(*) AS anzahl
            FROM Einsaetze e
            LEFT JOIN Besatzung b ON e.besatzung_id = b.id
            WHERE :personId IN (b.stf_id, b.ma_id, b.atf_id, b.atm_id, b.wtf_id, b.wtm_id, b.prakt_id)
              AND e.alarmuhrzeit BETWEEN :startdatum AND :enddatum
            GROUP BY funktion
        ");
        $funktionenStmt->execute([':personId' => $personId, ':startdatum' => $startdatum, ':enddatum' => $enddatum]);
        $funktionenVerteilung = $funktionenStmt->fetchAll(PDO::FETCH_ASSOC);

        // Kategorien laden
        $kategorienStmt = $pdo->prepare("
            SELECT e.stichwort, COUNT(*) AS anzahl
            FROM Einsaetze e
            LEFT JOIN Besatzung b ON e.besatzung_id = b.id
            WHERE :personId IN (b.stf_id, b.ma_id, b.atf_id, b.atm_id, b.wtf_id, b.wtm_id, b.prakt_id)
              AND e.alarmuhrzeit BETWEEN :startdatum AND :enddatum
            GROUP BY e.stichwort
        ");
        $kategorienStmt->execute([':personId' => $personId, ':startdatum' => $startdatum, ':enddatum' => $enddatum]);
        $kategorien = $kategorienStmt->fetchAll(PDO::FETCH_ASSOC);

        // Gesamtanzahl laden
        $gesamtAnzahlStmt = $pdo->prepare("
            SELECT COUNT(*) AS gesamtanzahl
            FROM Einsaetze e
            LEFT JOIN Besatzung b ON e.besatzung_id = b.id
            WHERE :personId IN (b.stf_id, b.ma_id, b.atf_id, b.atm_id, b.wtf_id, b.wtm_id, b.prakt_id)
              AND e.alarmuhrzeit BETWEEN :startdatum AND :enddatum
        ");
        $gesamtAnzahlStmt->execute([':personId' => $personId, ':startdatum' => $startdatum, ':enddatum' => $enddatum]);
        $gesamtAnzahl = $gesamtAnzahlStmt->fetchColumn();
    } catch (PDOException $e) {
        die("Fehler bei den Abfragen: " . $e->getMessage());
    }
}
