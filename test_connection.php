<?php
require 'db.php';

try {
    $stmt = $pdo->query("SELECT 1");
    echo "Datenbankverbindung erfolgreich!";
} catch (PDOException $e) {
    echo "Fehler bei der Datenbankverbindung: " . $e->getMessage();
}
?>
