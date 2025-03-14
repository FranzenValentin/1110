<?php
require_once 'parts/session_check.php';
require 'parts/db.php';

// Fahrzeug-ID aus der URL oder Standardwert
if (isset($_GET['fahrzeug']) && is_numeric($_GET['fahrzeug'])) {
    $fahrzeugId = (int)$_GET['fahrzeug'];
} else {
    $fahrzeugId = 1; // Standardwert
}

// Fahrzeug validieren
$query = "SELECT COUNT(*) FROM fahrzeuge WHERE id = :fahrzeug_id";
$stmt = $pdo->prepare($query);
$stmt->execute([':fahrzeug_id' => $fahrzeugId]);
if ($stmt->fetchColumn() == 0) {
    exit("Ungültige Fahrzeug-ID.");
}

// Initialisierung der Variablen
$inDienstZeit = '';
$ausserDienstZeit = '';
$roles = ['stf', 'ma', 'atf', 'atm', 'wtf', 'wtm', 'prakt'];

// Zeiten und Dienst laden
$zeitQuery = "
    SELECT inDienstZeit, ausserDienstZeit, stf_id, ma_id, atf_id, atm_id, wtf_id, wtm_id, prakt_id 
    FROM dienste 
    WHERE fahrzeug_id = :fahrzeug_id 
    ORDER BY STR_TO_DATE(inDienstZeit, '%d.%m.%Y %H:%i') DESC 
    LIMIT 1
";
$zeitStmt = $pdo->prepare($zeitQuery);
$zeitStmt->execute([':fahrzeug_id' => $fahrzeugId]);
$latestBesatzung = $zeitStmt->fetch(PDO::FETCH_ASSOC);

if ($latestBesatzung) {
    $inDienstZeit = $latestBesatzung['inDienstZeit']
        ? DateTime::createFromFormat('d.m.Y H:i', $latestBesatzung['inDienstZeit'])->format('Y-m-d\TH:i')
        : '';
    $ausserDienstZeit = $latestBesatzung['ausserDienstZeit']
        ? DateTime::createFromFormat('d.m.Y H:i', $latestBesatzung['ausserDienstZeit'])->format('Y-m-d\TH:i')
        : '';
}

