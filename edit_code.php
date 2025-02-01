<?php
require_once 'parts/session_check.php';
require 'parts/db.php';

$user_id = $_SESSION['last_user_id']; // Benutzer-ID aus der Session
$error = $success = "";

// Wenn das Formular abgeschickt wird
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_code = trim($_POST['current_code']);
    $new_code = trim($_POST['new_code']);
    $confirm_code = trim($_POST['confirm_code']);

    if (empty($current_code) || empty($new_code) || empty($confirm_code)) {
        $error = "Bitte alle Felder ausfüllen.";
    } elseif ($new_code !== $confirm_code) {
        $error = "Die neuen Zugangscodes stimmen nicht überein.";
    } elseif (!ctype_digit($new_code) || strlen($new_code) < 4) {
        $error = "Der neue Zugangscode muss mindestens 4 Ziffern lang sein.";
    } else {
        try {
            // Aktuellen Code überprüfen
            $stmt = $pdo->prepare("SELECT code FROM personal WHERE id = :user_id");
            $stmt->execute(['user_id' => $user_id]);
            $stored_code = $stmt->fetchColumn();

            if ($stored_code === false) {
                $error = "Benutzer nicht gefunden oder kein Code gespeichert.";
            } elseif ((string)$stored_code !== $current_code) {
                $error = "Der aktuelle Zugangscode ist falsch. (Eingegeben: $current_code, Gespeichert: $stored_code)";
            } else {
                // Neuen Code speichern
                $stmt = $pdo->prepare("UPDATE personal SET code = :new_code WHERE id = :user_id");
                $stmt->execute(['new_code' => $new_code, 'user_id' => $user_id]);

                $success = "Dein Zugangscode wurde erfolgreich geändert.";
                file_put_contents(
                    __DIR__ . '/change_code_logs.txt',
                    "Benutzer-ID: $user_id | Neuer Code gesetzt | Zeit: " . date('Y-m-d H:i:s') . PHP_EOL,
                    FILE_APPEND
                );
            }
        } catch (PDOException $e) {
            $error = "Fehler beim Ändern des Zugangscodes: " . $e->getMessage();
        }
    }
}

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zugangscode ändern</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header>
        <h1>Zugangscode ändern</h1>
        <?php include 'parts/menue.php'; ?>
    </header>
    
    <main>
    <div id="box" class="responsive-form">
        <form method="POST" class="change-password-form">
            <div class="form-group">
            <label for="current_code">Aktueller Zugangscode:</label>
            <input type="password" id="current_code" name="current_code" required placeholder="Aktuellen Code eingeben">
            </div>

            <div class="form-group">
            <label for="new_code">Neuer Zugangscode:</label>
            <input type="password" id="new_code" name="new_code" pattern="[0-9]*" inputmode="numeric" required placeholder="Neuen Code eingeben">
            </div>

            <div class="form-group">
            <label for="confirm_code">Neuer Zugangscode bestätigen:</label>
            <input type="password" id="confirm_code" name="confirm_code" pattern="[0-9]*" inputmode="numeric" required placeholder="Neuen Code bestätigen">
            </div>

            <div class="form-group">
            <button type="submit">Zugangscode ändern</button>
            </div>

            <?php if ($error): ?>
                <p class="error"><?= htmlspecialchars($error) ?></p>
            <?php elseif ($success): ?>
                <p class="success"><?= htmlspecialchars($success) ?></p>
            <?php endif; ?>
        </form>
    </div>
    </main>
</body>
</html>