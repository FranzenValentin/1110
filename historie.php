<?php
session_start();
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header('Location: login.php');
    exit;
}
require 'db.php';

// Hilfsfunktion: Filterung der Einsätze
function fetchFilteredEinsaetze($pdo, $filters) {
    $whereClauses = [];
    $params = [];

    if (!empty($filters['einsatznummer'])) {
        $whereClauses[] = "e.einsatznummer_lts LIKE :einsatznummer";
        $params[':einsatznummer'] = "%" . $filters['einsatznummer'] . "%";
    }
    if (!empty($filters['stichwort'])) {
        $whereClauses[] = "e.stichwort LIKE :stichwort";
        $params[':stichwort'] = "%" . $filters['stichwort'] . "%";
    }
    if (!empty($filters['datum'])) {
        $whereClauses[] = "STR_TO_DATE(e.alarmuhrzeit, '%d.%m.%Y') = STR_TO_DATE(:datum, '%Y-%m-%d')";
        $params[':datum'] = $filters['datum'];
    }

    $whereSql = $whereClauses ? "WHERE " . implode(" AND ", $whereClauses) : "";
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
        $whereSql
        ORDER BY STR_TO_DATE(e.alarmuhrzeit, '%d.%m.%Y %H:%i') DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// AJAX-Anfrage
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $filters = [
        'einsatznummer' => $_POST['einsatznummer'] ?? null,
        'stichwort' => $_POST['stichwort'] ?? null,
        'datum' => $_POST['datum'] ?? null,
    ];
    $einsaetze = fetchFilteredEinsaetze($pdo, $filters);
    header('Content-Type: application/json');
    echo json_encode($einsaetze);
    exit;
}

// Standardmäßig alle Einsätze abrufen
$einsaetze = fetchFilteredEinsaetze($pdo, []);
?>


<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Einsatz Historie</title>
    <link rel="stylesheet" href="styles.css">
    <script>
        async function filterEinsaetze() {
            const einsatznummer = document.getElementById('einsatznummer').value;
            const stichwort = document.getElementById('stichwort').value;
            const datum = document.getElementById('datum').value;

            const response = await fetch('historie.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ einsatznummer, stichwort, datum })
            });

            const data = await response.json();
            const tbody = document.querySelector('#einsaetze-table tbody');
            tbody.innerHTML = '';

            if (data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="8" style="text-align: center;">Keine Einsätze gefunden.</td></tr>';
            } else {
                data.forEach(einsatz => {
                    const personal = [
                        einsatz.stf ? `StF: ${einsatz.stf}` : null,
                        einsatz.ma ? `Ma: ${einsatz.ma}` : null,
                        einsatz.atf ? `AtF: ${einsatz.atf}` : null,
                        einsatz.atm ? `AtM: ${einsatz.atm}` : null,
                        einsatz.wtf ? `WtF: ${einsatz.wtf}` : null,
                        einsatz.wtm ? `WtM: ${einsatz.wtm}` : null,
                        einsatz.prakt ? `Prakt: ${einsatz.prakt}` : null,
                    ].filter(Boolean).join('<br>');

                    tbody.innerHTML += `
                        <tr>
                            <td>${einsatz.interne_einsatznummer}</td>
                            <td>${einsatz.einsatznummer_lts}</td>
                            <td>${einsatz.stichwort}</td>
                            <td>${einsatz.alarmuhrzeit}</td>
                            <td>${einsatz.zurueckzeit}</td>
                            <td>${einsatz.adresse}</td>
                            <td>${einsatz.stadtteil}</td>
                            <td><details><summary>Details anzeigen</summary>${personal}</details></td>
                        </tr>
                    `;
                });
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('input').forEach(input => input.addEventListener('input', filterEinsaetze));
        });
    </script>
</head>
<body>
    <header>
        <h1>Einsatz Historie</h1>
        <form method="POST" action="logout.php" class="logout-form">
            <button type="submit">Logout</button>
        </form>
    </header>

    <main>
        <!-- Filter -->
        <section id="filter">
            <h2>Filter</h2>
            <form>
                <label for="einsatznummer">Einsatznummer:</label>
                <input type="text" id="einsatznummer" placeholder="Einsatznummer">

                <label for="stichwort">Stichwort:</label>
                <input type="text" id="stichwort" placeholder="Stichwort">

                <label for="datum">Datum:</label>
                <input type="date" id="datum">
            </form>
        </section>

        <!-- Tabelle -->
        <section id="letzte-einsaetze">
            <table id="einsaetze-table">
                <thead>
                    <tr>
                        <th>Interne Einsatznummer</th>
                        <th>Einsatznummer</th>
                        <th>Stichwort</th>
                        <th>Alarmzeit</th>
                        <th>Zurückzeit</th>
                        <th>Straße</th>
                        <th>Stadtteil</th>
                        <th>Personal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($einsaetze as $einsatz): ?>
                        <?php
                        $personal = [
                            $einsatz['stf'] ? "StF: " . htmlspecialchars($einsatz['stf']) : null,
                            $einsatz['ma'] ? "Ma: " . htmlspecialchars($einsatz['ma']) : null,
                            $einsatz['atf'] ? "AtF: " . htmlspecialchars($einsatz['atf']) : null,
                            $einsatz['atm'] ? "AtM: " . htmlspecialchars($einsatz['atm']) : null,
                            $einsatz['wtf'] ? "WtF: " . htmlspecialchars($einsatz['wtf']) : null,
                            $einsatz['wtm'] ? "WtM: " . htmlspecialchars($einsatz['wtm']) : null,
                            $einsatz['prakt'] ? "Prakt: " . htmlspecialchars($einsatz['prakt']) : null,
                        ];
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($einsatz['interne_einsatznummer']) ?></td>
                            <td><?= htmlspecialchars($einsatz['einsatznummer_lts']) ?></td>
                            <td><?= htmlspecialchars($einsatz['stichwort']) ?></td>
                            <td><?= htmlspecialchars($einsatz['alarmuhrzeit']) ?></td>
                            <td><?= htmlspecialchars($einsatz['zurueckzeit']) ?></td>
                            <td><?= htmlspecialchars($einsatz['adresse']) ?></td>
                            <td><?= htmlspecialchars($einsatz['stadtteil']) ?></td>
                            <td>
                                <details>
                                    <summary>Details anzeigen</summary>
                                    <?= implode('<br>', array_filter($personal)) ?>
                                </details>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    </main>
</body>
</html>
