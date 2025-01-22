<?php
require_once 'session_check.php'; // Session-Überprüfung
require 'db.php'; // Verbindung zur Datenbank herstellen

// Hilfsfunktion: Einsätze laden
function fetchEinsaetze($pdo, $offset, $limit)
{
    $sql = "
        SELECT e.interne_einsatznummer, e.einsatznummer_lts, e.alarmuhrzeit, e.zurueckzeit, e.stichwort, e.adresse, e.stadtteil,
               p1.nachname AS stf, p2.nachname AS ma, p3.nachname AS atf, p4.nachname AS atm,
               p5.nachname AS wtf, p6.nachname AS wtm, p7.nachname AS prakt
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
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f9f9f9;
            margin: 0;
            padding: 0;
        }
        header {
            background-color: #007bff;
            color: white;
            padding: 15px;
            text-align: center;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        table th, table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        table th {
            background-color: #f4f4f4;
            color: #333;
        }
        #load-more {
            display: block;
            margin: 20px auto;
            padding: 10px 20px;
            font-size: 16px;
            color: white;
            background-color: #007bff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        #load-more:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <header>
        <h1>Einsatz Historie</h1>
    </header>
    <main>
        <table id="einsaetze-table">
            <thead>
                <tr>
                    <th>Interne Nr.</th>
                    <th>Einsatznummer</th>
                    <th>Alarmzeit</th>
                    <th>Rückkehrzeit</th>
                    <th>Stichwort</th>
                    <th>Adresse</th>
                    <th>Stadtteil</th>
                    <th>STF</th>
                    <th>MA</th>
                    <th>ATF</th>
                    <th>ATM</th>
                    <th>WTF</th>
                    <th>WTM</th>
                    <th>Praktikant</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($einsaetze['data'] as $einsatz): ?>
                    <tr>
                        <td><?= htmlspecialchars($einsatz['interne_einsatznummer']) ?></td>
                        <td><?= htmlspecialchars($einsatz['einsatznummer_lts']) ?></td>
                        <td><?= htmlspecialchars($einsatz['alarmuhrzeit']) ?></td>
                        <td><?= htmlspecialchars($einsatz['zurueckzeit']) ?></td>
                        <td><?= htmlspecialchars($einsatz['stichwort']) ?></td>
                        <td><?= htmlspecialchars($einsatz['adresse']) ?></td>
                        <td><?= htmlspecialchars($einsatz['stadtteil']) ?></td>
                        <td><?= htmlspecialchars($einsatz['stf']) ?></td>
                        <td><?= htmlspecialchars($einsatz['ma']) ?></td>
                        <td><?= htmlspecialchars($einsatz['atf']) ?></td>
                        <td><?= htmlspecialchars($einsatz['atm']) ?></td>
                        <td><?= htmlspecialchars($einsatz['wtf']) ?></td>
                        <td><?= htmlspecialchars($einsatz['wtm']) ?></td>
                        <td><?= htmlspecialchars($einsatz['prakt']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <button id="load-more">Mehr laden</button>
    </main>

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
                    <td>${einsatz.interne_einsatznummer || ''}</td>
                    <td>${einsatz.einsatznummer_lts || ''}</td>
                    <td>${einsatz.alarmuhrzeit || ''}</td>
                    <td>${einsatz.zurueckzeit || ''}</td>
                    <td>${einsatz.stichwort || ''}</td>
                    <td>${einsatz.adresse || ''}</td>
                    <td>${einsatz.stadtteil || ''}</td>
                    <td>${einsatz.stf || ''}</td>
                    <td>${einsatz.ma || ''}</td>
                    <td>${einsatz.atf || ''}</td>
                    <td>${einsatz.atm || ''}</td>
                    <td>${einsatz.wtf || ''}</td>
                    <td>${einsatz.wtm || ''}</td>
                    <td>${einsatz.prakt || ''}</td>
                `;
                tbody.appendChild(row);
            });

            offset += limit;

            // Verstecke die Schaltfläche, wenn alle Einträge geladen sind
            if (offset >= totalEntries) {
                document.getElementById('load-more').style.display = 'none';
            }
        }

        document.getElementById('load-more').addEventListener('click', loadMoreEinsaetze);
    </script>
</body>
</html>
