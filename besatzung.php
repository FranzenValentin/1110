<?php
session_start();
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header('Location: login.php'); // Weiterleitung zur Login-Seite
    exit;
}
require 'db.php';

// Standardfahrzeug (LHF 1110/1)
$fahrzeugId = isset($_GET['fahrzeug']) && is_numeric($_GET['fahrzeug']) ? (int)$_GET['fahrzeug'] : 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save'])) {
        // Besatzung speichern
        $roles = ['stf', 'ma', 'atf', 'atm', 'wtf', 'wtm', 'prakt'];
        $changes = [];

        foreach ($roles as $role) {
            if (isset($_POST[$role]) && $_POST[$role] !== '') {
                $person_id = $_POST[$role];
                $changes[$role] = $person_id;
            } else {
                $changes[$role] = null;
            }
        }

        // Prüfen, ob die letzte Zeile nur NULL enthält und zur aktuellen Fahrzeug-ID gehört
        $stmt = $pdo->prepare("SELECT * FROM Besatzung WHERE fahrzeug_id = :fahrzeugId ORDER BY id DESC LIMIT 1");
        $stmt->execute([':fahrzeugId' => $fahrzeugId]);
        $lastRow = $stmt->fetch();
        $lastRowIsNull = ($lastRow && is_null($lastRow['stf_id']) && is_null($lastRow['ma_id']) && is_null($lastRow['atf_id']) && is_null($lastRow['atm_id']) && is_null($lastRow['wtf_id']) && is_null($lastRow['wtm_id']) && is_null($lastRow['prakt_id']));

        if ($lastRowIsNull) {
            // Letzte Zeile überschreiben
            $stmt = $pdo->prepare("UPDATE Besatzung SET stf_id = :stf, ma_id = :ma, atf_id = :atf, atm_id = :atm, wtf_id = :wtf, wtm_id = :wtm, prakt_id = :prakt WHERE id = :id");
            $stmt->execute([
                ':stf' => $changes['stf'],
                ':ma' => $changes['ma'],
                ':atf' => $changes['atf'],
                ':atm' => $changes['atm'],
                ':wtf' => $changes['wtf'],
                ':wtm' => $changes['wtm'],
                ':prakt' => $changes['prakt'],
                ':id' => $lastRow['id']
            ]);
        } else {
            // Neue Zeile mit den Änderungen erstellen
            $stmt = $pdo->prepare("INSERT INTO Besatzung (stf_id, ma_id, atf_id, atm_id, wtf_id, wtm_id, prakt_id, fahrzeug_id) VALUES (:stf, :ma, :atf, :atm, :wtf, :wtm, :prakt, :fahrzeugId)");
            $stmt->execute([
                ':stf' => $changes['stf'],
                ':ma' => $changes['ma'],
                ':atf' => $changes['atf'],
                ':atm' => $changes['atm'],
                ':wtf' => $changes['wtf'],
                ':wtm' => $changes['wtm'],
                ':prakt' => $changes['prakt'],
                ':fahrzeugId' => $fahrzeugId
            ]);
        }

        $message = "Besatzung erfolgreich aktualisiert.";
        header("Location: " . $_SERVER['PHP_SELF'] . "?fahrzeug=" . $fahrzeugId); // Seite neu laden
        exit;
    } elseif (isset($_POST['clear'])) {
        // Neue Zeile mit nur NULL für das ausgewählte Fahrzeug einfügen
        $stmt = $pdo->prepare("INSERT INTO Besatzung (stf_id, ma_id, atf_id, atm_id, wtf_id, wtm_id, prakt_id, fahrzeug_id) VALUES (NULL, NULL, NULL, NULL, NULL, NULL, NULL, :fahrzeugId)");
        $stmt->execute([':fahrzeugId' => $fahrzeugId]);

        $message = "Auswahl zurückgesetzt. Bitte speichern, um Änderungen zu übernehmen.";
    }
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dienst bearbeiten</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header>
        <h1>Dienst bearbeiten</h1>
        <form method="POST" action="logout.php" class="logout-form">
            <button type="submit">Logout</button>
        </form>
        <form method="POST" action="index.php" class="back-form">
            <button type="submit">Zurück</button>
         </form>
        
        
    </header>
    <main>

    <?php
// Fahrzeugliste laden
$query = "SELECT id, name FROM Fahrzeuge";
$statement = $pdo->prepare($query);
$statement->execute();
$fahrzeuge = $statement->fetchAll(PDO::FETCH_ASSOC);

// Initialisierung der Variablen
$inDienstZeit = '';
$ausserDienstZeit = '';
$fahrzeugId = $_POST['fahrzeug_id'] ?? $_GET['fahrzeug_id'] ?? null;

