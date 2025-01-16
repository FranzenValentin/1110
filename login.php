<?php
session_start();

// Zeit in Sekunden für die Inaktivität (5 Minuten = 300 Sekunden)
define('SESSION_TIMEOUT', 300);

// Überprüfen, ob der Benutzer angemeldet ist
if (isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true) {
    // Überprüfen, ob die Zeit der letzten Aktivität gesetzt ist
    if (isset($_SESSION['last_activity'])) {
        $inactivityDuration = time() - $_SESSION['last_activity'];

        // Wenn die Inaktivitätsdauer größer als SESSION_TIMEOUT ist, abmelden
        if ($inactivityDuration > SESSION_TIMEOUT) {
            // Sitzung zerstören
            session_unset();
            session_destroy();

            // Weiterleitung zur Login-Seite
            header("Location: login.php?timeout=1");
            exit;
        }
    }

    // Zeit der letzten Aktivität aktualisieren
    $_SESSION['last_activity'] = time();
}


try {
    loadEnv(__DIR__ . '/../config.env');
} catch (Exception $e) {
    echo "Fehler: " . $e->getMessage();
}

// Definiere den Zugangscode
define('ACCESS_CODE', $_ENV['app.access_code']);


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = $_POST['access_code'];
    if ($code === ACCESS_CODE) {
        $_SESSION['authenticated'] = true;
        header('Location: index.php'); // Weiterleitung zur geschützten Seite
        exit;
    } else {
        $error = "Falscher Zugangscode.";
    }
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



?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header>
        <h1>Login</h1>
    </header>
    <main>
        <form method="POST" class="login-form">
            <label for="access_code">Zugangscode:</label>
            <input 
                type="password" 
                id="access_code" 
                name="access_code" 
                inputmode="numeric" 
                pattern="[0-9]*" 
                required>
            <button type="submit">Anmelden</button>
            <?php if (isset($error)) { echo "<p class='error'>$error</p>"; } 
            
            if (isset($_GET['timeout']) && $_GET['timeout'] == 1) {
                echo "<p class='error'>Sie wurden wegen Inaktivität abgemeldet. Bitte melden Sie sich erneut an.</p>";
            }
            
            ?>
            
        </form>
    </main>
</body>
</html>
