<?php
try {
    $db = new PDO('mysql:host=localhost;dbname=deine_datenbank', 'benutzername', 'passwort', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    echo "Datenbankverbindung erfolgreich.";
} catch (PDOException $e) {
    die("Fehler bei der Datenbankverbindung: " . $e->getMessage());
}
