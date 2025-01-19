<?php
session_start();
require 'db.php'; // Verbindung zur Datenbank herstellen

// Zeit in Sekunden für die Inaktivität (5 Minuten = 300 Sekunden)
define('SESSION_TIMEOUT', 300);

// Überprüfen, ob die Datenbankverbindung existiert
if (!$pdo) {
    die("Datenbankverbindung fehlgeschlagen.");
}

// Benutzerliste abrufen
$users = [];
$lastLoggedUser = $_SESSION['last_user'] ?? ($_COOKIE['last_user'] ?? null);

try {
    $stmt = $pdo->query("SELECT nachname, vorname FROM personal ORDER BY nachname, vorname");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$lastLoggedUser && !empty($users)) {
        $lastLoggedUser = $users[0]['nachname'] . ' ' . $users[0]['vorname'];
    }
} catch (PDOException $e) {
    die("Fehler beim Abrufen der Benutzerdaten: " . $e->getMessage());
}

// Prüfen, ob der Benutzer angemeldet ist
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $inputCode = trim($_POST['access_code']);
    $deviceInfo = $_SERVER['HTTP_USER_AGENT'];
    $logTime = date('Y-m-d H:i:s');

    try {
        // Benutzername aufteilen in Nachname und Vorname
        $nameParts = explode(' ', $username, 2);
        if (count($nameParts) < 2) {
            $error = "Ungültiger Benutzername. Bitte 'Nachname Vorname' eingeben.";
        } else {
            [$nachname, $vorname] = $nameParts;

            // Datenbankabfrage ausführen
            $stmt = $pdo->prepare("SELECT code FROM personal WHERE nachname = :nachname AND vorname = :vorname");
            $stmt->execute(['nachname' => $nachname, 'vorname' => $vorname]);
            $dbCode = $stmt->fetchColumn();

            if ($dbCode && $dbCode == $inputCode) {
                // Login erfolgreich
                $_SESSION['authenticated'] = true;
                $_SESSION['last_user'] = $username;

                // Letzten Benutzer im Cookie speichern
                setcookie('last_user', $username, time() + (86400 * 30), '/');

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
    <form method="POST" class="login-form" autocomplete="off">
            <label for="username">Benutzername:</label>
            <div style="position: relative;">
                <input 
                    type="text" 
                    id="username" 
                    name="username" 
                    placeholder="Nachname Vorname" 
                    required>
                <ul id="suggestions" class="suggestions"></ul>
            </div>

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
      
            <?php if (isset($_GET['timeout']) && $_GET['timeout'] == 1): ?>
                <p class='error'>Sie wurden wegen Inaktivität abgemeldet. Bitte melden Sie sich erneut an.</p>
            <?php endif; ?>
        </form>
    </main>

    <script>
        const usernameInput = document.getElementById("username");
        const suggestionsList = document.getElementById("suggestions");

        // Automatisches Scrollen bei Fokus
        document.addEventListener("focusin", (event) => {
            const target = event.target;

            if (target.id === "username" || target.id === "access_code") {
                setTimeout(() => {
                    const rect = target.getBoundingClientRect(); // Position relativ zum Sichtfenster
                    const scrollY = window.scrollY || document.documentElement.scrollTop; // Aktuelle Scroll-Position

                    // Zielposition berechnen: 10 Pixel oberhalb des Feldes
                    const targetPosition = rect.top + scrollY - 30;

                    // Scrollen zur berechneten Position
                    window.scrollTo({
                        top: targetPosition, // Zielscrollposition
                        behavior: "smooth", // Weiche Scrollanimation
                    });
                }, 300); // Kurze Verzögerung, damit die Tastatur erscheint
            }
        });

        // Event: Eingabe in das Benutzernamenfeld
        usernameInput.addEventListener("input", async () => {
            const query = usernameInput.value.trim();
            if (query.length < 2) {
                suggestionsList.innerHTML = ""; // Liste zurücksetzen, wenn zu wenig Zeichen
                return;
            }

            // AJAX-Anfrage an den Server senden
            const response = await fetch(`fetch_users.php?query=${encodeURIComponent(query)}`);
            const results = await response.json();

            // Vorschläge anzeigen
            suggestionsList.innerHTML = results
                .map(user => `<li>${user}</li>`)
                .join("");

            // Klick auf einen Vorschlag
            suggestionsList.querySelectorAll("li").forEach(item => {
                item.addEventListener("click", () => {
                    usernameInput.value = item.textContent; // Benutzername ins Feld einfügen
                    suggestionsList.innerHTML = ""; // Liste zurücksetzen
                    // Fokus auf das Zugangscodefeld setzen
                    const accessCodeInput = document.getElementById("access_code");
                    accessCodeInput.focus();    
                });
            });
        });

        // Liste schließen, wenn der Benutzer außerhalb klickt
        document.addEventListener("click", (e) => {
            if (!usernameInput.contains(e.target) && !suggestionsList.contains(e.target)) {
                suggestionsList.innerHTML = "";
            }
        });
    </script>
</body>
</html>
