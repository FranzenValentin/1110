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
    const timeout = 300000; // Timeout-Dauer (5 Minuten in Millisekunden)
    const warningTime = 30000; // Warnzeit (30 Sekunden vor Timeout)

    let inactivityTimer; // Timer für die automatische Abmeldung
    let warningTimer; // Timer für die Warnung vor Abmeldung

    // Funktion zum Anzeigen der Warnmeldung
    function showWarning() {
        const warningElement = document.createElement('div');
        warningElement.id = 'session-warning';
        warningElement.style.position = 'fixed';
        warningElement.style.bottom = '20px';
        warningElement.style.right = '20px';
        warningElement.style.padding = '15px';
        warningElement.style.backgroundColor = 'rgba(255, 0, 0, 0.8)';
        warningElement.style.color = 'white';
        warningElement.style.fontSize = '16px';
        warningElement.style.borderRadius = '5px';
        warningElement.style.zIndex = '1000';
        warningElement.innerText = 'Sie werden in 30 Sekunden abgemeldet. Bitte bleiben Sie aktiv!';
        document.body.appendChild(warningElement);
    }

    // Funktion zum Entfernen der Warnmeldung
    function removeWarning() {
        const warningElement = document.getElementById('session-warning');
        if (warningElement) {
            warningElement.remove();
        }
    }

    // Funktion zur automatischen Abmeldung
    function autoLogout() {
        window.location.href = 'login.php?timeout=1';
    }

    // Timer für Warnung und Abmeldung starten
    function startTimers() {
        // Timer für die Warnung
        warningTimer = setTimeout(() => {
            showWarning();
        }, timeout - warningTime);

        // Timer für die automatische Abmeldung
        inactivityTimer = setTimeout(() => {
            autoLogout();
        }, timeout);
    }

    // Timer zurücksetzen und Warnung entfernen
    function resetTimers() {
        clearTimeout(warningTimer);
        clearTimeout(inactivityTimer);
        removeWarning(); // Warnung entfernen
        startTimers(); // Timer neu starten
    }

    // Timer starten
    startTimers();

    // Benutzerinteraktion überwachen und Timer zurücksetzen
    ['click', 'mousemove', 'keydown', 'scroll'].forEach(event => {
        window.addEventListener(event, resetTimers);
    });

    // Funktion zur Prüfung des Session-Status
    function checkSession() {
        fetch('session_check.php?check_status=1')
            .then(response => response.json())
            .then(data => {
                if (data.status === 'inactive') {
                    autoLogout(); // Automatische Abmeldung
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
