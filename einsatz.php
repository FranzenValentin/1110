<?php
require 'db.php'; // Datenbankverbindung laden

// Fehleranzeigen aktivieren
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {
    // Fahrzeuge laden
    $fahrzeugeStmt = $pdo->prepare("SELECT id, name FROM Fahrzeuge ORDER BY name");
    $fahrzeugeStmt->execute();
    $fahrzeuge = $fahrzeugeStmt->fetchAll(PDO::FETCH_ASSOC);

    // Stichworte laden, sortiert nach Kategorie und Stichwort
    $stichworteStmt = $pdo->prepare("SELECT id, kategorie, stichwort FROM Stichworte ORDER BY kategorie, stichwort");
    $stichworteStmt->execute();
    $stichworte = $stichworteStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Fehler beim Laden der Daten: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save']) || isset($_POST['save_and_back'])) {
        try {
            // Werte aus dem Formular abrufen
            $einsatznummer_lts = !empty($_POST['einsatznummer_lts']) ? $_POST['einsatznummer_lts'] : null;
            $stichwort_id = !empty($_POST['stichwort_id']) ? (int) $_POST['stichwort_id'] : null;
            $alarmuhrzeit = !empty($_POST['alarmuhrzeit']) ? $_POST['alarmuhrzeit'] : null;
            $zurueckzeit = !empty($_POST['zurueckzeit']) ? $_POST['zurueckzeit'] : null;
            $adresse = !empty($_POST['adresse']) ? $_POST['adresse'] : null;
            $fahrzeug_id = !empty($_POST['fahrzeug_id']) ? (int) $_POST['fahrzeug_id'] : 1; // Standardfahrzeug ID = 1

            // Fahrzeugname anhand der ID ermitteln
            $fahrzeug_name = null;
            foreach ($fahrzeuge as $fahrzeug) {
                if ($fahrzeug['id'] === $fahrzeug_id) {
                    $fahrzeug_name = $fahrzeug['name'];
                    break;
                }
            }
            if (!$fahrzeug_name) {
                throw new Exception("Ungültige Fahrzeug-ID ausgewählt.");
            }

            // Eingabedaten validieren
            if (!preg_match('/\d{4}-\d{2}-\d{2} \d{2}:\d{2}', $alarmuhrzeit)) {
                throw new Exception("Alarmuhrzeit hat ein ungültiges Format.");
            }
            if (!preg_match('/\d{4}-\d{2}-\d{2} \d{2}:\d{2}', $zurueckzeit)) {
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
                    (einsatznummer_lts, stichwort_id, alarmuhrzeit, zurueckzeit, adresse, fahrzeug_name, besatzung_id)
                    VALUES (:einsatznummer_lts, :stichwort_id, :alarmuhrzeit, :zurueckzeit, :adresse, :fahrzeug_name, :besatzung_id)";
            $stmt = $pdo->prepare($sql);

            // SQL-Parameter binden und ausführen
            $stmt->execute([
                ':einsatznummer_lts' => $einsatznummer_lts,
                ':stichwort_id' => $stichwort_id,
                ':alarmuhrzeit' => $alarmuhrzeit,
                ':zurueckzeit' => $zurueckzeit,
                ':adresse' => $adresse,
                ':fahrzeug_name' => $fahrzeug_name,
                ':besatzung_id' => $besatzung_id
            ]);

            echo "<p style='color: green;'>Einsatz wurde erfolgreich gespeichert.</p>";

            // Weiterleitung zu index.php, falls der "Speichern und zurück"-Button gedrückt wurde
            if (isset($_POST['save_and_back'])) {
                header("Location: index.php");
                exit;
            }
        } catch (Exception $e) {
            // Fehler ausgeben
            echo "<p style='color: red;'>Fehler: " . $e->getMessage() . "</p>";
        }
    } elseif (isset($_POST['back'])) {
        // Weiterleitung zu index.php, falls der "Zurück"-Button gedrückt wurde
        header("Location: index.php");
        exit;
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
    function setCurrentTime(fieldId) {
        const now = new Date();
        const year = now.getFullYear();
        const month = String(now.getMonth() + 1).padStart(2, '0'); // Monat mit führender Null
        const day = String(now.getDate()).padStart(2, '0'); // Tag mit führender Null
        const hours = String(now.getHours()).padStart(2, '0'); // Stunde mit führender Null
        const minutes = String(now.getMinutes()).padStart(2, '0'); // Minute mit führender Null

        const formattedTime = `${year}-${month}-${day} ${hours}:${minutes}`; // Format YYYY-MM-DD HH:MM
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
            <label>Stichwort:
                <select name="stichwort_id">
                    <?php foreach ($stichworte as $stichwort): ?>
                        <option value="<?= htmlspecialchars($stichwort['id']) ?>">
                            <?= htmlspecialchars($stichwort['kategorie'] . ' - ' . $stichwort['stichwort']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label><br>
            <label>Alarmuhrzeit: 
                <input type="text" name="alarmuhrzeit" id="alarmuhrzeit" placeholder="YYYY-MM-DD HH:MM">
                <button type="button" onclick="setCurrentTime('alarmuhrzeit')">Aktuelle Zeit</button>
            </label><br>
            <label>Zurückzeit: 
                <input type="text" name="zurueckzeit" id="zurueckzeit" placeholder="YYYY-MM-DD HH:MM">
                <button type="button" onclick="setCurrentTime('zurueckzeit')">Aktuelle Zeit</button>
            </label><br>
            <label>Adresse: <input type="text" name="adresse"></label><br>
            <label>Fahrzeug:
                <select name="fahrzeug_id">
                    <?php foreach ($fahrzeuge as $fahrzeug): ?>
                        <option value="<?= htmlspecialchars($fahrzeug['id']) ?>" 
                                <?= $fahrzeug['id'] === 1 ? 'selected' : '' ?>>
                            <?= htmlspecialchars($fahrzeug['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label><br>
            <div>
                <button type="submit" name="save">Speichern</button>
                <button type="submit" name="save_and_back">Speichern und zurück</button>
                <button type="submit" name="back">Zurück</button>
            </div>
        </form>
    </main>
</body>
</html>
