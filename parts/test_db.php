<?php
session_start();
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header('Location: ../login.php');
    exit("Zugriff verweigert. Bitte melden Sie sich an.");
}

// Funktion, um die .env-Datei zu laden
function loadEnv($filePath)
{
    if (!file_exists($filePath)) {
        throw new Exception("Die Datei $filePath wurde nicht gefunden.");
    }

    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        // Kommentare ignorieren
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        // Zeilen in Schlüssel-Wert-Paare aufteilen
        $parts = explode('=', $line, 2);

        if (count($parts) == 2) {
            $key = trim($parts[0]);
            $value = trim($parts[1]);

            // Entferne Anführungszeichen, falls vorhanden
            $value = trim($value, '"\'');
            
            // Speichere die Variable in $_ENV und $_SERVER
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }
}

try {
    // Lade die .env-Datei
    loadEnv(__DIR__ . '/../config.env');
} catch (Exception $e) {
    die("Fehler beim Laden der .env-Datei: " . $e->getMessage());
}

// Datenbankverbindung testen
$host = 'localhost:3306'; // Hostname des Servers
$dbname = '1110'; // Name der Datenbank
$username = 'web126'; // Benutzername
$password = $_ENV['db.password'] ?? null; // Passwort

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Datenbankverbindung erfolgreich!";
} catch (PDOException $e) {
    die("Datenbankverbindung fehlgeschlagen: " . $e->getMessage());
}
?>
