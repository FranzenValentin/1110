<?php
session_start();
session_unset();
session_destroy();
header('Location: login.php'); // Zurück zur Login-Seite
exit;
?>
