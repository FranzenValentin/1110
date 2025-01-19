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

// Benutzerliste abrufen
$users = [];
$lastLoggedUser = $_SESSION['last_user'] ?? null;

try {
    $stmt = $pdo->query("SELECT nachname, vorname FROM personal ORDER BY nachname, vorname");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$lastLoggedUser && !empty($users)) {
        $lastLoggedUser = $users[0]['nachname'] . ' ' . $users[0]['vorname'];
    }
} catch (PDOException $e) {
    die("Fehler beim Abrufen der Benutzerdaten: " . $e->getMessage());
}

// Prüfen, ob der Benutzer einen Login-Versuch unternommen hat
$error = null;
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
    <link rel="stylesheet" href="styles.css">
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const usernameInput = document.getElementById("username");

            // Beim Klicken in das Eingabefeld wird der Text gelöscht
            usernameInput.addEventListener("focus", function () {
                usernameInput.value = "";
            });

            // Setze den letzten Benutzer, falls vorhanden
            const lastUser = "<?= htmlspecialchars($lastLoggedUser ?? '') ?>";
            if (lastUser) {
                usernameInput.value = lastUser;
            }
        });
    </script>
</head>
<body>
    <header>
        <h1>Login</h1>
    </header>
    <main>
        <form method="POST" class="login-form">
            <label for="username">Benutzername:</label>
            <input 
                list="usernames" 
                id="username" 
                name="username" 
                placeholder="Nachname Vorname" 
                required>
            <datalist id="usernames">
                <?php foreach ($users as $user): 
                    $fullName = htmlspecialchars($user['nachname'] . ' ' . $user['vorname']); ?>
                    <option value="<?= $fullName ?>"></option>
                <?php endforeach; ?>
            </datalist>

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
    </main>
</body>
</html>
