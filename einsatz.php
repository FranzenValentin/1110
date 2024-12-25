<?php
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $einsatznummer = $_POST['einsatznummer'];
    $alarmzeit = $_POST['alarmzeit'];
    $zurueckzeit = $_POST['zurueckzeit'] ?: null;
    $stichwort = $_POST['stichwort'];
    $adresse = $_POST['adresse'];
    
    // Aktuellste Besatzung abrufen
    $stmt = $conn->prepare("SELECT * FROM Besatzung ORDER BY id DESC LIMIT 1");
    $stmt->execute();
    $besatzung = $stmt->fetch(PDO::FETCH_ASSOC);

    // Einsatz einfügen
    $sql = "INSERT INTO Einsaetze (einsatznummer, alarmzeit, zurueckzeit, stichwort, adresse, stf_id, ma_id, atf_id, atm_id, wtf_id, wtm_id, prakt_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        $einsatznummer, $alarmzeit, $zurueckzeit, $stichwort, $adresse,
        $besatzung['stf_id'], $besatzung['ma_id'], $besatzung['atf_id'], 
        $besatzung['atm_id'], $besatzung['wtf_id'], $besatzung['wtm_id'], 
        $besatzung['prakt_id']
    ]);

    echo "<p>Einsatz erfolgreich eingetragen!</p>";
}

?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css">
    <title>Einsatz eintragen</title>
    <script>
        function setCurrentTime(fieldId) {
            const now = new Date();
            const formattedTime = now.toISOString().slice(0, 19).replace('T', ' ');
            document.getElementById(fieldId).value = formattedTime;
        }
    </script>
</head>
<body>
    <header>
        <h1>Einsatz eintragen</h1>
    </header>
    <main>
        <form method="POST">
            <label>Einsatznummer: <input type="text" name="einsatznummer" required></label><br>
            <label>Alarmzeit: 
                <input type="text" name="alarmzeit" id="alarmzeit" required>
                <button type="button" onclick="setCurrentTime('alarmzeit')">Aktuelle Zeit</button>
            </label><br>
            <label>Zurückzeit: 
                <input type="text" name="zurueckzeit" id="zurueckzeit">
                <button type="button" onclick="setCurrentTime('zurueckzeit')">Aktuelle Zeit</button>
            </label><br>
            <label>Stichwort: <input type="text" name="stichwort" required></label><br>
            <label>Adresse: <input type="text" name="adresse" required></label><br>
            <div>
                <button type="submit">Einsatz speichern</button>
            </div>
        </form>
    </main>
</body>
</html>
