<?php
require 'db.php'; // Datenbankverbindung laden

// Fehleranzeigen aktivieren
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Werte aus dem Formular abrufen
        $einsatznummer_lts = !empty($_POST['einsatznummer_lts']) ? $_POST['einsatznummer_lts'] : null;
        $stichwort_id = !empty($_POST['stichwort_id']) ? (int) $_POST['stichwort_id'] : null;
        $alarmuhrzeit = !empty($_POST['alarmuhrzeit']) ? $_POST['alarmuhrzeit'] : null;
        $zurueckzeit = !empty($_POST['zurueckzeit']) ? $_POST['zurueckzeit'] : null;
        $adresse = !empty($_POST['adresse']) ? $_POST['adresse'] : null;
        $fahrzeug = !empty($_POST['fahrzeug']) ? $_POST['fahrzeug'] : null;

        // Eingabedaten validieren
        if (!preg_match('/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/', $alarmuhrzeit)) {
            throw new Exception("Alarmuhrzeit hat ein ungültiges Format.");
        }
        if (!preg_match('/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/', $zurueckzeit)) {
            throw new Exception("Zurückzeit hat ein ungültiges Format.");
        }

        // Aktuellste Besatzung abrufen
        $stmt = $pdo->prepare("SELECT id FROM Besatzung ORDER BY id DESC LIMIT 1");
        $stmt->execute();
        $besatzung_id = $stmt->fetchColumn();

        // Sicherstellen, dass eine gültige Besatzung existiert
        if (!$besatzung_id) {
            throw new Exception("Es konnte keine gültige Besatzung gefunden werden.");
        }

        // SQL-Statement vorbereiten
        $sql = "INSERT INTO Einsaetze 
                (einsatznummer_lts, stichwort_id, alarmuhrzeit, zurueckzeit, adresse, fahrzeug, besatzung_id)
                VALUES (:einsatznummer_lts, :stichwort_id, :alarmuhrzeit, :zurueckzeit, :adresse, :fahrzeug, :besatzung_id)";
        $stmt = $pdo->prepare($sql);

        // SQL-Parameter binden und ausführen
        $stmt->execute([
            ':einsatznummer_lts' => $einsatznummer_lts,
            ':stichwort_id' => $stichwort_id,
            ':alarmuhrzeit' => $alarmuhrzeit,
            ':zurueckzeit' => $zurueckzeit,
            ':adresse' => $adresse,
            ':fahrzeug' => $fahrzeug,
            ':besatzung_id' => $besatzung_id
        ]);

        echo "<p style='color: green;'>Einsatz wurde erfolgreich gespeichert.</p>";
    } catch (Exception $e) {
        // Fehler ausgeben
        echo "<p style='color: red;'>Fehler: " . $e->getMessage() . "</p>";
    }
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
        // Funktion, um die aktuelle Uhrzeit in das Feld einzufügen
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
            <label>Einsatznummer LTS: <input type="text" name="einsatznummer_lts"></label><br>
            <label>Stichwort ID: <input type="number" name="stichwort_id"></label><br>
            <label>Alarmuhrzeit: 
                <input type="text" name="alarmuhrzeit" id="alarmuhrzeit" placeholder="YYYY-MM-DD HH:MM:SS">
                <button type="button" onclick="setCurrentTime('alarmuhrzeit')">Aktuelle Zeit</button>
            </label><br>
            <label>Zurückzeit: 
                <input type="text" name="zurueckzeit" id="zurueckzeit" placeholder="YYYY-MM-DD HH:MM:SS">
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
