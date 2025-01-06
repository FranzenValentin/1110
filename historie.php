<?php
session_start();
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header('Location: login.php');
    exit;
}
require 'db.php';

// Hilfsfunktion für die Filterung
function fetchFilteredEinsaetze($pdo, $filters) {
    $whereClauses = [];
    $params = [];

    if (!empty($filters['einsatznummer'])) {
        $whereClauses[] = "e.einsatznummer_lts LIKE :einsatznummer";
        $params[':einsatznummer'] = "%" . $filters['einsatznummer'] . "%";
    }
    if (!empty($filters['fahrzeug'])) {
        $whereClauses[] = "e.fahrzeug_name = :fahrzeug";
        $params[':fahrzeug'] = $filters['fahrzeug'];
    }
    if (!empty($filters['stichwort'])) {
        $whereClauses[] = "e.stichwort = :stichwort";
        $params[':stichwort'] = $filters['stichwort'];
    }
    if (!empty($filters['startzeit'])) {
        $whereClauses[] = "STR_TO_DATE(e.alarmuhrzeit, '%d.%m.%Y %H:%i') >= STR_TO_DATE(:startzeit, '%Y-%m-%d %H:%i:%s')";
        $params[':startzeit'] = $filters['startzeit'] . " 00:00:00";
    }
    if (!empty($filters['endzeit'])) {
        $whereClauses[] = "STR_TO_DATE(e.alarmuhrzeit, '%d.%m.%Y %H:%i') <= STR_TO_DATE(:endzeit, '%Y-%m-%d %H:%i:%s')";
        $params[':endzeit'] = $filters['endzeit'] . " 23:59:59";
    }

    $whereSql = $whereClauses ? "WHERE " . implode(" AND ", $whereClauses) : "";
    $sql = "
        SELECT e.interne_einsatznummer, e.einsatznummer_lts, e.alarmuhrzeit, e.zurueckzeit, e.fahrzeug_name, e.adresse, e.stadtteil, e.stichwort,
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
        $whereSql
        ORDER BY 
            CAST(SUBSTRING_INDEX(e.interne_einsatznummer, '_', 1) AS UNSIGNED) DESC,
            CAST(SUBSTRING_INDEX(e.interne_einsatznummer, '_', -1) AS UNSIGNED) DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Filter einlesen
$filters = [
    'einsatznummer' => $_GET['einsatznummer'] ?? null,
    'fahrzeug' => $_GET['fahrzeug'] ?? null,
    'stichwort' => $_GET['stichwort'] ?? null,
    'startzeit' => $_GET['startzeit'] ?? null,
    'endzeit' => $_GET['endzeit'] ?? null,
];

// Fahrzeuge für den Filter abrufen
$fahrzeuge = $pdo->query("SELECT DISTINCT fahrzeug_name FROM einsaetze")->fetchAll(PDO::FETCH_COLUMN);

// Einsätze abrufen
$einsaetze = fetchFilteredEinsaetze($pdo, $filters);
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Einsatz Historie</title>
    <link rel="stylesheet" href="styles.css">
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
        <!-- Filterformular -->
        <section id="filter">
            <h2>Filter</h2>
            <form method="GET" action="historie.php">
                <label for="einsatznummer">Einsatznummer:</label>
                <input type="text" id="einsatznummer" name="einsatznummer" value="<?= htmlspecialchars($filters['einsatznummer'] ?? '') ?>">

                <label for="fahrzeug">Fahrzeug:</label>
                <select id="fahrzeug" name="fahrzeug">
                    <option value="">Alle</option>
                    <?php foreach ($fahrzeuge as $fahrzeug): ?>
                        <option value="<?= htmlspecialchars($fahrzeug) ?>" <?= ($filters['fahrzeug'] ?? '') === $fahrzeug ? 'selected' : '' ?>>
                            <?= htmlspecialchars($fahrzeug) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label for="stichwort">Stichwort:</label>
                <input type="text" id="stichwort" name="stichwort" value="<?= htmlspecialchars($filters['stichwort'] ?? '') ?>">

                <label for="startzeit">Startzeit:</label>
                <input type="date" id="startzeit" name="startzeit" value="<?= htmlspecialchars($filters['startzeit'] ?? '') ?>">

                <label for="endzeit">Endzeit:</label>
                <input type="date" id="endzeit" name="endzeit" value="<?= htmlspecialchars($filters['endzeit'] ?? '') ?>">

                <button type="submit">Filtern</button>
            </form>
        </section>

        <!-- Einsatztabelle -->
        <section id="letzte-einsaetze">
            <table>
                <thead>
                    <tr>
                        <th>Interne Einsatznummer</th>
                        <th>Einsatznummer</th>
                        <th>Stichwort</th>
                        <th>Alarmzeit</th>
                        <th>Zurückzeit</th>
                        <th>Fahrzeug</th>
                        <th>Straße</th>
                        <th>Stadtteil</th>
                        <th>Personal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($einsaetze)): ?>
                        <tr>
                            <td colspan="9" style="text-align: center;">Keine Einsätze gefunden.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($einsaetze as $row): ?>
                            <?php
                            $personal = array_filter([
                                $row['stf'] ? "StF: " . htmlspecialchars($row['stf']) : null,
                                $row['ma'] ? "Ma: " . htmlspecialchars($row['ma']) : null,
                                $row['atf'] ? "AtF: " . htmlspecialchars($row['atf']) : null,
                                $row['atm'] ? "AtM: " . htmlspecialchars($row['atm']) : null,
                                $row['wtf'] ? "WtF: " . htmlspecialchars($row['wtf']) : null,
                                $row['wtm'] ? "WtM: " . htmlspecialchars($row['wtm']) : null,
                                $row['prakt'] ? "Prakt: " . htmlspecialchars($row['prakt']) : null,
                            ]);
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($row['interne_einsatznummer']) ?></td>
                                <td><?= htmlspecialchars($row['einsatznummer_lts']) ?></td>
                                <td><?= htmlspecialchars($row['stichwort']) ?></td>
                                <td><?= htmlspecialchars($row['alarmuhrzeit']) ?></td>
                                <td><?= htmlspecialchars($row['zurueckzeit']) ?></td>
                                <td><?= htmlspecialchars($row['fahrzeug_name']) ?></td>
                                <td><?= htmlspecialchars($row['adresse']) ?></td>
                                <td><?= htmlspecialchars($row['stadtteil']) ?></td>
                                <td>
                                    <details>
                                        <summary>Details anzeigen</summary>
                                        <?= implode('<br>', $personal) ?>
                                    </details>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>
    </main>
</body>
</html>
