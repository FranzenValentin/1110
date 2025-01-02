<?php
session_start();
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header('Location: login.php'); // Weiterleitung zur Login-Seite
    exit;
}
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vorname = trim($_POST['vorname'] ?? '');
    $nachname = trim($_POST['nachname'] ?? '');

    if (!empty($vorname) && !empty($nachname)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO Personal (vorname, nachname) VALUES (:vorname, :nachname)");
            $stmt->execute([
                ':vorname' => $vorname,
                ':nachname' => $nachname
            ]);
            $message = "Benutzer erfolgreich hinzugefügt: $vorname $nachname";
        } catch (PDOException $e) {
            $error = "Fehler beim Hinzufügen des Benutzers: " . $e->getMessage();
        }
    } else {
        $error = "Bitte füllen Sie alle Felder aus.";
    }
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Benutzer hinzufügen">
    <title>Neuen Benutzer hinzufügen
        <form method="POST" action="logout.php" class="logout-form">
            <button type="submit">Logout</button>
        </form>
        <form method="POST" action="index.php" class="back-form">
            <button type="submit">Zurück</button>
        </form>
    </title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header>
        <h1>Neuen Benutzer hinzufügen</h1>
        <form method="POST" action="logout.php" class="logout-form">
            <button type="submit">Logout</button>
        </form>
    </header>
    <main>
        <section id="benutzer-hinzufuegen">
            <h2>Benutzerinformationen</h2>
            <?php if (isset($message)) { echo "<p class='success'>$message</p>"; } ?>
            <?php if (isset($error)) { echo "<p class='error'>$error</p>"; } ?>
            <form action="" method="POST">
                <div>
                    <label for="vorname">Vorname:</label>
                    <input type="text" id="vorname" name="vorname" required>
                </div>
                <div>
                    <label for="nachname">Nachname:</label>
                    <input type="text" id="nachname" name="nachname" required>
                </div>
                <div>
                    <button type="submit">Benutzer hinzufügen</button><button type="button" onclick="window.location.href='index.php';">Zurück zur Startseite</button>
                </div>
            </form>
        </section>
    </main>
</body>
</html>
