<?php
// Einbindung der db.php
require_once 'db.php';

try {
    // Test: Verbindung herstellen und prüfen
    echo "Verbindung zur Datenbank erfolgreich!<br>";

    // Test: Tabelle erstellen
    $createTableQuery = "
        CREATE TABLE IF NOT EXISTS test_table (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(50) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ";
    $pdo->exec($createTableQuery);
    echo "Testtabelle 'test_table' erfolgreich erstellt oder existiert bereits.<br>";

    // Test: Daten in die Tabelle einfügen
    $insertQuery = "INSERT INTO test_table (name) VALUES (:name)";
    $stmt = $pdo->prepare($insertQuery);
    $stmt->execute([':name' => 'Testeintrag']);
    echo "Testdaten erfolgreich eingefügt.<br>";

    // Test: Daten aus der Tabelle abrufen
    $selectQuery = "SELECT * FROM test_table";
    $result = $pdo->query($selectQuery)->fetchAll(PDO::FETCH_ASSOC);

    // Testdaten ausgeben
    echo "Inhalt der Testtabelle:<br>";
    foreach ($result as $row) {
        echo "ID: {$row['id']}, Name: {$row['name']}, Erstellungsdatum: {$row['created_at']}<br>";
    }
} catch (PDOException $e) {
    // Fehler ausgeben, falls etwas schiefgeht
    die("Fehler: " . $e->getMessage());
}
