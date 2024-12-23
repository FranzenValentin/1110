<?php
// Datenbankverbindung
$host = 'localhost:3306'; // Hostname des Servers
$dbname = 'web126_1110'; // Name der Datenbank
$username = 'web126'; // Benutzername
$password = 'vajbin-rogje8-joZkic'; // Passwort

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Datenbankverbindung fehlgeschlagen: " . $e->getMessage());
}
?>
