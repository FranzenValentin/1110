<?php
require 'db.php';
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Einsatzverwaltung</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header>
        <h1>Einsatzverwaltungssystem</h1>
    </header>
    <nav>
        <ul>
            <li><a href="besatzung.php">Besatzung verwalten</a></li>
            <li><a href="einsatz.php">Einsätze hinzufügen</a></li>
            <li><a href="stichworte.php">Stichworte verwalten</a></li>
        </ul>
    </nav>
    <main>
        <p>Willkommen im Einsatzverwaltungssystem. Wählen Sie eine Option aus dem Menü.</p>
    </main>
    <footer>
        <p>&copy; 2024 Einsatzverwaltung. Alle Rechte vorbehalten.</p>
    </footer>
</body>
</html>
