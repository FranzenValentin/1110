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
        $whereClauses[] = "DATE(STR_TO_DATE(e.alarmuhrzeit, '%d.%m.%Y %H:%i')) = :datum";
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
    $filters = json_decode(file_get_contents('php://input'), true);
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
        // Live-Filter Funktion
        async function filterEinsaetze() {
            const einsatznummer = document.getElementById('einsatznummer').value;
            const stichwort = document.getElementById('stichwort').value;
            const datum = document.getElementById('datum').value;
            const adresse = document.getElementById('adresse').value;

            const response = await fetch('historie.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ einsatznummer, stichwort, datum, adresse })
            });

            const data = await response.json();
            const tbody = document.querySelector('#einsaetze-table tbody');
            tbody.innerHTML = '';

            if (data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="9" style="text-align: center;">Keine Einsätze gefunden.</td></tr>';
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

        // Toggle für den Filterbereich
        function toggleFilter() {
            const filterSection = document.getElementById('filter-section');
            const filterButton = document.getElementById('toggle-filter-btn');
            const isHidden = filterSection.style.display === 'none';
            filterSection.style.display = isHidden ? 'block' : 'none';
            filterButton.textContent = isHidden ? 'Filter ausblenden' : 'Filter einblenden';
        }

        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('input').forEach(input => input.addEventListener('input', filterEinsaetze));
            filterEinsaetze(); // Initiales Laden
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
        <!-- Filter Toggle Button -->
        <button id="toggle-filter-btn" onclick="toggleFilter()">Filter einblenden</button>

        <!-- Filterbereich -->
        <section id="filter-section" style="display: none; margin-top: 15px;">
            <form style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
                <label for="einsatznummer">Einsatznummer:</label>
                <input type="text" id="einsatznummer" placeholder="Einsatznummer">

                <label for="stichwort">Stichwort:</label>
                <input type="text" id="stichwort" placeholder="Stichwort" style="width: auto;">

                <label for="datum">Datum:</label>
                <input type="date" id="datum">

                <label for="adresse">Adresse:</label>
                <input type="text" id="adresse" placeholder="Adresse" style="width: auto;">
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
                        <th>Adresse</th>
                        <th>Stadtteil</th>
                        <th>Personal</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td colspan="9" style="text-align: center;">Daten werden geladen...</td></tr>
                </tbody>
            </table>
        </section>
    </main>
</body>
</html>
