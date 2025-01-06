<?php
session_start();
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header('Location: login.php');
    exit;
}

require 'db.php';

// Zeitzone setzen
date_default_timezone_set('Europe/Berlin');
$aktuelleUhrzeit = date('d.m.Y H:i');

// CSRF-Schutz
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Hilfsfunktionen
function fetchFromDatabase($pdo, $query, $params = [])
{
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function fetchSingleFromDatabase($pdo, $query, $params = [])
{
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Fahrzeuge abrufen
$fahrzeuge = fetchFromDatabase($pdo, "SELECT id, name FROM fahrzeuge");

// Fahrzeug-ID setzen
$fahrzeugId = isset($_GET['fahrzeug']) ? (int)$_GET['fahrzeug'] : fetchSingleFromDatabase($pdo, "SELECT id FROM fahrzeuge WHERE name = 'LHF 1' LIMIT 1")['id'];

// Aktuellen Dienst abrufen
$dienstQuery = "
    SELECT id, inDienstZeit, ausserDienstZeit 
    FROM dienste 
    WHERE fahrzeug_id = :fahrzeug_id 
    AND STR_TO_DATE(inDienstZeit, '%d.%m.%Y %H:%i') <= STR_TO_DATE(:aktuelleUhrzeit, '%d.%m.%Y %H:%i')
    AND (STR_TO_DATE(ausserDienstZeit, '%d.%m.%Y %H:%i') > STR_TO_DATE(:aktuelleUhrzeit, '%d.%m.%Y %H:%i') 
         OR ausserDienstZeit IS NULL)
    ORDER BY STR_TO_DATE(inDienstZeit, '%d.%m.%Y %H:%i') DESC 
    LIMIT 1
";
$currentDienst = fetchSingleFromDatabase($pdo, $dienstQuery, [
    ':fahrzeug_id' => $fahrzeugId,
    ':aktuelleUhrzeit' => $aktuelleUhrzeit,
]);

$dienstVorhanden = !empty($currentDienst);
$dienstId = $currentDienst['id'] ?? null;
$inDienstZeit = $currentDienst['inDienstZeit'] ?? 'Keine Daten';
$ausserDienstZeit = $currentDienst['ausserDienstZeit'] ?? 'Keine Daten';

// Aktuelle Besatzung abrufen
$roles = [
    'stf' => 'Staffel-Führer',
    'ma' => 'Maschinist',
    'atf' => 'Angriffstrupp-Führer',
    'atm' => 'Angriffstrupp-Mann',
    'wtf' => 'Wassertrupp-Führer',
    'wtm' => 'Wassertrupp-Mann',
    'prakt' => 'Praktikant'
];
$besatzung = [];
if ($dienstId) {
    $besatzungQuery = "
        SELECT 
            CASE 
                WHEN p.id = d.stf_id THEN 'Staffel-Führer'
                WHEN p.id = d.ma_id THEN 'Maschinist'
                WHEN p.id = d.atf_id THEN 'Angriffstrupp-Führer'
                WHEN p.id = d.atm_id THEN 'Angriffstrupp-Mann'
                WHEN p.id = d.wtf_id THEN 'Wassertrupp-Führer'
                WHEN p.id = d.wtm_id THEN 'Wassertrupp-Mann'
                WHEN p.id = d.prakt_id THEN 'Praktikant'
            END AS rolle,
            CONCAT(p.vorname, ' ', p.nachname) AS name
        FROM personal p
        JOIN dienste d ON p.id IN (
            d.stf_id, d.ma_id, d.atf_id, d.atm_id, d.wtf_id, d.wtm_id, d.prakt_id
        )
        WHERE d.id = :dienst_id
    ";
    $besatzung = fetchFromDatabase($pdo, $besatzungQuery, [':dienst_id' => $dienstId]);
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Einsatzverwaltung</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<header>
    <h1>Einsatzverwaltungssystem</h1>
    <form method="POST" action="logout.php" class="logout-form">
        <button type="submit">Logout</button>
    </form>
</header>

<main>
    <!-- Aktueller Dienst -->
    <section id="aktueller-dienste">
        <h2>
            Aktueller Dienst mit dem 
            <form method="GET" class="dropdown-form" style="display: inline;">
                <select name="fahrzeug" onchange="this.form.submit()">
                    <?php foreach ($fahrzeuge as $fahrzeug): ?>
                        <option value="<?= htmlspecialchars($fahrzeug['id']) ?>" <?= $fahrzeugId == $fahrzeug['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($fahrzeug['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
            <?= "vom $inDienstZeit bis zum $ausserDienstZeit"; ?>
        </h2>

        <!-- Besatzungstabelle -->
        <?php if ($dienstVorhanden): ?>
            <table>
                <thead>
                    <tr>
                        <th>Funktion</th>
                        <th>Aktuell zugewiesen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($roles as $key => $label): ?>
                        <tr>
                            <td><?= htmlspecialchars($label) ?></td>
                            <td>
                                <?php
                                $person = array_filter($besatzung, fn($b) => $b['rolle'] === $label);
                                echo $person ? htmlspecialchars(array_values($person)[0]['name']) : '<em>NICHT BESETZT</em>';
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>

    <?php if ($dienstVorhanden): ?>
        <!-- Neuer Einsatz -->
        <section id="neuer-einsatz">
            <h2>Neuen Einsatz eintragen</h2>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                <table>
                    <tr>
                        <td><input type="text" name="einsatznummer_lts" placeholder="Einsatznummer LTS"></td>
                        <td><input type="datetime-local" name="alarmuhrzeit" placeholder="Alarmzeit" required></td>
                        <td><input type="datetime-local" name="zurueckzeit" placeholder="Zurückzeit"></td>
                        <td>
                            <select name="stichwort" required>
                                <option value="">Stichwort wählen</option>
                                <?php
                                $stichworte = fetchFromDatabase($pdo, "SELECT stichwort FROM stichworte ORDER BY stichwort");
                                foreach ($stichworte as $stichwort) {
                                    echo "<option value=\"" . htmlspecialchars($stichwort['stichwort']) . "\">" . htmlspecialchars($stichwort['stichwort']) . "</option>";
                                }
                                ?>
                            </select>
                        </td>
                        <td><input type="text" name="adresse" placeholder="Straße und Hausnummer" required></td>
                        <td><input type="text" name="stadtteil" placeholder="Stadtteil" required></td>
                        <td>
                            <select name="fahrzeug_name">
                                <?php foreach ($fahrzeuge as $fahrzeug): ?>
                                    <option value="<?= htmlspecialchars($fahrzeug['name']) ?>"><?= htmlspecialchars($fahrzeug['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                </table>
                <button type="submit" name="save">Speichern</button>
            </form>
        </section>

        <!-- Letzte Einsätze -->
        <section id="letzte-einsaetze">
            <h2>Letzte 15 Einsätze</h2>
            <table>
                <thead>
                    <tr>
                        <th>Einsatznummer</th>
                        <th>Stichwort</th>
                        <th>Alarmzeit</th>
                        <th>Fahrzeug</th>
                        <th>Personal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($letzteEinsaetze as $einsatz): ?>
                        <tr>
                            <td><?= htmlspecialchars($einsatz['interne_einsatznummer']) ?></td>
                            <td><?= htmlspecialchars($einsatz['stichwort']) ?></td>
                            <td><?= htmlspecialchars($einsatz['alarmuhrzeit']) ?></td>
                            <td><?= htmlspecialchars($einsatz['fahrzeug_name']) ?></td>
                            <td>
                                <details>
                                    <summary>Details anzeigen</summary>
                                    <ul>
                                        <?php if ($einsatz['stf']) echo "<li>StF: " . htmlspecialchars($einsatz['stf']) . "</li>"; ?>
                                        <?php if ($einsatz['ma']) echo "<li>Ma: " . htmlspecialchars($einsatz['ma']) . "</li>"; ?>
                                        <?php if ($einsatz['atf']) echo "<li>AtF: " . htmlspecialchars($einsatz['atf']) . "</li>"; ?>
                                        <?php if ($einsatz['atm']) echo "<li>AtM: " . htmlspecialchars($einsatz['atm']) . "</li>"; ?>
                                        <?php if ($einsatz['wtf']) echo "<li>WtF: " . htmlspecialchars($einsatz['wtf']) . "</li>"; ?>
                                        <?php if ($einsatz['wtm']) echo "<li>WtM: " . htmlspecialchars($einsatz['wtm']) . "</li>"; ?>
                                        <?php if ($einsatz['prakt']) echo "<li>Prakt: " . htmlspecialchars($einsatz['prakt']) . "</li>"; ?>
                                    </ul>
                                </details>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>

        <!-- Letzte Dienste -->
        <section id="letzte-dienste">
            <h2>Letzte 5 Dienste</h2>
            <table>
                <thead>
                    <tr>
                        <th>Fahrzeug</th>
                        <th>Zeitraum</th>
                        <th>Dauer</th>
                        <th>Alarmanzahl</th>
                        <th>Alarme</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($dienste as $dienst): ?>
                        <tr>
                            <td><?= htmlspecialchars($dienst['fahrzeug_name']) ?></td>
                            <td><?= htmlspecialchars($dienst['inDienstZeit']) ?> - <?= htmlspecialchars($dienst['ausserDienstZeit']) ?></td>
                            <td>
                                <?php
                                $stunden = floor($dienst['dauer_minuten'] / 60);
                                $minuten = $dienst['dauer_minuten'] % 60;
                                echo sprintf('%02d:%02d Stunden', $stunden, $minuten);
                                ?>
                            </td>
                            <td><?= htmlspecialchars($dienst['alarmanzahl']) ?></td>
                            <td>
                                <details>
                                    <summary>Details anzeigen</summary>
                                    <ul>
                                        <?php
                                        $alarme = fetchFromDatabase($pdo, "SELECT stichwort FROM einsaetze WHERE dienst_id = :dienst_id", [':dienst_id' => $dienst['id']]);
                                        foreach ($alarme as $alarm) {
                                            echo "<li>" . htmlspecialchars($alarm['stichwort']) . "</li>";
                                        }
                                        ?>
                                    </ul>
                                </details>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    <?php else: ?>
        <section>
            <h2>Kein aktiver Dienst vorhanden.</h2>
            <a href="neuerDienst.php" class="button">Neuen Dienst erstellen</a>
        </section>
    <?php endif; ?>
</main>
</body>
</html>
