<?php
require_once 'parts/session_check.php';
require 'parts/db.php';

// Standardfahrzeug (LHF 1110/1)
$fahrzeugId = isset($_POST['fahrzeug']) && is_numeric($_POST['fahrzeug']) ? (int)$_POST['fahrzeug'] : null;
$inDienstZeit = $_POST['inDienstZeit'] ?? null;
$ausserDienstZeit = $_POST['ausserDienstZeit'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save'])) {
        try {
            // Validierung
            if (!$fahrzeugId || !$inDienstZeit || !$ausserDienstZeit) {
                $message = "Bitte wählen Sie ein Fahrzeug und geben Sie die Zeiten ein.";
            } else {
                // Zeiten ins gewünschte Format umwandeln
                $inDienstZeitFormatted = DateTime::createFromFormat('Y-m-d\TH:i', $inDienstZeit)->format('d.m.Y H:i');
                $ausserDienstZeitFormatted = DateTime::createFromFormat('Y-m-d\TH:i', $ausserDienstZeit)->format('d.m.Y H:i');

                // Debugging der umgewandelten Zeiten
                error_log("Fahrzeug-ID: $fahrzeugId, inDienstZeit: $inDienstZeitFormatted, ausserDienstZeit: $ausserDienstZeitFormatted");

                // Besatzung speichern
                $roles = ['stf', 'ma', 'atf', 'atm', 'wtf', 'wtm', 'prakt'];
                $changes = [];

                foreach ($roles as $role) {
                    $changes[$role] = isset($_POST[$role]) && $_POST[$role] !== '' ? $_POST[$role] : null;
                }

                // Werte in die Tabelle einfügen
                $stmt = $pdo->prepare("
                    INSERT INTO dienste (fahrzeug_id, inDienstZeit, ausserDienstZeit, stf_id, ma_id, atf_id, atm_id, wtf_id, wtm_id, prakt_id) 
                    VALUES (:fahrzeugId, :inDienstZeit, :ausserDienstZeit, :stf, :ma, :atf, :atm, :wtf, :wtm, :prakt)
                ");
                $stmt->execute([
                    ':fahrzeugId' => $fahrzeugId,
                    ':inDienstZeit' => $inDienstZeitFormatted,  // Im Format dd.mm.yyyy hh:mm
                    ':ausserDienstZeit' => $ausserDienstZeitFormatted,  // Im Format dd.mm.yyyy hh:mm
                    ':stf' => $changes['stf'],
                    ':ma' => $changes['ma'],
                    ':atf' => $changes['atf'],
                    ':atm' => $changes['atm'],
                    ':wtf' => $changes['wtf'],
                    ':wtm' => $changes['wtm'],
                    ':prakt' => $changes['prakt']
                ]);

                $message = "Besatzung und Zeiten erfolgreich gespeichert.";
                header("Location: " . $_SERVER['PHP_SELF']);
                exit;
            }
        } catch (PDOException $e) {
            // Fehlerausgabe
            error_log("Datenbankfehler: " . $e->getMessage());
            $message = "Ein Fehler ist aufgetreten: " . $e->getMessage();
        }
    }
}


?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Besatzung verwalten</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header>
        <h1>Besatzung und Dienstzeiten verwalten</h1>
        <?php include 'parts/menue.php'; ?>
    </header>
    <main>
        <section id="box">
            <h2>Dienstzeiten und Fahrzeug auswählen</h2>
            <?php if (isset($message)) { echo "<p>$message</p>"; } ?>
            <form method="POST">
                <div>
                    <label for="fahrzeug">Fahrzeug:</label>
                    <select name="fahrzeug" id="fahrzeug" required>
                        <option value="1" <?php echo ($fahrzeugId == 1) ? 'selected' : ''; ?>>LHF 1110/1</option>
                        <option value="2" <?php echo ($fahrzeugId == 2) ? 'selected' : ''; ?>>LHF 1110/2</option>
                        <option value="3" <?php echo ($fahrzeugId == 3) ? 'selected' : ''; ?>>LHF 1110/3</option>
                    </select>
                </div>
                <div>
                    <label for="inDienstZeit">In Dienst Zeit:</label>
                    <input type="datetime-local" id="inDienstZeit" name="inDienstZeit" value="<?php echo htmlspecialchars($inDienstZeit); ?>" required>
                </div>
                <div>
                    <label for="ausserDienstZeit">Außer Dienst Zeit:</label>
                    <input type="datetime-local" id="ausserDienstZeit" name="ausserDienstZeit" value="<?php echo htmlspecialchars($ausserDienstZeit); ?>" required>
                </div>
                <button type="submit" name="next">Weiter</button>
            </form>
        </section>

        <?php if ($fahrzeugId && $inDienstZeit && $ausserDienstZeit): ?>
        <section id="box">
            <h2>Besatzung zuweisen</h2>
            <form method="POST">
                <input type="hidden" name="fahrzeug" value="<?php echo $fahrzeugId; ?>">
                <input type="hidden" name="inDienstZeit" value="<?php echo htmlspecialchars($inDienstZeit); ?>">
                <input type="hidden" name="ausserDienstZeit" value="<?php echo htmlspecialchars($ausserDienstZeit); ?>">

                <table>
                    <thead>
                        <tr>
                            <th>Funktion</th>
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

                        foreach ($roles as $key => $label) {
                            echo "<tr>";
                            echo "<td>$label</td>";
                            echo "<td><select name='$key'>";
                            echo "<option value=''>Keine Auswahl</option>";
                            $stmt = $pdo->query("SELECT id, CONCAT(nachname, ', ', vorname) AS name FROM personal ORDER BY nachname, vorname ASC");
                            while ($row = $stmt->fetch()) {
                                echo "<option value='{$row['id']}'>{$row['name']}</option>";
                            }
                            echo "</select></td>";
                            echo "</tr>";
                        }
                        ?>
                    </tbody>
                </table>
                <button type="submit" name="save" onclick="window.location.href='index.php'">Speichern und zurück</button>
            </form>
        </section>
        <?php endif; ?>
    </main>
</body>
</html>
