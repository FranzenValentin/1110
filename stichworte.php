<?php
require_once 'parts/session_check.php';
require 'parts/db.php';

// Stichwort speichern, wenn das Formular abgeschickt wird
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kategorie = $_POST['kategorie'] ?? null;
    $stichwort = $_POST['stichwort'] ?? null;

    if ($kategorie && $stichwort) {
        try {
            // Daten in die Datenbank einfügen
            $stmt = $pdo->prepare("INSERT INTO stichworte (kategorie, stichwort) VALUES (:kategorie, :stichwort)");
            $stmt->execute([':kategorie' => $kategorie, ':stichwort' => $stichwort]);
            $successMessage = "Das Stichwort wurde erfolgreich hinzugefügt.";
        } catch (PDOException $e) {
            $errorMessage = "Fehler beim Speichern: " . htmlspecialchars($e->getMessage());
        }
    } else {
        $errorMessage = "Bitte geben Sie eine Kategorie und ein Stichwort ein.";
    }
}

// Alle Stichworte laden, um sie auf der Seite anzuzeigen
try {
    $stichworteStmt = $pdo->query("SELECT * FROM stichworte ORDER BY kategorie, stichwort");
    $stichworte = $stichworteStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Fehler beim Laden der Stichworte: " . htmlspecialchars($e->getMessage()));
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stichworte verwalten</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header>
        <h1>Stichworte verwalten</h1>
        <?php include 'parts/menue.php'; ?>
    </header>
    <main>
        <!-- Erfolg- oder Fehlermeldung anzeigen -->
        <?php if (isset($successMessage)): ?>
            <p class="success"><?= htmlspecialchars($successMessage) ?></p>
        <?php elseif (isset($errorMessage)): ?>
            <p class="error"><?= htmlspecialchars($errorMessage) ?></p>
        <?php endif; ?>

        <!-- Formular zum Hinzufügen eines Stichworts -->
        <form action="" method="POST" id="box">
            <div>
                <label for="kategorie">Kategorie:</label>
                <select id="kategorie" name="kategorie">
                    <option value="TH">Technische Hilfe</option>
                    <option value="BRAND">Brand</option>
                    <option value="RD">Rettungsdienst</option>
                </select>
                <input type="text" id="stichwort1" name="stichwort" placeholder="Stichwort" required>
            </div>
            <div>
                <br />
            </div>
            <div>
                <button type="submit">Stichwort hinzufügen</button>
            </div>
        </form>
    </main>
    <script src="js/session_timeout.js"></script>
</body>
</html>
