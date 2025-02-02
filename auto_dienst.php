<?php
require_once 'parts/db.php';

$error = '';
$zuweisungDetails = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Array von IDs der anwesenden Personen aus dem Formular
    $anwesende = isset($_POST['anwesende']) ? $_POST['anwesende'] : [];

    if (empty($anwesende)) {
        $error = "Keine anwesenden Personen angegeben.";
    } else {
        try {
            // Schritt 1: Daten der letzten 3 Monate abrufen
            $threeMonthsAgo = date('Y-m-d H:i:s', strtotime('-3 months'));

            // Abfrage der Dienste innerhalb der letzten 3 Monate
            $dienstHistoryQuery = "
                SELECT 
                    stf_id, ma_id, atf_id, atm_id, wtf_id, wtm_id
                FROM dienste
                WHERE STR_TO_DATE(inDienstZeit, '%d.%m.%Y %H:%i') >= :threeMonthsAgo
            ";
            $stmt = $pdo->prepare($dienstHistoryQuery);
            $stmt->execute(['threeMonthsAgo' => $threeMonthsAgo]);
            $dienstHistory = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Zähle Dienste pro Person und Rolle
            $dienstCounter = [];
            foreach ($dienstHistory as $dienst) {
                foreach ($dienst as $role => $personId) {
                    if ($personId) {
                        $dienstCounter[$role][$personId] = ($dienstCounter[$role][$personId] ?? 0) + 1;
                    }
                }
            }

            // Schritt 2: Filter nach Qualifikationen und gerechte Zuweisung
            $roles = ['stf_id' => 'stf', 'ma_id' => 'ma', 'atf_id' => 'tf', 'atm_id' => 'tf', 'wtf_id' => 'tf', 'wtm_id' => 'tf'];
            $zuweisung = []; // Speichert die berechnete Zuweisung
            $zuweisungDetails = []; // Speichert Details für die Anzeige

            foreach ($roles as $role => $qualification) {
                // Personen mit der benötigten Qualifikation herausfiltern
                $query = "
                    SELECT id, nachname, vorname
                    FROM personal
                    WHERE id IN (" . implode(',', array_map('intval', $anwesende)) . ") AND $qualification = 1
                ";
                $stmt = $pdo->query($query);
                $eligiblePersons = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if (empty($eligiblePersons)) {
                    throw new Exception("Keine qualifizierten Personen für die Rolle: $role gefunden.");
                }

                // Personen nach bisheriger Dienstanzahl sortieren (aufsteigend)
                usort($eligiblePersons, function ($a, $b) use ($dienstCounter, $role) {
                    $countA = $dienstCounter[$role][$a['id']] ?? 0;
                    $countB = $dienstCounter[$role][$b['id']] ?? 0;
                    return $countA <=> $countB;
                });

                // Wähle die Person mit der geringsten Anzahl
                $zuweisung[$role] = $eligiblePersons[0]['id'];
                $zuweisungDetails[$role] = [
                    'id' => $eligiblePersons[0]['id'],
                    'name' => $eligiblePersons[0]['vorname'] . ' ' . $eligiblePersons[0]['nachname'],
                    'dienste' => $dienstCounter[$role][$eligiblePersons[0]['id']] ?? 0,
                ];
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Automatische Dienstzuteilung</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f4f4f4;
        }

        h1 {
            text-align: center;
        }

        .error {
            color: red;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <h1>Automatische Dienstzuteilung</h1>
    <?php if (!empty($error)): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="POST">
        <label for="anwesende">Anwesende auswählen:</label><br>
        <select id="anwesende" name="anwesende[]" multiple size="10" required>
            <?php
            // Alle Personen abrufen
            $stmt = $pdo->query("SELECT id, nachname, vorname FROM personal ORDER BY nachname, vorname");
            $personal = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($personal as $person) {
                echo "<option value='{$person['id']}'>" . htmlspecialchars($person['vorname'] . ' ' . $person['nachname']) . "</option>";
            }
            ?>
        </select><br><br>
        <button type="submit">Zuteilung anzeigen</button>
    </form>

    <?php if (!empty($zuweisungDetails)): ?>
        <h2>Zuweisungsvorschlag</h2>
        <table>
            <thead>
                <tr>
                    <th>Rolle</th>
                    <th>Person</th>
                    <th>Dienste in dieser Rolle</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($zuweisungDetails as $role => $details): ?>
                    <tr>
                        <td><?= htmlspecialchars(ucfirst(str_replace('_id', '', $role))) ?></td>
                        <td><?= htmlspecialchars($details['name']) ?></td>
                        <td><?= htmlspecialchars($details['dienste']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</body>
</html>
