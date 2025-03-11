<?php
try {
    loadEnv(__DIR__ . '/../../config.env');
} catch (Exception $e) {
    echo "Fehler: " . $e->getMessage();
}

// Datenbankverbindung
$host = "localhost:" . $_ENV['db.port']; // Hostname des Servers
$dbname = 'kantine'; // Name der Datenbank
$username = 'web127'; // Benutzername
$password = $_ENV['db.password']; // Passwort

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Fehler bei der Verbindung zur Datenbank: " . $e->getMessage());
}
?>