// Aktualisieren der Daten
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    $inDienstZeitInput = $_POST['inDienstZeit'] ?? null;
    $ausserDienstZeitInput = $_POST['ausserDienstZeit'] ?? null;

    if ($fahrzeugId && $inDienstZeitInput && $ausserDienstZeitInput) {
        try {
            // Konvertierung ins Datenbankformat
            $inDienstZeitDB = DateTime::createFromFormat('Y-m-d\TH:i', $inDienstZeitInput)->format('d.m.Y H:i');
            $ausserDienstZeitDB = DateTime::createFromFormat('Y-m-d\TH:i', $ausserDienstZeitInput)->format('d.m.Y H:i');    

            // Rollen validieren
            $validRoles = [];
            foreach ($roles as $role) {
                $validRoles[$role] = isset($_POST[$role]) && is_numeric($_POST[$role]) ? (int)$_POST[$role] : null;

                // Validierung: Überprüfen, ob die ID in der Tabelle Personal existiert
                if ($validRoles[$role] !== null) {
                    $personCheckStmt = $pdo->prepare("SELECT COUNT(*) FROM personal WHERE id = :id");
                    $personCheckStmt->execute([':id' => $validRoles[$role]]);
                    if ($personCheckStmt->fetchColumn() == 0) {
                        $validRoles[$role] = null; // Ungültige ID, auf NULL setzen
                    }
                }
            }

            // Neuen Dienst aktualisieren
            $updateQuery = "
                UPDATE dienste 
                SET inDienstZeit = :inDienstZeit, 
                    ausserDienstZeit = :ausserDienstZeit, 
                    stf_id = :stf, ma_id = :ma, atf_id = :atf, 
                    atm_id = :atm, wtf_id = :wtf, wtm_id = :wtm, 
                    prakt_id = :prakt 
                WHERE fahrzeug_id = :fahrzeug_id 
                AND STR_TO_DATE(inDienstZeit, '%d.%m.%Y %H:%i') = STR_TO_DATE(:original_inDienstZeit, '%d.%m.%Y %H:%i')
                LIMIT 1
            ";
            $updateStmt = $pdo->prepare($updateQuery);
            $updateStmt->execute([
                ':inDienstZeit' => $inDienstZeitDB,
                ':ausserDienstZeit' => $ausserDienstZeitDB,
                ':stf' => $validRoles['stf'],
                ':ma' => $validRoles['ma'],
                ':atf' => $validRoles['atf'],
                ':atm' => $validRoles['atm'],
                ':wtf' => $validRoles['wtf'],
                ':wtm' => $validRoles['wtm'],
                ':prakt' => $validRoles['prakt'],
                ':fahrzeug_id' => $fahrzeugId,
                ':original_inDienstZeit' => $latestBesatzung['inDienstZeit']
            ]);

            // Weiterleitung zur Indexseite mit Fahrzeug-ID
            header("Location: index.php?fahrzeug=$fahrzeugId");
            exit;
        } catch (PDOException $e) {
            echo "<p style='color: red;'>Fehler beim Speichern der Daten: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    } else {
        echo "<p style='color: red;'>Bitte fülle alle Felder aus!</p>";
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
    <h1>Dienst bearbeiten - <?php
            // Fahrzeugnamen abrufen
            $fahrzeugNameQuery = "SELECT name FROM fahrzeuge WHERE id = :fahrzeug_id";
            $fahrzeugNameStmt = $pdo->prepare($fahrzeugNameQuery);
            $fahrzeugNameStmt->execute([':fahrzeug_id' => $fahrzeugId]);
            $fahrzeugName = $fahrzeugNameStmt->fetchColumn();

            // Fahrzeugnamen ausgeben
            echo htmlspecialchars($fahrzeugName ?? 'Unbekanntes Fahrzeug');
            ?></h1>
<?php include 'parts/menue.php'; ?>
</header>
<main>
    <section id="box">
    <form method="POST" action="">
        <h2>In Dienst Zeit:</h2>
        <input type="datetime-local" name="inDienstZeit" value="<?php echo htmlspecialchars($inDienstZeit); ?>" required>

        <h2>Außer Dienst Zeit:</h2>
        <input type="datetime-local" name="ausserDienstZeit" value="<?php echo htmlspecialchars($ausserDienstZeit); ?>" required>

        <h2>Besatzung</h2>
        <table>
            <thead>
                <tr>
                    <th>Funktion</th>
                    <th>Aktuell zugewiesen</th>
                    <th>Neue Auswahl</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($roles as $role): ?>
                    <tr>
                        <td><?php echo ucfirst($role); ?></td>
                        <td>
                            <?php
                            if ($latestBesatzung && $latestBesatzung[$role . '_id']) {
                                $personStmt = $pdo->prepare("SELECT CONCAT(vorname, ' ', nachname) AS name FROM personal WHERE id = :id");
                                $personStmt->execute([':id' => $latestBesatzung[$role . '_id']]);
                                $person = $personStmt->fetch();
                                echo htmlspecialchars($person['name'] ?? 'Keine Zuweisung');
                            } else {
                                echo "Keine Zuweisung";
                            }
                            ?>
                        </td>
                        <td>
                            <select name="<?php echo $role; ?>">
                                <option value="">Keine Auswahl</option>
                                <?php
                                $stmt = $pdo->query("SELECT id, CONCAT(nachname, ', ', vorname) AS name FROM personal ORDER BY nachname, vorname");
                                while ($row = $stmt->fetch()) {
                                    $selected = ($latestBesatzung && $latestBesatzung[$role . '_id'] == $row['id']) ? 'selected' : '';
                                    echo "<option value='{$row['id']}' $selected>{$row['name']}</option>";
                                }
                                ?>
                            </select>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
            <button type="submit" name="save">Speichern</button>
        </div>
    </form>
    </section>
</main>
<script src="parts/session_timeout.js"></script>
</body>
</html>
