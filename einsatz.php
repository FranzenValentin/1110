<?php
require 'db.php';
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Einsätze hinzufügen</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header>
        <h1>Neuen Einsatz hinzufügen</h1>
    </header>
    <main>
        <form action="save_einsatz.php" method="POST">
            <label for="einsatznummer">Einsatznummer LTS:</label>
            <input type="number" id="einsatznummer" name="einsatznummer" required>
            <br>

            <label for="stichwort">Stichwort:</label>
            <select id="stichwort" name="stichwort">
                <?php
                $stmt = $pdo->query("SELECT id, stichwort FROM Stichworte");
                while ($row = $stmt->fetch()) {
                    echo "<option value='{$row['id']}'>{$row['stichwort']}</option>";
                }
                ?>
            </select>
            <br>

            <label for="alarmuhrzeit">Alarmuhrzeit:</label>
            <input type="datetime-local" id="alarmuhrzeit" name="alarmuhrzeit" required>
            <br>

            <label for="adresse">Adresse:</label>
            <input type="text" id="adresse" name="adresse" required>
            <br>

            <button type="submit">Einsatz speichern</button>
        </form>
    </main>
</body>
</html>
