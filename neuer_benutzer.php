<?php
session_start();
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header('Location: login.php'); // Weiterleitung zur Login-Seite
    exit;
}
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vorname = $_POST['vorname'] ?? '';
    $nachname = $_POST['nachname'] ?? '';

    if (!empty($vorname) && !empty($nachname)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO Personal (vorname, nachname) VALUES (:vorname, :nachname)");
            $stmt->execute([
                ':vorname' => $vorname,
                ':nachname' => $nachname
            ]);

            echo "<p>Benutzer erfolgreich hinzugefügt: $vorname $nachname</p>";
        } catch (PDOException $e) {
            echo "<p>Fehler beim Hinzufügen des Benutzers: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p>Bitte Vorname und Nachname ausfüllen.</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Benutzer hinzufügen">
    <title>Neuen Benutzer hinzufügen</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header>
        <h1>Neuen Benutzer hinzufügen</h1>
    </header>
    <main>
        <section id="benutzer-hinzufuegen">
            <h2>Benutzerinformationen</h2>
            <form action="" method="POST">
                <label for="vorname">Vorname:</label>
                <input type="text" id="vorname" name="vorname" required>
                <br>

                <label for="nachname">Nachname:</label>
                <input type="text" id="nachname" name="nachname" required>
                <br>

                <button type="submit">Benutzer hinzufügen</button>
                <button type="button" onclick="window.location.href='index.php';">Zurück zur Startseite</button>
            </form>
        </section>
    </main>
    <footer>
        <p>&copy; 2024 Benutzerverwaltung. Alle Rechte vorbehalten.</p>
    </footer>
</body>
</html>
