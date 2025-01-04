<?php
session_start();
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header('Location: login.php');
    exit("Zugriff verweigert. Bitte melden Sie sich an.");
}

require 'db.php';

// Fahrzeug-ID aus Anfrage oder Standardwert
$fahrzeugId = isset($_GET['fahrzeug']) && is_numeric($_GET['fahrzeug']) ? (int)$_GET['fahrzeug'] : 1;

// Fahrzeugliste laden
$query = "SELECT id, name FROM Fahrzeuge";
$statement = $pdo->prepare($query);
$statement->execute();
$fahrzeuge = $statement->fetchAll(PDO::FETCH_ASSOC);

// Initialisierung der Variablen
$inDienstZeit = '';
$ausserDienstZeit = '';
$roles = ['stf', 'ma', 'atf', 'atm', 'wtf', 'wtm', 'prakt'];

// Zeiten und Besatzung laden
if ($fahrzeugId) {
    $zeitQuery = "
        SELECT inDienstZeit, ausserDienstZeit, stf_id, ma_id, atf_id, atm_id, wtf_id, wtm_id, prakt_id 
        FROM Besatzung 
        WHERE fahrzeug_id = :fahrzeug_id 
        ORDER BY id DESC 
        LIMIT 1
    ";
    $zeitStmt = $pdo->prepare($zeitQuery);
    $zeitStmt->execute([':fahrzeug_id' => $fahrzeugId]);
    $latestBesatzung = $zeitStmt->fetch(PDO::FETCH_ASSOC);

    if ($latestBesatzung) {
        $inDienstZeit = $latestBesatzung['inDienstZeit']
            ? DateTime::createFromFormat('d.m.y H:i', $latestBesatzung['inDienstZeit'])->format('Y-m-d\TH:i')
            : '';
        $ausserDienstZeit = $latestBesatzung['ausserDienstZeit']
            ? DateTime::createFromFormat('d.m.y H:i', $latestBesatzung['ausserDienstZeit'])->format('Y-m-d\TH:i')
            : '';
    }
}

// Aktualisieren der Daten
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    $inDienstZeitInput = $_POST['inDienstZeit'] ?? null;
    $ausserDienstZeitInput = $_POST['ausserDienstZeit'] ?? null;

    if ($fahrzeugId && $inDienstZeitInput && $ausserDienstZeitInput) {
        try {
            // Konvertierung ins Datenbankformat
            $inDienstZeitDB = DateTime::createFromFormat('Y-m-d\TH:i', $inDienstZeitInput)->format('d.m.y H:i');
            $ausserDienstZeitDB = DateTime::createFromFormat('Y-m-d\TH:i', $ausserDienstZeitInput)->format('d.m.y H:i');

            // Aktualisieren der Datenbank
            $updateQuery = "
                UPDATE Besatzung 
                SET inDienstZeit = :inDienstZeit, 
                    ausserDienstZeit = :ausserDienstZeit, 
                    stf_id = :stf, ma_id = :ma, atf_id = :atf, 
                    atm_id = :atm, wtf_id = :wtf, wtm_id = :wtm, 
                    prakt_id = :prakt 
                WHERE fahrzeug_id = :fahrzeug_id
            ";
            $updateStmt = $pdo->prepare($updateQuery);
            $updateStmt->execute([
                ':inDienstZeit' => $inDienstZeitDB,
                ':ausserDienstZeit' => $ausserDienstZeitDB,
                ':stf' => $_POST['stf'] ?? null,
                ':ma' => $_POST['ma'] ?? null,
                ':atf' => $_POST['atf'] ?? null,
                ':atm' => $_POST['atm'] ?? null,
                ':wtf' => $_POST['wtf'] ?? null,
                ':wtm' => $_POST['wtm'] ?? null,
                ':prakt' => $_POST['prakt'] ?? null,
                ':fahrzeug_id' => $fahrzeugId
            ]);

            echo "<p style='color: green;'>Die Daten wurden erfolgreich aktualisiert.</p>";
        } catch (PDOException $e) {
            echo "<p style='color: red;'>Fehler beim Speichern der Daten: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    } else {
        echo "<p style='color: red;'>Bitte fülle alle Felder aus!</p>";
    }
}

// Daten löschen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear'])) {
    try {
        $clearQuery = "
            UPDATE Besatzung 
            SET inDienstZeit = NULL, 
                ausserDienstZeit = NULL, 
                stf_id = NULL, ma_id = NULL, atf_id = NULL, 
                atm_id = NULL, wtf_id = NULL, wtm_id = NULL, 
                prakt_id = NULL 
            WHERE fahrzeug_id = :fahrzeug_id
        ";
        $clearStmt = $pdo->prepare($clearQuery);
        $clearStmt->execute([':fahrzeug_id' => $fahrzeugId]);

        echo "<p style='color: green;'>Die Daten wurden erfolgreich gelöscht.</p>";
    } catch (PDOException $e) {
        echo "<p style='color: red;'>Fehler beim Löschen der Daten: " . htmlspecialchars($e->getMessage()) . "</p>";
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
    <form method="POST" action="">
        <h2>Fahrzeug auswählen</h2>
        <select name="fahrzeug_id" onchange="this.form.submit()" required>
            <option value="">Fahrzeug auswählen</option>
            <?php foreach ($fahrzeuge as $fahrzeug): ?>
                <option value="<?php echo htmlspecialchars($fahrzeug['id']); ?>"
                    <?php echo (isset($fahrzeugId) && $fahrzeugId == $fahrzeug['id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($fahrzeug['name']); ?>
                </option>
            <?php endforeach; ?>
        </select>

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
                                $personStmt = $pdo->prepare("SELECT CONCAT(vorname, ' ', nachname) AS name FROM Personal WHERE id = :id");
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
                                $stmt = $pdo->query("SELECT id, CONCAT(nachname, ', ', vorname) AS name FROM Personal ORDER BY nachname, vorname");
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
            <button type="submit" name="clear">Alle löschen</button>
            <button type="submit" name="save">Aktualisieren</button>
        </div>
    </form>
</main>
</body>
</html>
