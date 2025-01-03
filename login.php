<?php
session_start();

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
            <?php if (isset($error)) { echo "<p class='error'>$error</p>"; } ?>
            
        </form>
    </main>
</body>
</html>
