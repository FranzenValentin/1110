<?php
session_start();

// Maximale Inaktivitätszeit in Sekunden
define('SESSION_TIMEOUT', 300);

// Prüfe den Session-Status für Server-seitige Inaktivitätsverwaltung
if (isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true) {
    if (isset($_SESSION['last_activity'])) {
        $inactivityDuration = time() - $_SESSION['last_activity'];
        if ($inactivityDuration > SESSION_TIMEOUT) {
            session_unset();
            session_destroy();
            header("Content-Type: application/json");
            echo json_encode(['status' => 'inactive']);
            exit;
        }
    }
    // Aktualisiere die Zeit der letzten Aktivität
    $_SESSION['last_activity'] = time();
} elseif (isset($_GET['check_status'])) {
    // Wenn der Benutzer nicht angemeldet ist und der Status über AJAX geprüft wird
    header("Content-Type: application/json");
    echo json_encode(['status' => 'inactive']);
    exit;
}

// Optional: Rückgabe des Status bei einer Anfrage
if (isset($_GET['check_status'])) {
    header("Content-Type: application/json");
    echo json_encode(['status' => 'active']);
    exit;
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Session Check</title>
</head>
<body>
<script>
    // Timeout-Dauer in Millisekunden (5 Minuten = 300000 ms)
    const timeout = 300000;

    // Automatische Weiterleitung nach Timeout
    let inactivityTimer = setTimeout(() => {
        window.location.href = 'login.php?timeout=1';
    }, timeout);

    // Benutzerinteraktion überwachen und Timer zurücksetzen
    ['click', 'mousemove', 'keydown', 'scroll'].forEach(event => {
        window.addEventListener(event, () => {
            clearTimeout(inactivityTimer);
            inactivityTimer = setTimeout(() => {
                window.location.href = 'login.php?timeout=1';
            }, timeout);
        });
    });

    // Funktion zur Prüfung des Session-Status
    function checkSession() {
        fetch('session_check.php?check_status=1')
            .then(response => response.json())
            .then(data => {
                if (data.status === 'inactive') {
                    window.location.href = 'login.php?timeout=1';
                }
            })
            .catch(error => console.error('Fehler beim Prüfen der Session:', error));
    }

    // Sichtbarkeitsüberwachung (für Tablets und Hintergrundmodus)
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') {
            checkSession(); // Session-Status prüfen, wenn die Seite wieder sichtbar wird
        }
    });

    // Regelmäßige Überprüfung der Session alle 5 Minuten
    setInterval(checkSession, timeout);
</script>
</body>
</html>
