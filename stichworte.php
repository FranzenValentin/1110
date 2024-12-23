<?php
require 'db.php';
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
    </header>
    <main>
        <form action="save_stichwort.php" method="POST">
            <label for="kategorie">Kategorie:</label>
            <select id="kategorie" name="kategorie">
                <option value="TH">Technische Hilfe</option>
                <option value="BRAND">Brand</option>
                <option value="RD">Rettungsdienst</option>
            </select>
            <br>

            <label for="stichwort">Stichwort:</label>
            <input type="text" id="stichwort" name="stichwort" required>
            <br>

            <button type="submit">Stichwort hinzufügen</button>
        </form>
    </main>
</body>
</html>
