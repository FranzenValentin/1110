<?php
require_once 'parts/db.php';

$error = '';
$zuweisungDetails = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Array von IDs der anwesenden Personen aus dem Formular
    $anwesende = isset($_POST['anwesende']) ? explode(',', $_POST['anwesende']) : [];

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

            // Schritt 2: Filter nach Qualifikationen und gerechte Zuweisung
            $roles = [
                'stf_id' => 'stf',
                'ma_id' => 'ma',
                'atf_id' => 'tf',
                'atm_id' => 'none',
                'wtf_id' => 'tf',
                'wtm_id' => 'none'
            ];

            $zuweisung = [];
            $zuweisungDetails = [];
            $assignedPersons = []; // Personen, die bereits zugewiesen wurden

            foreach ($roles as $role => $qualification) {
                // Personen mit der benötigten Qualifikation herausfiltern und nicht bereits zugewiesene ausschließen
                $query = "
                    SELECT id, nachname, vorname
                    FROM personal
                    WHERE id IN (" . implode(',', array_map('intval', $anwesende)) . ")
                    " . ($qualification !== 'none' ? "AND $qualification = 1" : "") . "
                    AND id NOT IN (" . implode(',', array_map('intval', $assignedPersons)) . ")
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
                $selectedPerson = $eligiblePersons[0];
                $zuweisung[$role] = $selectedPerson['id'];
                $zuweisungDetails[$role] = [
                    'id' => $selectedPerson['id'],
                    'name' => $selectedPerson['vorname'] . ' ' . $selectedPerson['nachname'],
                    'dienste' => $dienstCounter[$role][$selectedPerson['id']] ?? 0,
                ];

                // Füge die Person zur Liste der bereits zugewiesenen hinzu
                $assignedPersons[] = $selectedPerson['id'];
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
    <link rel="stylesheet" href="style.css">
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
            input.value = ids.join(',');
        }
    </script>
</body>
</html>