// Zeiten aus der Datenbank laden, falls ein Fahrzeug ausgewählt wurde
if ($fahrzeugId) {
    $zeitQuery = "
        SELECT inDienstZeit, ausserDienstZeit 
        FROM Besatzung 
        WHERE fahrzeug_id = :fahrzeug_id 
        LIMIT 1
    ";
    $zeitStmt = $pdo->prepare($zeitQuery);
    $zeitStmt->execute([':fahrzeug_id' => $fahrzeugId]);
    $zeitResult = $zeitStmt->fetch(PDO::FETCH_ASSOC);

    // Aktuelle Zeiten setzen und ins ISO-Format umwandeln
    if ($zeitResult) {
        $inDienstZeit = $zeitResult['inDienstZeit'] 
            ? (new DateTime($zeitResult['inDienstZeit']))->format('Y-m-d\TH:i') 
            : '';
        $ausserDienstZeit = $zeitResult['ausserDienstZeit'] 
            ? (new DateTime($zeitResult['ausserDienstZeit']))->format('Y-m-d\TH:i') 
            : '';
    }
}
?>


<form method="POST" action="">
    <!-- Fahrzeug auswählen -->
    <h2>Fahrzeug:</h2>
    <div style="position: relative;">
        <select name="fahrzeug_id" onchange="this.form.submit()" required style="width: 100%; padding: 10px;">
            <option value="">Fahrzeug auswählen</option>
            <?php foreach ($fahrzeuge as $fahrzeug): ?>
                <option value="<?php echo htmlspecialchars($fahrzeug['id']); ?>"
                    <?php echo (isset($fahrzeugId) && $fahrzeugId == $fahrzeug['id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($fahrzeug['name']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- In Dienst Zeit -->
    <h2>In Dienst Zeit:</h2>
    <div style="position: relative;">
        <input 
            type="datetime-local" 
            id="inDienstZeit" 
            name="inDienstZeit" 
            value="<?php echo htmlspecialchars($inDienstZeit); ?>" 
            required 
            style="padding-left: 5px; width: 100%;">
    </div>

    <!-- Außer Dienst Zeit -->
    <h2>Außer Dienst Zeit:</h2>
    <div style="position: relative;">
        <input 
            type="datetime-local" 
            id="ausserDienstZeit" 
            name="ausserDienstZeit" 
            value="<?php echo htmlspecialchars($ausserDienstZeit); ?>" 
            required 
            style="padding-left: 5px; width: 100%;">
    </div>

    <!-- Speichern-Button -->
    <button type="submit" name="save" style="margin-top: 20px;">Speichern</button>
</form>




            <?php if (isset($message)) { echo "<p>$message</p>"; } ?>
            <form method="POST">
                <table>
                    <thead>
                        <tr>
                            <th>Funktion</th>
                            <th>Aktuell zugewiesen</th>
                            <th>Neue Auswahl</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $roles = [
                            'stf' => 'Staffel-Führer',
                            'ma' => 'Maschinist',
                            'atf' => 'Angriffstrupp-Führer',
                            'atm' => 'Angriffstrupp-Mann',
                            'wtf' => 'Wassertrupp-Führer',
                            'wtm' => 'Wassertrupp-Mann',
                            'prakt' => 'Praktikant'
                        ];

                        // Die letzte Besatzungszeile für das aktuelle Fahrzeug abrufen
                        $stmt = $pdo->prepare("SELECT * FROM Besatzung WHERE fahrzeug_id = :fahrzeugId ORDER BY id DESC LIMIT 1");
                        $stmt->execute([':fahrzeugId' => $fahrzeugId]);
                        $latestBesatzung = $stmt->fetch();

                        foreach ($roles as $key => $label) {
                            echo "<tr>";
                            echo "<td>$label</td>";

                            // Prüfen, ob eine Person bereits zugewiesen ist
                            if ($latestBesatzung && $latestBesatzung[$key . '_id']) {
                                $personStmt = $pdo->prepare("SELECT CONCAT(vorname, ' ', nachname) AS name FROM Personal WHERE id = :id");
                                $personStmt->execute([':id' => $latestBesatzung[$key . '_id']]);
                                $person = $personStmt->fetch();
                                echo "<td>" . ($person['name'] ?? '<em>Keine Zuweisung</em>') . "</td>";
                            } else {
                                echo "<td><em>Keine Zuweisung</em></td>";
                            }

                            // Dropdown zur Auswahl
                            echo "<td><select name='$key'>";
                            echo "<option value=''>Keine Auswahl</option>";
                            $stmt = $pdo->query("SELECT id, CONCAT(nachname, ', ', vorname) AS name FROM Personal ORDER BY nachname, vorname ASC");
                            while ($row = $stmt->fetch()) {
                                $selected = ($latestBesatzung && $latestBesatzung[$key . '_id'] == $row['id']) ? 'selected' : '';
                                echo "<option value='{$row['id']}' $selected>{$row['name']}</option>";
                            }
                            echo "</select></td>";
                            echo "</tr>";
                        }
                        ?>
                    </tbody>
                </table>
                <div>
                    <button type="submit" name="save">Speichern</button>
                    <button type="submit" name="clear">Alle löschen</button>
                </div>
            </form>
        </section>
    </main>
</body>
</html>
