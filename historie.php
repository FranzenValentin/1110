<?php
require_once 'parts/session_check.php';
require 'parts/db.php';

$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
$limit = 50;

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
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    $countSql = "SELECT COUNT(*) FROM einsaetze";
    $totalEntries = $pdo->query($countSql)->fetchColumn();

    return ['data' => $stmt->fetchAll(PDO::FETCH_ASSOC), 'totalEntries' => $totalEntries];
}

$result = fetchEinsaetze($pdo, $offset, $limit);
$data = $result['data'];
$totalEntries = $result['totalEntries'];
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
            border-collapse: collapse;
            width: 100%;
            table-layout: auto;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
            white-space: nowrap;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        details {
            cursor: pointer;
        }
        .center {
            text-align: center;
        }
        #load-more-container {
            text-align: center;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <header>
        <h1>Einsatz Historie</h1>
        <?php include 'parts/menue.php'; ?>
    </header>
    <main>
        <section id="box">
        <table>
            <thead>
                <tr>
                    <th>E-Nr. int. </th>
                    <th>E-Nr. LTS</th>
                    <th>Zeit</th>
                    <th>Stichwort</th>
                    <th>Adresse</th>
                    <th>Personal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data as $einsatz): ?>
                    <tr>
                        <td><?= htmlspecialchars($einsatz['interne_einsatznummer']) ?></td>
                        <td><?= htmlspecialchars($einsatz['einsatznummer_lts']) ?></td>
                        <td>
                            <?php
                            $alarmzeit = date('d.m.Y H:i', strtotime($einsatz['alarmuhrzeit']));
                            $zurueckzeit = date('H:i', strtotime($einsatz['zurueckzeit']));
                            echo $alarmzeit . ' - ' . $zurueckzeit;
                            ?>
                        </td>
                        <td><?= htmlspecialchars($einsatz['stichwort']) ?></td>
                        <td><?= htmlspecialchars($einsatz['adresse']) ?></td>
                        <td>
                            <details>
                                <summary>Details</summary>
                                <ul>
                                    <li>STF: <?= htmlspecialchars($einsatz['stf'] ?? 'N/A') ?></li>
                                    <li>MA: <?= htmlspecialchars($einsatz['ma'] ?? 'N/A') ?></li>
                                    <li>ATF: <?= htmlspecialchars($einsatz['atf'] ?? 'N/A') ?></li>
                                    <li>ATM: <?= htmlspecialchars($einsatz['atm'] ?? 'N/A') ?></li>
                                    <li>WTF: <?= htmlspecialchars($einsatz['wtf'] ?? 'N/A') ?></li>
                                    <li>WTM: <?= htmlspecialchars($einsatz['wtm'] ?? 'N/A') ?></li>
                                    <li>PRAKT: <?= htmlspecialchars($einsatz['prakt'] ?? 'N/A') ?></li>
                                </ul>
                            </details>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div>
            <?php if ($offset + $limit < $totalEntries): ?>
                <form method="GET" action="historie.php">
                    <input type="hidden" name="offset" value="<?= $offset + $limit ?>">
                    <button type="submit">Mehr Laden</button>
                </form>
            <?php else: ?>
                <p>Alle Einträge geladen.</p>
            <?php endif; ?>
        </div>
    </section>
    </main>
    <script src="parts/session_timeout.js"></script>
</body>
</html>
