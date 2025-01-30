<?php
require_once 'session_check.php';
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vorname = trim($_POST['vorname'] ?? '');
    $nachname = trim($_POST['nachname'] ?? '');

    if (!empty($vorname) && !empty($nachname)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO personal (vorname, nachname) VALUES (:vorname, :nachname)");
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
    </title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header>
        <?php include 'parts/menue.php'; ?>
    </header>
    <main>
        <section id="benutzer-hinzufuegen">
            <h2>Benutzerinformationen</h2>
            <?php if (isset($message)) { echo "<p class='success'>$message</p>"; } ?>
            <?php if (isset($error)) { echo "<p class='error'>$error</p>"; } ?>
            <form action="" method="POST">
                <div>
                    <input type="text" id="vorname" name="vorname" placeholder="Vorname" required>
                    <input type="text" id="nachname" name="nachname" placeholder="Nachname" required>
                </div>
                <div>
                   <br />
                </div>
                
                <div>
                    <button type="submit">Benutzer hinzufügen</button>
                </div>
            </form>
        </section>
    </main>
</body>
</html>
