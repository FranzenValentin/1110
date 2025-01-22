<?php
require_once 'session_check.php'; // Session-Überprüfung
require 'db.php'; // Verbindung zur Datenbank herstellen

// Hilfsfunktion: Einsätze laden
function fetchEinsaetze($pdo, $offset, $limit)
{
    $sql = "
        SELECT e.interne_einsatznummer, e.einsatznummer_lts, e.alarmuhrzeit, e.zurueckzeit, e.stichwort, 
               CONCAT(e.adresse, ', ', e.stadtteil) AS adresse,
               p1.nachname AS stf, p2.nachname AS ma, p3.nachname AS atf, 
               p4.nachname AS atm, p5.nachname AS wtf, p6.nachname AS wtm, p7.nachname AS prakt
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
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
            white-space: nowrap; /* Spaltenbreite nur nach Bedarf */
        }
        th {
            background-color: #f4f4f4;
        }
        details {
            cursor: pointer;
        }
        .btn {
            background-color: #007bff;
            color: white;
            padding: 10px 15px;
            border: none;
            cursor: pointer;
            border-radius: 4px;
        }
        .btn:hover {
            background-color: #0056b3;
        }
    </style>
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
                    <td>${einsatz.interne_einsatznummer}</td>
                    <td>${einsatz.einsatznummer_lts}</td>
                    <td>${formatDate(einsatz.alarmuhrzeit)} - ${formatDate(einsatz.zurueckzeit)}</td>
                    <td>${einsatz.stichwort}</td>
                    <td>${einsatz.adresse}</td>
                    <td>
                        <details>
                            <summary>Details anzeigen</summary>
                            <p>STF: ${einsatz.stf || 'N/A'}</p>
                            <p>MA: ${einsatz.ma || 'N/A'}</p>
                            <p>ATF: ${einsatz.atf || 'N/A'}</p>
                            <p>ATM: ${einsatz.atm || 'N/A'}</p>
                            <p>WTF: ${einsatz.wtf || 'N/A'}</p>
                            <p>WTM: ${einsatz.wtm || 'N/A'}</p>
                            <p>Praktikant: ${einsatz.prakt || 'N/A'}</p>
                        </details>
                    </td>
                `;

                tbody.appendChild(row);
            });

            offset += limit; // Offset aktualisieren
            if (offset >= totalEntries) {
                document.getElementById('load-more').style.display = 'none';
            }
        }

        function formatDate(dateString) {
            const options = { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' };
            const date = new Date(dateString);
            return date.toLocaleString('de-DE', options);
        }

        document.addEventListener('DOMContentLoaded', () => {
            document.getElementById('load-more').addEventListener('click', loadMoreEinsaetze);
        });
    </script>
</head>
<body>
    <header>
        <h1>Einsatzhistorie</h1>
    </header>

    <table id="einsaetze-table">
        <thead>
            <tr>
                <th>Interne Einsatznummer</th>
                <th>Einsatznummer LTS</th>
                <th>Zeit</th>
                <th>Stichwort</th>
                <th>Adresse</th>
                <th>Details</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($einsaetze['data'] as $einsatz): ?>
                <tr>
                    <td><?= htmlspecialchars($einsatz['interne_einsatznummer']) ?></td>
                    <td><?= htmlspecialchars($einsatz['einsatznummer_lts']) ?></td>
                    <td><?= htmlspecialchars(date('d.m.Y H:i', strtotime($einsatz['alarmuhrzeit']))) ?> - <?= htmlspecialchars(date('H:i', strtotime($einsatz['zurueckzeit']))) ?></td>
                    <td><?= htmlspecialchars($einsatz['stichwort']) ?></td>
                    <td><?= htmlspecialchars($einsatz['adresse']) ?></td>
                    <td>
                        <details>
                            <summary>Details anzeigen</summary>
                            <p>STF: <?= htmlspecialchars($einsatz['stf'] ?? 'N/A') ?></p>
                            <p>MA: <?= htmlspecialchars($einsatz['ma'] ?? 'N/A') ?></p>
                            <p>ATF: <?= htmlspecialchars($einsatz['atf'] ?? 'N/A') ?></p>
                            <p>ATM: <?= htmlspecialchars($einsatz['atm'] ?? 'N/A') ?></p>
                            <p>WTF: <?= htmlspecialchars($einsatz['wtf'] ?? 'N/A') ?></p>
                            <p>WTM: <?= htmlspecialchars($einsatz['wtm'] ?? 'N/A') ?></p>
                            <p>Praktikant: <?= htmlspecialchars($einsatz['prakt'] ?? 'N/A') ?></p>
                        </details>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <button id="load-more" class="btn">Mehr laden</button>
</body>
</html>