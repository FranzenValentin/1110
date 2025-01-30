<?php
session_start();
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header('Location: ../login.php');
    exit("Zugriff verweigert. Bitte melden Sie sich an.");
}

$firstName = $_SESSION['last_user_firstname'] ?? ''; // Vorname aus der Session
$userID = $_SESSION['last_user_id'] ?? ''; // Benutzer-ID aus der Session

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
</head>
<body>
<script>
    const timeout = 300000; // Timeout-Dauer (5 Minuten in Millisekunden)
    const warningTime = 30000; // Warnzeit (30 Sekunden vor Timeout)

    let inactivityTimer; // Timer für die automatische Abmeldung
    let warningTimer; // Timer für die Warnung vor Abmeldung

    // Funktion zum Anzeigen der modalen Warnung
    function showModalWarning() {
        // Modal erstellen
        const modal = document.createElement('div');
        modal.id = 'session-warning-modal';
        modal.style.position = 'fixed';
        modal.style.top = '0';
        modal.style.left = '0';
        modal.style.width = '100%';
        modal.style.height = '100%';
        modal.style.backgroundColor = 'rgba(0, 0, 0, 0.7)';
        modal.style.color = 'white';
        modal.style.display = 'flex';
        modal.style.flexDirection = 'column';
        modal.style.justifyContent = 'center';
        modal.style.alignItems = 'center';
        modal.style.zIndex = '1000';

        // Nachricht
        const message = document.createElement('p');
        message.style.fontSize = '20px';
        message.style.marginBottom = '20px';
        message.innerText = 'Ihre Sitzung läuft in 30 Sekunden ab! Klicken Sie auf "Aktiv bleiben", um angemeldet zu bleiben.';

        // Schaltfläche
        const button = document.createElement('button');
        button.style.padding = '10px 20px';
        button.style.fontSize = '16px';
        button.style.backgroundColor = '#007bff';
        button.style.color = 'white';
        button.style.border = 'none';
        button.style.borderRadius = '5px';
        button.style.cursor = 'pointer';
        button.innerText = 'Aktiv bleiben';
        button.onclick = () => {
            removeModalWarning(); // Modal entfernen
            resetTimers(); // Timer zurücksetzen
        };

        modal.appendChild(message);
        modal.appendChild(button);
        document.body.appendChild(modal);
    }

    // Funktion zum Entfernen der modalen Warnung
    function removeModalWarning() {
        const modal = document.getElementById('session-warning-modal');
        if (modal) {
            modal.remove();
        }
    }

    // Funktion zur automatischen Abmeldung
    function autoLogout() {
        window.location.href = '../
        login.php?timeout=1';
    }

    // Timer für Warnung und Abmeldung starten
    function startTimers() {
        // Timer für die Warnung
        warningTimer = setTimeout(() => {
            showModalWarning();
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
        removeModalWarning(); // Warnung entfernen
        startTimers(); // Timer neu starten
    }

    // Timer starten
    startTimers();

    // Benutzerinteraktion überwachen und Timer zurücksetzen
    ['click', 'mousemove', 'keydown', 'scroll'].forEach(event => {
        window.addEventListener(event, resetTimers);
    });

    // Sichtbarkeitsüberwachung (für Tablets und Hintergrundmodus)
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') {
            resetTimers();
        }
    });

    // Regelmäßige Überprüfung der Session alle 5 Minuten
    setInterval(() => {
        fetch('session_check.php?check_status=1')
            .then(response => response.json())
            .then(data => {
                if (data.status === 'inactive') {
                    autoLogout();
                }
            })
            .catch(error => console.error('Fehler beim Prüfen der Session:', error));
    }, timeout);
</script>
</body>
</html>
