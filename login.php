<?php
session_start();
require 'db.php'; // Verbindung zur Datenbank herstellen

// Zeit in Sekunden für die Inaktivität (5 Minuten = 300 Sekunden)
define('SESSION_TIMEOUT', 300);

// Überprüfen, ob die Datenbankverbindung existiert
if (!$pdo) {
    die("Datenbankverbindung fehlgeschlagen.");
}

// Überprüfen, ob der Benutzer angemeldet ist
if (isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true) {
    if (isset($_SESSION['last_activity'])) {
        $inactivityDuration = time() - $_SESSION['last_activity'];
        if ($inactivityDuration > SESSION_TIMEOUT) {
            session_unset();
            session_destroy();
            header("Location: login.php?timeout=1");
            exit;
        }
    }
    $_SESSION['last_activity'] = time();
}

$error = null;

// Prüfen, ob der Benutzer einen Login-Versuch unternommen hat
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $inputCode = trim($_POST['access_code']);
    $deviceInfo = $_SERVER['HTTP_USER_AGENT'];
    $logTime = date('Y-m-d H:i:s');

    try {
        $stmt = $pdo->prepare("SELECT code FROM personal WHERE CONCAT(nachname, ' ', vorname) = :username");
        $stmt->execute(['username' => $username]);
        $dbCode = $stmt->fetchColumn();

        if ($dbCode && $dbCode == $inputCode) {
            $_SESSION['authenticated'] = true;
            $_SESSION['last_user'] = $username;

            // Login erfolgreich protokollieren
            file_put_contents(
                __DIR__ . '/login_logs.txt',
                "Erfolgreicher Login | Benutzer: $username | Gerät: $deviceInfo | Zeit: $logTime" . PHP_EOL,
                FILE_APPEND
            );

            header('Location: index.php');
            exit;
        } else {
            $error = "Ungültiger Benutzername oder Zugangscode.";
            file_put_contents(
                __DIR__ . '/login_logs.txt',
                "Fehlgeschlagener Login | Benutzer: $username | Gerät: $deviceInfo | Zeit: $logTime" . PHP_EOL,
                FILE_APPEND
            );
        }
    } catch (PDOException $e) {
        die("Fehler beim Login: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-color: #f4f4f4;
        }
        .login-form {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            max-width: 400px;
            width: 100%;
        }
        .login-form h1 {
            font-size: 24px;
            margin-bottom: 20px;
            text-align: center;
        }
        .login-form label {
            font-size: 16px;
            display: block;
            margin-bottom: 5px;
        }
        .login-form input {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 16px;
        }
        .login-form button {
            width: 100%;
            padding: 10px;
            font-size: 18px;
            background: #007BFF;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .login-form button:hover {
            background: #0056b3;
        }
        .error {
            color: red;
            font-size: 14px;
            text-align: center;
        }
    </style>
</head>
<body>
    <form method="POST" class="login-form">
        <h1>Login</h1>
        <label for="username">Benutzername:</label>
        <input type="text" id="username" name="username" placeholder="Nachname Vorname" required>

        <label for="access_code">Zugangscode:</label>
        <input 
            type="password" 
            id="access_code" 
            name="access_code" 
            inputmode="numeric" 
            pattern="[0-9]*" 
            placeholder="Code eingeben" 
            required>

        <button type="submit">Anmelden</button>

        <?php if ($error): ?>
            <p class='error'><?= $error ?></p>
        <?php endif; ?>
        
        <?php if (isset($_GET['timeout']) && $_GET['timeout'] == 1): ?>
            <p class='error'>Sie wurden wegen Inaktivität abgemeldet. Bitte melden Sie sich erneut an.</p>
        <?php endif; ?>
    </form>
</body>
</html>
