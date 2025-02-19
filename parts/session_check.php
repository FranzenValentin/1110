<?php
session_start();
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header('Location: ../login.php');
    exit;
}

$firstName = $_SESSION['last_user_firstname'] ?? ''; 
$lastName = $_SESSION['last_user_lastname'] ?? ''; 
$userID = $_SESSION['last_user_id'] ?? ''; 

// Maximale Inaktivitätszeit in Sekunden
define('SESSION_TIMEOUT', 300);

// Prüfe den Session-Status für Server-seitige Inaktivitätsverwaltung
if (isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true) {
    if (isset($_SESSION['last_activity'])) {
        $inactivityDuration = time() - $_SESSION['last_activity'];
        if ($inactivityDuration > SESSION_TIMEOUT) {
            session_unset();
            session_destroy();

            // Prüfen, ob die Anfrage ein AJAX-Request ist
            if (isset($_GET['check_status'])) {
                header("Content-Type: application/json");
                echo json_encode(['status' => 'inactive']);
                exit;
            } else {
                header('Location: ../login.php?timeout=1');
                exit;
            }
        }
    }
    $_SESSION['last_activity'] = time(); // Aktualisiere die Aktivität
} 

// Wenn der Benutzer den Session-Status prüft (für AJAX-Anfragen)
if (isset($_GET['check_status'])) {
    header("Content-Type: application/json");
    echo json_encode(['status' => 'active']);
    exit;
}
?>