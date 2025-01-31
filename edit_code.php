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
    <style>
        /* Hauptlayout */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background: #f5f5f5;
            padding-top: 80px; /* Platz für den fixierten Header */
        }

        header {
            background-color: #007bff;
            color: white;
            width: 100%;
            text-align: center;
            padding: 1rem 0;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
            position: fixed; /* Fixiert den Header */
            top: 0;
            left: 0;
            z-index: 1000; /* Immer im Vordergrund */
        }

        header h1 {
            margin: 0;
            font-size: 1.8rem;
        }

        main {
            background: white;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            border-radius: 10px;
            max-width: 500px;
            width: 90%;
            margin: 0 auto;
        }

        .change-password-form {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .change-password-form label {
            font-size: 1rem;
            font-weight: bold;
        }

        .change-password-form input {
            padding: 0.8rem;
            font-size: 1rem;
            border: 1px solid #ccc;
            border-radius: 5px;
            transition: border-color 0.3s ease;
        }

        .change-password-form input:focus {
            border-color: #007bff;
            outline: none;
        }

        .change-password-form button {
            padding: 0.8rem 1.2rem;
            font-size: 1.2rem;
            color: white;
            background: #007bff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        .change-password-form button:hover {
            background: #0056b3;
        }

        .change-password-form .error {
            color: #e74c3c;
            background: #fdecea;
            padding: 0.8rem;
            border: 1px solid #e74c3c;
            border-radius: 5px;
            text-align: center;
        }

        .change-password-form .success {
            color: #27ae60;
            background: #eafaf1;
            padding: 0.8rem;
            border: 1px solid #27ae60;
            border-radius: 5px;
            text-align: center;
        }

        /* Responsives Design */
        @media (max-width: 768px) {
            header h1 {
                font-size: 1.5rem;
            }

            main {
                padding: 1.5rem;
            }

            .change-password-form input,
            .change-password-form button {
                font-size: 0.9rem;
                padding: 0.7rem;
            }
        }

        @media (max-width: 480px) {
            .change-password-form {
                gap: 1rem;
            }

            .change-password-form label {
                font-size: 0.9rem;
            }

            .change-password-form button {
                padding: 0.6rem;
                font-size: 0.8rem;
            }
        }
    </style>
</head>
<body>
    <header>
        <h1>Zugangscode ändern</h1>
        <?php include 'parts/menue.php'; ?>
    </header>
    
    <main>
        <form method="POST" class="change-password-form">
            <label for="current_code">Aktueller Zugangscode:</label>
            <input type="password" id="current_code" name="current_code" required placeholder="Aktuellen Code eingeben">

            <label for="new_code">Neuer Zugangscode:</label>
            <input type="password" id="new_code" name="new_code" pattern="[0-9]*" inputmode="numeric" required placeholder="Neuen Code eingeben">

            <label for="confirm_code">Neuer Zugangscode bestätigen:</label>
            <input type="password" id="confirm_code" name="confirm_code" pattern="[0-9]*" inputmode="numeric" required placeholder="Neuen Code bestätigen">

            <button type="submit">Zugangscode ändern</button>

            <?php if ($error): ?>
                <p class="error"><?= htmlspecialchars($error) ?></p>
            <?php elseif ($success): ?>
                <p class="success"><?= htmlspecialchars($success) ?></p>
            <?php endif; ?>
        </form>
    </main>
</body>
</html>
