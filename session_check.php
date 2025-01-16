<?php
session_start();
define('SESSION_TIMEOUT', 10);

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
?>

<script>
    // Zeit für die automatische Weiterleitung in Millisekunden (5 Minuten = 300000 ms)
    const timeout = 1000;

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
</script>
