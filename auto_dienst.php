<?php
require_once 'parts/db.php';

$error = '';
$zuweisungDetails = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    var_dump($_POST); // Debugging: Zeige die POST-Daten an
    $anwesende = isset($_POST['anwesende']) ? $_POST['anwesende'] : [];

    if (empty($anwesende)) {
        $error = "Keine anwesenden Personen angegeben.";
    } else {
        try {
            // Schritt 1: Daten der letzten 3 Monate abrufen
            $threeMonthsAgo = date('Y-m-d H:i:s', strtotime('-3 months'));

            $dienstHistoryQuery = "
                SELECT 
                    stf_id, ma_id, atf_id, atm_id, wtf_id, wtm_id
                FROM dienste
                WHERE STR_TO_DATE(inDienstZeit, '%d.%m.%Y %H:%i') >= :threeMonthsAgo
            ";
            $stmt = $pdo->prepare($dienstHistoryQuery);
            $stmt->execute(['threeMonthsAgo' => $threeMonthsAgo]);
            $dienstHistory = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $dienstCounter = [];
            foreach ($dienstHistory as $dienst) {
                foreach ($dienst as $role => $personId) {
                    if ($personId) {
                        $dienstCounter[$role][$personId] = ($dienstCounter[$role][$personId] ?? 0) + 1;
                    }
                }
            }

            $roles = ['stf_id' => 'stf', 'ma_id' => 'ma', 'atf_id' => 'tf', 'atm_id' => 'none', 'wtf_id' => 'tf', 'wtm_id' => 'none'];
            $zuweisung = [];
            $zuweisungDetails = [];

            foreach ($roles as $role => $qualification) {
                $query = "
                    SELECT id, nachname, vorname
                    FROM personal
                    WHERE id IN (" . implode(',', array_map('intval', $anwesende)) . ")
                    " . ($qualification !== 'none' ? "AND $qualification = 1" : "") . "
                ";
                $stmt = $pdo->query($query);
                $eligiblePersons = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if (empty($eligiblePersons)) {
                    throw new Exception("Keine qualifizierten Personen für die Rolle: $role gefunden.");
                }

                usort($eligiblePersons, function ($a, $b) use ($dienstCounter, $role) {
                    $countA = $dienstCounter[$role][$a['id']] ?? 0;
                    $countB = $dienstCounter[$role][$b['id']] ?? 0;
                    return $countA <=> $countB;
                });

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

        #available, #selected {
            border: 1px solid #ddd;
            border-radius: 5px;
            min-height: 150px;
            padding: 10px;
            margin: 10px;
            overflow-y: auto;
        }

        .person {
            padding: 8px;
            margin: 5px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: #f9f9f9;
            cursor: grab;
        }

        .person.dragging {
            opacity: 0.5;
        }

        .droppable {
            background-color: #f1f1f1;
            border: 2px dashed #aaa;
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
        <div id="available" class="droppable">
            <h3>Verfügbare Personen</h3>
            <?php
            $stmt = $pdo->query("SELECT id, nachname, vorname FROM personal ORDER BY nachname, vorname");
            $personal = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($personal as $person) {
                echo "<div class='person' draggable='true' data-id='{$person['id']}'>" . htmlspecialchars($person['vorname'] . ' ' . $person['nachname']) . "</div>";
            }
            ?>
        </div>

        <div id="selected" class="droppable">
            <h3>Anwesende Personen</h3>
        </div>

        <input type="hidden" id="anwesendeInput" name="anwesende">
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

    <script>
        const available = document.getElementById('available');
        const selected = document.getElementById('selected');
        const input = document.getElementById('anwesendeInput');

        let dragged;

        document.querySelectorAll('.person').forEach(person => {
            person.addEventListener('dragstart', function() {
                dragged = this;
                setTimeout(() => this.classList.add('dragging'), 0);
            });

            person.addEventListener('dragend', function() {
                this.classList.remove('dragging');
            });
        });

        [available, selected].forEach(container => {
            container.addEventListener('dragover', function(e) {
                e.preventDefault();
                this.classList.add('droppable');
            });

            container.addEventListener('dragleave', function() {
                this.classList.remove('droppable');
            });

            container.addEventListener('drop', function() {
                this.classList.remove('droppable');
                this.appendChild(dragged);
                updateAnwesende();
            });
        });

        function updateAnwesende() {
            const ids = Array.from(selected.children)
                .filter(child => child.classList.contains('person'))
                .map(person => person.dataset.id);
            console.log(ids); // Debugging: Zeige die IDs in der Konsole an
            input.value = ids.join(',');
        }
    </script>
</body>
</html>