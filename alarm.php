<?php
require_once 'parts/session_check.php';
require 'parts/db.php';
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alarme nachtragen</title>
    <link rel="stylesheet" href="styles.css">
    <script src="https://maps.googleapis.com/maps/api/js?key=<?= htmlspecialchars($apiKey) ?>&libraries=places"></script>
</head>
<body>
    <header>
        <h1>Alarme nachtragen</h1>
        <?php include 'parts/menue.php'; ?>
    </header>

    <main>
        <?php include 'parts/new_alarm.php'; ?>
    </main>

</body>
</html>