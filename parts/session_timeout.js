const timeout = 300000; // Timeout-Dauer (5 Minuten in Millisekunden)
const warningTime = 30000; // Warnzeit (30 Sekunden vor Timeout)

let inactivityTimer; // Timer für die automatische Abmeldung
let warningTimer; // Timer für die Warnung vor Abmeldung

// Funktion zum Anzeigen der modalen Warnung
function showModalWarning() {
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

    const message = document.createElement('p');
    message.style.fontSize = '20px';
    message.style.marginBottom = '20px';
    message.innerText = 'Ihre Sitzung läuft in 30 Sekunden ab! Klicken Sie auf "Aktiv bleiben", um angemeldet zu bleiben.';

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
    window.location.href = '../login.php?timeout=1';
}

// Timer für Warnung und Abmeldung starten
function startTimers() {
    warningTimer = setTimeout(() => {
        showModalWarning();
    }, timeout - warningTime);

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
