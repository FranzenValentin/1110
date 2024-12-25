<?php
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $interne_einsatznummer = $_POST['interne_einsatznummer'];
    $einsatznummer_lts = $_POST['einsatznummer_lts'];
    $stichwort_id = $_POST['stichwort_id'];
    $alarmuhrzeit = $_POST['alarmuhrzeit'];
    $zurueckzeit = $_POST['zurueckzeit'] ?: null;
    $adresse = $_POST['adresse'];
    $fahrzeug = $_POST['fahrzeug'];

    // Aktuellste Besatzung abrufen
    $stmt = $conn->prepare("SELECT id FROM Besatzung ORDER BY id DESC LIMIT 1");
    $stmt->execute();
    $besatzung_id = $stmt->fetchColumn();

    // Einsatz einfügen
    $sql = "INSERT INTO Einsaetze (interne_einsatznummer, einsatznummer_lts, stichwort_id, alarmuhrzeit, zurueckzeit, adresse, fahrzeug, besatzung_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        $interne_einsatznummer, $einsatznummer_lts, $stichwort_id, $alarmuhrzeit, $zurueckzeit, $adresse, $fahrzeug, $besatzung_id
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
            <label>Interne Einsatznummer: <input type="text" name="interne_einsatznummer" required></label><br>
            <label>Einsatznummer LTS: <input type="text" name="einsatznummer_lts" required></label><br>
            <label>Stichwort ID: <input type="number" name="stichwort_id" required></label><br>
            <label>Alarmuhrzeit: 
                <input type="text" name="alarmuhrzeit" id="alarmuhrzeit" required>
                <button type="button" onclick="setCurrentTime('alarmuhrzeit')">Aktuelle Zeit</button>
            </label><br>
            <label>Zurückzeit: 
                <input type="text" name="zurueckzeit" id="zurueckzeit">
                <button type="button" onclick="setCurrentTime('zurueckzeit')">Aktuelle Zeit</button>
            </label><br>
            <label>Adresse: <input type="text" name="adresse" required></label><br>
            <label>Fahrzeug: <input type="text" name="fahrzeug" required></label><br>
            <div>
                <button type="submit">Einsatz speichern</button>
            </div>
        </form>
    </main>
</body>
</html>
