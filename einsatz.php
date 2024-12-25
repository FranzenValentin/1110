<?php
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Eingabewerte aus dem Formular abrufen und leere Felder als NULL setzen
    $interne_einsatznummer = $_POST['interne_einsatznummer'] ?: null;
    $einsatznummer_lts = $_POST['einsatznummer_lts'] ?: null;
    $stichwort_id = $_POST['stichwort_id'] ?: null;
    $alarmuhrzeit = $_POST['alarmuhrzeit'] ?: null;
    $zurueckzeit = $_POST['zurueckzeit'] ?: null;
    $adresse = $_POST['adresse'] ?: null;
    $fahrzeug = $_POST['fahrzeug'] ?: null;

    // Aktuellste Besatzung abrufen
    $stmt = $conn->prepare("SELECT id FROM Besatzung ORDER BY id DESC LIMIT 1");
    $stmt->execute();
    $besatzung_id = $stmt->fetchColumn();

    // Einsatz einfügen
    $sql = "INSERT INTO Einsaetze (interne_einsatznummer, einsatznummer_lts, stichwort_id, alarmuhrzeit, zurueckzeit, adresse, fahrzeug, besatzung_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);

    // Übergabe der Parameter und Berücksichtigung von NULL-Werten
    $stmt->execute([
        $interne_einsatznummer, 
        $einsatznummer_lts, 
        $stichwort_id, 
        $alarmuhrzeit, 
        $zurueckzeit, 
        $adresse, 
        $fahrzeug, 
        $besatzung_id
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
            <label>Interne Einsatznummer: <input type="text" name="interne_einsatznummer"></label><br>
            <label>Einsatznummer LTS: <input type="text" name="einsatznummer_lts"></label><br>
            <label>Stichwort ID: <input type="number" name="stichwort_id"></label><br>
            <label>Alarmuhrzeit: 
                <input type="text" name="alarmuhrzeit" id="alarmuhrzeit">
                <button type="button" onclick="setCurrentTime('alarmuhrzeit')">Aktuelle Zeit</button>
            </label><br>
            <label>Zurückzeit: 
                <input type="text" name="zurueckzeit" id="zurueckzeit">
                <button type="button" onclick="setCurrentTime('zurueckzeit')">Aktuelle Zeit</button>
            </label><br>
            <label>Adresse: <input type="text" name="adresse"></label><br>
            <label>Fahrzeug: <input type="text" name="fahrzeug"></label><br>
            <div>
                <button type="submit">Einsatz speichern</button>
            </div>
        </form>
    </main>
</body>
</html>
