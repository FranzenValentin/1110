<?php
require_once 'session_check.php';
require 'db.php';

// Hilfsfunktion: Filterung der Einsätze
function fetchFilteredEinsaetze($pdo, $filters) {
    $whereClauses = [];
    $params = [];
    $limitClause = "";

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
    if (!empty($filters['adresse'])) {
        $whereClauses[] = "(e.adresse LIKE :adresse OR e.stadtteil LIKE :adresse)";
        $params[':adresse'] = "%" . $filters['adresse'] . "%";
    }

    $whereSql = $whereClauses ? "WHERE " . implode(" AND ", $whereClauses) : "";

    // Pagination: Berechnung von Offset und Limit
    if (!empty($filters['page']) && !empty($filters['entriesPerPage'])) {
        $page = (int)$filters['page'];
        $entriesPerPage = (int)$filters['entriesPerPage'];
        $offset = ($page - 1) * $entriesPerPage;
        $limitClause = "LIMIT $offset, $entriesPerPage";
    }

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
        $limitClause
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    // Gesamtanzahl der Einträge für Pagination berechnen
    $countSql = "
        SELECT COUNT(*) FROM einsaetze e
        $whereSql
    ";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $totalEntries = $countStmt->fetchColumn();

    return ['data' => $stmt->fetchAll(PDO::FETCH_ASSOC), 'totalEntries' => $totalEntries];
}

// AJAX-Anfrage
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $filters = json_decode(file_get_contents('php://input'), true);
    $result = fetchFilteredEinsaetze($pdo, $filters);
    header('Content-Type: application/json');
    echo json_encode($result);
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

        let currentPage = 1; // Startseite
        let entriesPerPage = 20; // Standard-Einträge pro Seite

        async function filterEinsaetze(page = 1) {
            currentPage = page;

            const einsatznummer = document.getElementById('einsatznummer').value;
            const stichwort = document.getElementById('stichwort').value;
            const datum = document.getElementById('datum').value;
            const adresse = document.getElementById('adresse').value;

            const response = await fetch('historie.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ einsatznummer, stichwort, datum, adresse, page: currentPage, entriesPerPage })
            });

            const { data, totalEntries } = await response.json();
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

                renderPagination(totalEntries);
            }
        }

        function renderPagination(totalEntries) {
            const paginationDiv = document.getElementById('pagination');
            paginationDiv.innerHTML = '';

            const totalPages = Math.ceil(totalEntries / entriesPerPage);

            for (let i = 1; i <= totalPages; i++) {
                const button = document.createElement('button');
                button.textContent = i;
                button.className = i === currentPage ? 'active' : '';
                button.onclick = () => filterEinsaetze(i);
                paginationDiv.appendChild(button);
            }

            // Einträge anzeigen (z. B., "1-20 von 300")
            const fromEntry = (currentPage - 1) * entriesPerPage + 1;
            const toEntry = Math.min(currentPage * entriesPerPage, totalEntries);
            const entriesStatus = document.getElementById('entries-status');
            entriesStatus.textContent = `${fromEntry}-${toEntry} von ${totalEntries}`;
        }

        function updateEntriesPerPage() {
            const select = document.getElementById('entries-per-page');
            const newEntriesPerPage = parseInt(select.value, 10);
            if (newEntriesPerPage !== entriesPerPage) {
                entriesPerPage = newEntriesPerPage;
                filterEinsaetze(1); // Lade die erste Seite mit der neuen Einstellung
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('input').forEach(input => input.addEventListener('input', () => filterEinsaetze(1)));
            document.getElementById('entries-per-page').addEventListener('change', updateEntriesPerPage);
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
        <form method="POST" action="index.php" class="back-form">
            <button type="submit">Zurück</button>
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
            <input type="text" id="adresse" placeholder="Adresse oder Stadtteil" style="width: auto;">
            <button type="button" onclick="clearFiltersAndCollapse()">Filter entfernen</button>
        </form>

        <script>
            function clearFiltersAndCollapse() {
                // Leert alle Eingabefelder
                document.getElementById('einsatznummer').value = '';
                document.getElementById('stichwort').value = '';
                document.getElementById('datum').value = '';
                document.getElementById('adresse').value = '';
                
                // Aktualisiert die Tabelle ohne Filter
                filterEinsaetze();

                // Einklappen des Filterbereichs
                const filterSection = document.getElementById('filter-section');
                const filterButton = document.getElementById('toggle-filter-btn');
                filterSection.style.display = 'none';
                filterButton.textContent = 'Filter einblenden';
            }
        </script>

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
            <!-- Pagination -->
                <div id="pagination" style="margin-top: 15px; text-align: center;"></div>
                <div id="entries-status" style="margin-top: 10px; text-align: center; font-weight: bold;"></div>
                <div style="margin-top: 15px; text-align: center;">
                    <label for="entries-per-page" style="margin-right: 10px;">Einträge pro Seite:</label>
                    <select id="entries-per-page" onchange="updateEntriesPerPage()" style="padding: 5px;">
                        <option value="10">10</option>
                        <option value="20" selected>20</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>

        </section>
    </main>
</body>
</html>
