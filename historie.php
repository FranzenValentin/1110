<?php
require_once 'session_check.php';
require 'db.php';

// Hilfsfunktion: Einsätze laden
function fetchEinsaetze($pdo, $offset, $limit)
{
    $sql = "
        SELECT 
            e.interne_einsatznummer, 
            e.einsatznummer_lts, 
            e.alarmuhrzeit, 
            e.zurueckzeit, 
            e.stichwort, 
            CONCAT(e.adresse, ', ', e.stadtteil) AS adresse,
            p1.nachname AS stf, 
            p2.nachname AS ma, 
            p3.nachname AS atf, 
            p4.nachname AS atm, 
            p5.nachname AS wtf, 
            p6.nachname AS wtm, 
            p7.nachname AS prakt
        FROM einsaetze e
        LEFT JOIN dienste b ON e.dienst_id = b.id
        LEFT JOIN personal p1 ON b.stf_id = p1.id
        LEFT JOIN personal p2 ON b.ma_id = p2.id
        LEFT JOIN personal p3 ON b.atf_id = p3.id
        LEFT JOIN personal p4 ON b.atm_id = p4.id
        LEFT JOIN personal p5 ON b.wtf_id = p5.id
        LEFT JOIN personal p6 ON b.wtm_id = p6.id
        LEFT JOIN personal p7 ON b.prakt_id = p7.id
        ORDER BY STR_TO_DATE(e.alarmuhrzeit, '%d.%m.%Y %H:%i') DESC
        LIMIT :offset, :limit
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->execute();

    // Gesamte Anzahl der Einsätze
    $countSql = "SELECT COUNT(*) FROM einsaetze";
    $countStmt = $pdo->query($countSql);
    $totalEntries = $countStmt->fetchColumn();

    return ['data' => $stmt->fetchAll(PDO::FETCH_ASSOC), 'totalEntries' => $totalEntries];
}

// AJAX-Anfrage verarbeiten
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $offset = isset($_POST['offset']) ? (int)$_POST['offset'] : 0;
    $limit = isset($_POST['limit']) ? (int)$_POST['limit'] : 50;

    $result = fetchEinsaetze($pdo, $offset, $limit);
    header('Content-Type: application/json');
    echo json_encode($result);
    exit;
}

// Standardmäßig die ersten 50 Einsätze laden
$einsaetze = fetchEinsaetze($pdo, 0, 50);
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Einsatz Historie</title>
    <link rel="stylesheet" href="styles.css">
    <script>
        let offset = 50; // Start mit den ersten 50 Einträgen
        const limit = 50; // Anzahl der Einträge pro Ladevorgang

        async function loadMoreEinsaetze() {
            const response = await fetch('historie.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ offset, limit })
            });

            const { data, totalEntries } = await response.json();
            const tbody = document.querySelector('#einsaetze-table tbody');

            if (data.length === 0) {
                document.getElementById('load-more').style.display = 'none';
                return;
            }

            data.forEach(einsatz => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${einsatz.alarmuhrzeit}</td>
                    <td>${einsatz.stichwort}</td>
                    <td>${einsatz.adresse}</td>
                    <td>
                        <button onclick="toggleDetails(this)">Details anzeigen</button>
                        <div class="details" style="display: none;">
                            <p>STF: ${einsatz.stf || '-'}</p>
                            <p>MA: ${einsatz.ma || '-'}</p>
                            <p>ATF: ${einsatz.atf || '-'}</p>
                            <p>ATM: ${einsatz.atm || '-'}</p>
                            <p>WTF: ${einsatz.wtf || '-'}</p>
                            <p>WTM: ${einsatz.wtm || '-'}</p>
                            <p>Praktikant: ${einsatz.prakt || '-'}</p>
                        </div>
                    </td>
                `;
                tbody.appendChild(row);
            });

            offset += limit;
        }

        function toggleDetails(button) {
            const details = button.nextElementSibling;
            details.style.display = details.style.display === 'none' ? 'block' : 'none';
        }
    </script>
</head>
<link rel="stylesheet" href="styles.css">
<body>
    <h1>Einsatz Historie</h1>

    <table id="einsaetze-table" border="1">
        <thead>
            <tr>
                <th>Alarmzeit</th>
                <th>Stichwort</th>
                <th>Adresse</th>
                <th>Funktionen</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($einsaetze['data'] as $einsatz): ?>
                <tr>
                    <td><?= htmlspecialchars($einsatz['alarmuhrzeit']) ?></td>
                    <td><?= htmlspecialchars($einsatz['stichwort']) ?></td>
                    <td><?= htmlspecialchars($einsatz['adresse']) ?></td>
                    <td>
                        <button onclick="toggleDetails(this)">Details anzeigen</button>
                        <div class="details" style="display: none;">
                            <p>STF: <?= htmlspecialchars($einsatz['stf'] ?? '-') ?></p>
                            <p>MA: <?= htmlspecialchars($einsatz['ma'] ?? '-') ?></p>
                            <p>ATF: <?= htmlspecialchars($einsatz['atf'] ?? '-') ?></p>
                            <p>ATM: <?= htmlspecialchars($einsatz['atm'] ?? '-') ?></p>
                            <p>WTF: <?= htmlspecialchars($einsatz['wtf'] ?? '-') ?></p>
                            <p>WTM: <?= htmlspecialchars($einsatz['wtm'] ?? '-') ?></p>
                            <p>Praktikant: <?= htmlspecialchars($einsatz['prakt'] ?? '-') ?></p>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <button id="load-more" onclick="loadMoreEinsaetze()">Mehr laden</button>
</body>
</html>
