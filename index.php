<?php
session_start();
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    //header('Location: login.php'); // Weiterleitung zur Login-Seite
    exit;
}

require 'db.php';


// Debugging: Aktuelle Uhrzeit anzeigen
// Zeitzone setzen (z. B. für Deutschland)
date_default_timezone_set('Europe/Berlin');
$aktuelleUhrzeit = date('d.m.Y H:i');

// SQL-Abfrage
$dienstQuery = "
    SELECT inDienstZeit, ausserDienstZeit 
    FROM dienste 
    WHERE STR_TO_DATE(inDienstZeit, '%d.%m.%Y %H:%i') <= STR_TO_DATE(:aktuelleUhrzeit, '%d.%m.%Y %H:%i')
    AND (STR_TO_DATE(ausserDienstZeit, '%d.%m.%Y %H:%i') > STR_TO_DATE(:aktuelleUhrzeit, '%d.%m.%Y %H:%i') 
         OR ausserDienstZeit IS NULL)
    LIMIT 1
";

$dienstStmt = $pdo->prepare($dienstQuery);
$dienstStmt->execute([
    ':fahrzeug_id' => $fahrzeugId,
    ':aktuelleUhrzeit' => $aktuelleUhrzeit,
]);

$dienstStmt = $pdo->prepare($dienstQuery);
$dienstStmt->execute([
    ':fahrzeug_id' => $fahrzeugId,
    ':aktuelleUhrzeit' => $aktuelleUhrzeit,
]);

// Dienstdaten abrufen, falls ein aktiver Dienst existiert
$dienstResult = $dienstStmt->fetch(PDO::FETCH_ASSOC);

// Setze $dienstVorhanden auf 1, wenn ein aktiver Dienst existiert
$dienstVorhanden = $dienstResult ? 1 : 0;

?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Einsatzverwaltung</title>
    <link rel="stylesheet" href="styles.css">
    <script src="https://maps.googleapis.com/maps/api/js?key=<?= htmlspecialchars($apiKey) ?>&libraries=places"></script>
</head>
<body>
<header>
        <h1>Einsatzverwaltungssystem</h1>
        <form method="POST" action="logout.php" class="logout-form">
            <button type="submit">Logout</button>
        </form>
</header>


    <main>
    <?php if ($dienstVorhanden): ?>

        <!-- Neuer Alarm -->
        <section id="neuer-alarm">
            <h2>Neuen Einsatz eintragen</h2>
            <?php
                try {
                    // Fahrzeuge laden
                    $fahrzeugeStmt = $pdo->prepare("SELECT name FROM fahrzeuge ORDER BY name");
                    $fahrzeugeStmt->execute();
                    $fahrzeuge = $fahrzeugeStmt->fetchAll(PDO::FETCH_ASSOC);

                    // Stichworte laden, sortiert nach Kategorie und Stichwort
                    $stichworteStmt = $pdo->prepare("SELECT id, kategorie, stichwort, priority FROM stichworte ORDER BY priority ASC, stichwort ASC");
                    $stichworteStmt->execute();
                    $stichworte = $stichworteStmt->fetchAll(PDO::FETCH_ASSOC);
                } catch (PDOException $e) {
                    die("Fehler beim Laden der Daten: " . $e->getMessage());
                }

                if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
                    try {
                        // Formularwerte abrufen
                        $einsatznummer_lts = $_POST['einsatznummer_lts'] ?? null;
                        $stichwort = $_POST['stichwort'] ?? null;
                        $alarmuhrzeit = $_POST['alarmuhrzeit'] ?? null;
                        $zurueckzeit = $_POST['zurueckzeit'] ?? null;
                        $adresse = $_POST['adresse'] ?? null;
                        $stadtteil = $_POST['stadtteil'] ?? null;
                        $fahrzeug_name = $_POST['fahrzeug_name'] ?? null;
                        $latitude = $_POST['latitude'] ?? null;
                        $longitude = $_POST['longitude'] ?? null;

                
                        // Alarmuhrzeit ins richtige Format konvertieren
                        if ($alarmuhrzeit) {
                            $alarmuhrzeit = DateTime::createFromFormat('Y-m-d\TH:i', $alarmuhrzeit)->format('d.m.Y H:i');
                        } else {
                            throw new Exception("Alarmuhrzeit ist erforderlich.");
                        }
                
                        // Zurückzeit optional konvertieren
                        if ($zurueckzeit) {
                            $zurueckzeit = DateTime::createFromFormat('Y-m-d\TH:i', $zurueckzeit)->format('d.m.Y H:i');
                        }

                        try {
                            // Alarmuhrzeit validieren
                            if (!preg_match('/^\d{2}\.\d{2}\.\d{4} \d{2}:\d{2}$/', $alarmuhrzeit)) {
                                throw new Exception("Alarmuhrzeit muss im Format dd.mm.yyyy hh:mm vorliegen.");
                            }
                            
                            // Dienst suchen
                            $dienstQuery = "
                                SELECT id 
                                FROM dienste 
                                WHERE fahrzeug_id = (
                                    SELECT id FROM fahrzeuge WHERE name = :fahrzeug_name
                                )
                                AND STR_TO_DATE(inDienstZeit, '%d.%m.%Y %H:%i') <= STR_TO_DATE(:alarmuhrzeit, '%d.%m.%Y %H:%i')
                                AND (STR_TO_DATE(ausserDienstZeit, '%d.%m.%Y %H:%i') > STR_TO_DATE(:alarmuhrzeit, '%d.%m.%Y %H:%i') 
                                    OR ausserDienstZeit IS NULL)
                                LIMIT 1
                            ";
                            $dienstStmt = $pdo->prepare($dienstQuery);
                            $dienstStmt->execute([
                                ':fahrzeug_name' => $fahrzeug_name,
                                ':alarmuhrzeit' => $alarmuhrzeit
                            ]);
                            $dienst_id = $dienstStmt->fetchColumn();

                            if (!$dienst_id) {
                                throw new Exception("Kein gültiger Dienst für die Alarmuhrzeit gefunden.");
                            }

                        } catch (Exception $e) {
                            echo "<p>Fehler: " . htmlspecialchars($e->getMessage()) . "</p>";
                            error_log($e->getMessage());
                        }

                
                        try {
                            // Überprüfen, ob der Einsatz bereits existiert
                            $checkQuery = "
                                SELECT COUNT(*) 
                                FROM einsaetze 
                                WHERE einsatznummer_lts = :einsatznummer_lts 
                                AND alarmuhrzeit = :alarmuhrzeit
                                AND fahrzeug_name = :fahrzeug_name
                            ";
                            $checkStmt = $pdo->prepare($checkQuery);
                            $checkStmt->execute([
                                ':einsatznummer_lts' => $einsatznummer_lts,
                                ':alarmuhrzeit' => $alarmuhrzeit,
                                ':fahrzeug_name' => $fahrzeug_name,
                            ]);
                            $exists = $checkStmt->fetchColumn();
                        
                            if ($exists) {
                                throw new Exception("Ein Einsatz mit dieser Einsatznummer, Alarmuhrzeit und Fahrzeug existiert bereits.");
                            }
                        
                            // Einsatz in die Datenbank einfügen
                            $einsatzQuery = "
                                INSERT INTO einsaetze 
                                (einsatznummer_lts, stichwort, alarmuhrzeit, zurueckzeit, adresse, stadtteil, fahrzeug_name, dienst_id, latitude, longitude) 
                                VALUES 
                                (:einsatznummer_lts, :stichwort, :alarmuhrzeit, :zurueckzeit, :adresse, :stadtteil, :fahrzeug_name, :dienst_id, :latitude, :longitude)
                            ";
                            $einsatzStmt = $pdo->prepare($einsatzQuery);
                            $einsatzStmt->execute([
                                ':einsatznummer_lts' => $einsatznummer_lts,
                                ':stichwort' => $stichwort,
                                ':alarmuhrzeit' => $alarmuhrzeit,
                                ':zurueckzeit' => $zurueckzeit,
                                ':adresse' => $adresse,
                                ':stadtteil' => $stadtteil,
                                ':fahrzeug_name' => $fahrzeug_name,
                                ':dienst_id' => $dienst_id,
                                ':latitude' => $latitude,
                                ':longitude' => $longitude
                            ]);
                        
                            echo "<p style='color: green;'>Einsatz wurde erfolgreich gespeichert.</p>";
                        } catch (Exception $e) {
                            echo "<p style='color: red;'>Fehler: " . htmlspecialchars($e->getMessage()) . "</p>";
                        }
                    } catch (Exception $e) 
                    {
                    } 
                }        
                
            ?>

            <form method="POST">
                <table>
                                <tbody>
                        <tr>
                            <!-- Einsatznummer LTS -->
                            <td id="dünn">
                                <div>
                                    <input type="text" inputmode="numeric"  id="einsatznummer_lts" name="einsatznummer_lts" placeholder="Einsatznummer LTS" >
                                </div>
                            </td>

                            <!-- Alarmzeit -->
                            <td id="dünn">
                                <div style="position: relative;">
                                    <input 
                                        type="datetime-local" 
                                        id="alarmuhrzeit" 
                                        name="alarmuhrzeit" 
                                        oninput="syncZurueckzeit()" 
                                        onfocus="hidePlaceholder('alarmPlaceholder')" 
                                        onblur="showPlaceholder('alarmPlaceholder', this)" 
                                        style="padding-left: 5px;">
                                    <span id="alarmPlaceholder" style="position: absolute; left: 15px; top: 13px; color: #aaa;">Alarmzeit</span>
                                </div>
                            </td>

                            <!-- Zurückzeit -->
                            <td id="dünn">
                                <div style="position: relative;">
                                    <input 
                                        type="datetime-local" 
                                        id="zurueckzeit" 
                                        name="zurueckzeit" 
                                        onfocus="hidePlaceholder('returnPlaceholder')" 
                                        onblur="showPlaceholder('returnPlaceholder', this)" 
                                        style="padding-left: 5px;">
                                    <span id="returnPlaceholder" style="position: absolute; left: 15px; top: 13px; color: #aaa;">Zurückzeit</span>
                                </div>
                            </td>


                            <script>

                                function hidePlaceholder(placeholderId) {
                                    document.getElementById(placeholderId).style.display = 'none';
                                }

                                function showPlaceholder(placeholderId, input) {
                                    if (!input.value) {
                                        document.getElementById(placeholderId).style.display = 'block';
                                    }
                                }
                                function hidePlaceholder(placeholderId) {
                                    document.getElementById(placeholderId).style.display = 'none';
                                }

                                function showPlaceholder(placeholderId, input) {
                                    if (!input.value) {
                                        document.getElementById(placeholderId).style.display = 'block';
                                    }
                                }
                            </script>

                <!-- Stichwort -->
                <td id="dick">
                    <div>
                        <select id="stichwort" name="stichwort" required>
                            <option value="">Bitte Stichwort auswählen</option>
                            <?php foreach ($stichworte as $stichwort): ?>
                                <option value="<?= htmlspecialchars($stichwort['stichwort']) ?>">
                                    <?= htmlspecialchars($stichwort['stichwort']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </td>

                <!-- Straße Hausnummer -->
                <td id="dick">
                <div>
                    <input type="text" id="address-input" name="adresse" placeholder="Linienstraße 128" style="width: 100%;"required>
                    <input type="hidden" id="latitude" name="latitude">
                    <input type="hidden" id="longitude" name="longitude">
                </div>
                </td>
                
                <!-- Stadtteil -->
                <td id="dick">
                    <div>
                        <input type="text" id="stadtteil" name="stadtteil" placeholder="Bezirk" autocomplete="off" readonly required>
                    </div>
                </td>

                <style>
                    #autocomplete-list {
                        position: absolute;
                        border: 1px solid #ccc;
                        border-radius: 4px;
                        background-color: white;
                        max-height: 200px;
                        overflow-y: auto;
                        z-index: 1000;
                    }

                    #autocomplete-list div {
                        padding: 10px;
                        cursor: pointer;
                    }

                    #autocomplete-list div:hover {
                        background-color: #e9e9e9;
                    }
                </style>



                <!-- Fahrzeug -->
                <td id="dünn">
                     <div>
                        <select id="fahrzeug_name" name="fahrzeug_name" >
                            <?php foreach ($fahrzeuge as $fahrzeug): ?>
                                <option value="<?= htmlspecialchars($fahrzeug['name']) ?>">
                                    <?= htmlspecialchars($fahrzeug['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                   </td>
                   </tr>
                 </tbody>
                </table>
                <button class="button-container" type="submit" name="save">Speichern</button>
            </form>

        </section>

<?php 
// Fahrzeuge aus der Datenbank abrufen
$query = "SELECT id, name FROM fahrzeuge";
$statement = $pdo->prepare($query);
$statement->execute();
$fahrzeuge = $statement->fetchAll(PDO::FETCH_ASSOC);

// Fahrzeug-ID setzen (Standard: LHF 1)
if (!isset($_GET['fahrzeug'])) {
    $defaultVehicleQuery = "SELECT id FROM fahrzeuge WHERE name = 'LHF 1' LIMIT 1";
    $defaultVehicleStmt = $pdo->prepare($defaultVehicleQuery);
    $defaultVehicleStmt->execute();
    $fahrzeugId = $defaultVehicleStmt->fetchColumn();
} else {
    $fahrzeugId = (int)$_GET['fahrzeug'];
}

// Standardwerte setzen
$inDienstZeit = 'Keine Daten';
$ausserDienstZeit = 'Keine Daten';

// SQL-Abfrage für die zeitlich neuesten Dienstzeiten
$zeitQuery = "
    SELECT inDienstZeit, ausserDienstZeit 
    FROM dienste 
    WHERE fahrzeug_id = :fahrzeug_id 
    ORDER BY STR_TO_DATE(inDienstZeit, '%d.%m.%Y %H:%i') DESC 
    LIMIT 1
";

if (empty($fahrzeugId)) {
    $fahrzeugId = 1;
}

$zeitStmt = $pdo->prepare($zeitQuery);
$zeitStmt->execute([':fahrzeug_id' => $fahrzeugId]);
$zeitResult = $zeitStmt->fetch(PDO::FETCH_ASSOC);

// Zeiten auslesen, falls vorhanden
if ($zeitResult) {
    $inDienstZeit = $zeitResult['inDienstZeit'] ?? 'Keine Daten';
    $ausserDienstZeit = $zeitResult['ausserDienstZeit'] ?? 'Keine Daten';
}
?>


        <!-- Aktueller Dienst -->
        <section id="aktueller-dienste">
            <h2>
                Aktueller Dienst mit dem 
                <form method="GET" class="dropdown-form" style="display: inline;">
                    <select name="fahrzeug" onchange="this.form.submit()">
                        <?php foreach ($fahrzeuge as $fahrzeug): ?>
                            <option value="<?php echo htmlspecialchars($fahrzeug['id']); ?>"
                                <?php echo ($fahrzeugId == $fahrzeug['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($fahrzeug['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
                <?php echo "vom $inDienstZeit bis zum $ausserDienstZeit"; ?>
            </h2>


            <table>
                <thead>
                    <tr>
                        <th>Funktion</th>
                        <th>Aktuell zugewiesen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Besatzungsrollen definieren
                    $roles = [
                        'stf' => 'Staffel-Führer',
                        'ma' => 'Maschinist',
                        'atf' => 'Angriffstrupp-Führer',
                        'atm' => 'Angriffstrupp-Mann',
                        'wtf' => 'Wassertrupp-Führer',
                        'wtm' => 'Wassertrupp-Mann',
                        'prakt' => 'Praktikant'
                    ];

                    // Besatzung basierend auf den ermittelten Zeiten und Fahrzeug abrufen
                    $besatzungStmt = $pdo->prepare("
                        SELECT * 
                        FROM dienste 
                        WHERE fahrzeug_id = :fahrzeug_id 
                        AND STR_TO_DATE(inDienstZeit, '%d.%m.%Y %H:%i') = STR_TO_DATE(:inDienstZeit, '%d.%m.%Y %H:%i')
                        AND (STR_TO_DATE(ausserDienstZeit, '%d.%m.%Y %H:%i') = STR_TO_DATE(:ausserDienstZeit, '%d.%m.%Y %H:%i') OR :ausserDienstZeit IS NULL)
                    ");
                    $besatzungStmt->execute([
                        ':fahrzeug_id' => $fahrzeugId,
                        ':inDienstZeit' => $inDienstZeit,
                        ':ausserDienstZeit' => $ausserDienstZeit !== 'Keine Daten' ? $ausserDienstZeit : null,
                    ]);
                    $besatzung = $besatzungStmt->fetch();

                    foreach ($roles as $key => $label) {
                        echo "<tr>";
                        echo "<td>$label</td>";

                        if ($besatzung && $besatzung[$key . '_id']) {
                            // Zuweisung der Person abrufen
                            $personStmt = $pdo->prepare("SELECT CONCAT(vorname, ' ', nachname) AS name FROM personal WHERE id = :id");
                            $personStmt->execute([':id' => $besatzung[$key . '_id']]);
                            $person = $personStmt->fetch();

                            // Name anzeigen, falls vorhanden
                            echo "<td>" . ($person['name'] ?? '<em>NICHT BESETZT</em>') . "</td>";
                        } else {
                            // Kein Name zugewiesen
                            echo "<td><em>NICHT BESETZT</em></td>";
                        }

                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>
            <div class="button-container">
            <button onclick="location.href='editDienst.php?fahrzeug=<?php echo $fahrzeugId; ?>&dienst=<?php echo $dienstId; ?>'">Dienst bearbeiten</button>
            <button onclick="location.href='neuerDienst.php'">Weiterer Dienst</button>
            </div>

            <?php else: ?>
                <!-- Keine Dienste vorhanden -->
                <section>
                    <h2 style="text-align: center;">Zum Anlegen eines Einsatzes muss zuerst ein Dienst eingetragen werden.</h2>
                    <div class="button-container" style="text-align: center;">
                    <button onclick="location.href='neuerDienst.php'" style="padding: 16px 20px;font-size: 1.1em;">Neuer Dienst</button>
                    </div>
                </section>
            <?php endif; ?>

        </section>


        <!-- Letzte Einsätze -->
        <section id="letzte-einsaetze">
            <h2>Letzte 10 Alarme</h2>
            <table>
                <thead>
                    <tr>
                        <th>Interne Einsatznummer</th>
                        <th>Stichwort</th>
                        <th>Alarmzeit</th>
                        <th>Fahrzeug</th>
                        <th>Personal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // SQL-Abfrage: Abrufen der letzten 15 Einsätze mit Besatzung und Personal
                    $stmt = $pdo->query("
                        SELECT e.interne_einsatznummer, e.alarmuhrzeit, e.fahrzeug_name, e.stichwort,
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
                        ORDER BY 
                            CAST(SUBSTRING_INDEX(e.interne_einsatznummer, '_', 1) AS UNSIGNED) DESC,
                            CAST(SUBSTRING_INDEX(e.interne_einsatznummer, '_', -1) AS UNSIGNED) DESC
                        LIMIT 10
                    ");


                    // Ergebnisse anzeigen
                    while ($row = $stmt->fetch()) {
                        // Personal zusammenstellen
                        $personal = [];
                        if ($row['stf']) $personal[] = "StF: " . htmlspecialchars($row['stf']);
                        if ($row['ma']) $personal[] = "Ma: " . htmlspecialchars($row['ma']);
                        if ($row['atf']) $personal[] = "AtF: " . htmlspecialchars($row['atf']);
                        if ($row['atm']) $personal[] = "AtM: " . htmlspecialchars($row['atm']);
                        if ($row['wtf']) $personal[] = "WtF: " . htmlspecialchars($row['wtf']);
                        if ($row['wtm']) $personal[] = "WtM: " . htmlspecialchars($row['wtm']);
                        if ($row['prakt']) $personal[] = "Prakt: " . htmlspecialchars($row['prakt']);

                        echo "<tr>
                                <td>" . htmlspecialchars($row['interne_einsatznummer']) . "</td>
                                <td>" . htmlspecialchars($row['stichwort']) . "</td>
                                <td>" . htmlspecialchars($row['alarmuhrzeit']) . "</td>
                                <td>" . htmlspecialchars($row['fahrzeug_name']) . "</td>
                                <td>
                                    <details>
                                        <summary>Details anzeigen</summary>
                                        " . implode('<br>', $personal) . "
                                    </details>
                                </td>
                              </tr>";
                    }
                    ?>
                </tbody>
            </table>
            <div class="button-container">
                <button onclick="location.href='historie.php'">Alle Alarme</button>
            </div>
        </section>


        <!-- letzten 5 Dienste -->
        <section id="letzte-dienste">
                <h2>Letzte 5 Dienste</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Fahrzeug</th>
                            <th>Zeitraum</th>
                            <th>Dienst Dauer</th>
                            <th>Alarmanzahl</th>
                            <th>Alarme (Stichworte)</th>
                            <th>Personal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Debug: Fahrzeug-ID und Abfrage-Start
                        echo "<!-- Debug: Starte Abfrage für letzte 5 Dienste -->";

                        // SQL-Abfrage, um die letzten 5 Dienste aller Fahrzeuge nach Datum zu erhalten
                        $dienstStmt = $pdo->prepare("
                            SELECT d.id, f.name AS fahrzeug_name, d.inDienstZeit, d.ausserDienstZeit,
                                TIMESTAMPDIFF(MINUTE, STR_TO_DATE(d.inDienstZeit, '%d.%m.%Y %H:%i'), 
                                STR_TO_DATE(d.ausserDienstZeit, '%d.%m.%Y %H:%i')) AS dauer_minuten,
                                COUNT(e.id) AS alarmanzahl
                            FROM dienste d
                            JOIN fahrzeuge f ON f.id = d.fahrzeug_id
                            LEFT JOIN einsaetze e ON e.dienst_id = d.id
                            GROUP BY d.id
                            ORDER BY STR_TO_DATE(d.inDienstZeit, '%d.%m.%Y %H:%i') DESC
                            LIMIT 5
                        ");

                        try {
                            $dienstStmt->execute();
                            $dienste = $dienstStmt->fetchAll(PDO::FETCH_ASSOC);

                            // Debug: Ergebnis der Abfrage anzeigen
                            echo "<!-- Debug: Dienste = " . print_r($dienste, true) . " -->";
                        } catch (PDOException $e) {
                            echo "<p style='color: red;'>Fehler bei der Abfrage der letzten Dienste: " . htmlspecialchars($e->getMessage()) . "</p>";
                        }

                        foreach ($dienste as $dienst) {
                            // Personal abrufen
                            $personalStmt = $pdo->prepare("
                                SELECT 
                                    CASE 
                                        WHEN p.id = d.stf_id THEN 'StF'
                                        WHEN p.id = d.ma_id THEN 'Ma'
                                        WHEN p.id = d.atf_id THEN 'AtF'
                                        WHEN p.id = d.atm_id THEN 'AtM'
                                        WHEN p.id = d.wtf_id THEN 'WtF'
                                        WHEN p.id = d.wtm_id THEN 'WtM'
                                        WHEN p.id = d.prakt_id THEN 'Prakt'
                                    END AS funktion,
                                    CONCAT(p.vorname, ' ', p.nachname) AS name,
                                    CASE
                                        WHEN p.id = d.stf_id THEN 1
                                        WHEN p.id = d.ma_id THEN 2
                                        WHEN p.id = d.atf_id THEN 3
                                        WHEN p.id = d.atm_id THEN 4
                                        WHEN p.id = d.wtf_id THEN 5
                                        WHEN p.id = d.wtm_id THEN 6
                                        WHEN p.id = d.prakt_id THEN 7
                                    END AS reihenfolge
                                FROM personal p
                                JOIN dienste d ON p.id IN (
                                    d.stf_id, d.ma_id, d.atf_id, d.atm_id, d.wtf_id, d.wtm_id, d.prakt_id
                                )
                                WHERE d.id = :dienst_id
                                ORDER BY reihenfolge ASC
                            ");

                            try {
                                $personalStmt->execute([':dienst_id' => $dienst['id']]);
                                $personalList = $personalStmt->fetchAll(PDO::FETCH_ASSOC);

                                // Debug: Personal-Ergebnisse anzeigen
                                echo "<!-- Debug: Personal für Dienst-ID {$dienst['id']} = " . print_r($personalList, true) . " -->";
                            } catch (PDOException $e) {
                                echo "<p style='color: red;'>Fehler beim Abrufen des Personals: " . htmlspecialchars($e->getMessage()) . "</p>";
                            }

                            // Alarme (Stichworte) abrufen
                            $alarmeStmt = $pdo->prepare("
                                SELECT stichwort
                                FROM einsaetze
                                WHERE dienst_id = :dienst_id
                            ");
                            try {
                                $alarmeStmt->execute([':dienst_id' => $dienst['id']]);
                                $alarmeList = $alarmeStmt->fetchAll(PDO::FETCH_COLUMN);

                                // Debug: Alarm-Ergebnisse anzeigen
                                echo "<!-- Debug: Alarme für Dienst-ID {$dienst['id']} = " . print_r($alarmeList, true) . " -->";
                            } catch (PDOException $e) {
                                echo "<p style='color: red;'>Fehler beim Abrufen der Alarme: " . htmlspecialchars($e->getMessage()) . "</p>";
                            }

                            // Dienst Dauer im Format 00:00 Stunden berechnen
                            $dauer_stunden = floor($dienst['dauer_minuten'] / 60);
                            $dauer_minuten = $dienst['dauer_minuten'] % 60;
                            $dauer_formatiert = sprintf('%02d:%02d Stunden', $dauer_stunden, $dauer_minuten);

                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($dienst['fahrzeug_name']) . "</td>";
                            echo "<td>" . htmlspecialchars($dienst['inDienstZeit']) . " - " . htmlspecialchars($dienst['ausserDienstZeit']) . "</td>";
                            echo "<td>" . htmlspecialchars($dauer_formatiert) . "</td>";
                            echo "<td>" . htmlspecialchars($dienst['alarmanzahl']) . "</td>";


                            
                            // Alarme (Stichworte) ausklappbar
                            echo "<td>
                                    <details>
                                        <summary>Details anzeigen</summary>
                                        <ul>";
                            foreach ($alarmeList as $alarm) {
                                echo "<li>" . htmlspecialchars($alarm) . "</li>";
                            }
                            echo "    </ul>
                                    </details>
                                </td>";

                            // Personal ausklappbar mit Funktion
                            echo "<td>
                                    <details>
                                        <summary>Details anzeigen</summary>
                                        <ul>";
                            foreach ($personalList as $person) {
                                echo "<li>" . htmlspecialchars($person['funktion'] . ': ' . $person['name']) . "</li>";
                            }
                            echo "    </ul>
                                    </details>
                                </td>";

                            echo "</tr>";
                        }
                        ?>
                    </tbody>
                </table>
                    <div class="button-container">
                        <button onclick="location.href='alleDienste.php'">Alle Dienste</button>
                    </div>
            
        </section>

        <!-- Navigation als Buttons -->
        <section id="navigation-buttons">
                    <h2>Statistiken</h2>
                    <div class="button-container">
                        <button onclick="location.href='statistiken.php'">Gesamtstatistiken</button>
                        <button onclick="location.href='statistiken_personal.php'">Personal-Statistiken</button>
                        <button onclick="location.href='einsatzentwicklung.php'">Einsatzentwicklung</button>
                    </div>
        </section>




        <!-- Navigation als Buttons -->
        <section id="navigation-buttons">
            <h2>Einstellungen</h2>
            <div class="button-container">
                <button onclick="location.href='neuer_benutzer.php'">Neuer Benutzer</button>
                <button onclick="location.href='stichworte.php'">Stichworte verwalten</button>
            </div>
        </section>

        <!--Export -->
        <section id="navigation-buttons">
            <h2>Export der Einsätze</h2>
            <div class="button-container">
                <form action="export_einsaetze.php" method="post">
                    <label for="monat">Monat:</label>
                        <select id="monat" name="monat" required>
                            <option value="01">Januar</option>
                            <option value="02">Februar</option>
                            <option value="03">März</option>
                            <option value="04">April</option>
                            <option value="05">Mai</option>
                            <option value="06">Juni</option>
                            <option value="07">Juli</option>
                            <option value="08">August</option>
                            <option value="09">September</option>
                            <option value="10">Oktober</option>
                            <option value="11">November</option>
                            <option value="12">Dezember</option>
                        </select>

                    <label for="jahr">Jahr:</label>
                        <input type="number" id="jahr" name="jahr" value="<?= date('Y') ?>" required>

            <button type="submit">Einsätze exportieren</button>

            </div>
        </section>
    </main>
    <script>
    function initAutocomplete() {
        const addressInput = document.getElementById("address-input");
        const latitudeEl = document.getElementById("latitude");
        const longitudeEl = document.getElementById("longitude");
        const districtEl = document.getElementById("stadtteil"); // Das Stadtteil-Feld

        const berlinBounds = {
            north: 52.6755,
            south: 52.3383,
            east: 13.7612,
            west: 13.0884,
        };

        const options = {
            types: ["geocode", "establishment"], // Parks und andere Orte einbeziehen
            componentRestrictions: { country: "DE" },
            fields: ["address_components", "geometry", "name"], // Name für Orte wie Parks hinzufügen
        };

        const autocomplete = new google.maps.places.Autocomplete(addressInput, options);
        autocomplete.setBounds(new google.maps.LatLngBounds(
            { lat: berlinBounds.south, lng: berlinBounds.west },
            { lat: berlinBounds.north, lng: berlinBounds.east }
        ));
        autocomplete.setOptions({ strictBounds: true });

        autocomplete.addListener("place_changed", () => {
            const place = autocomplete.getPlace();

            if (!place.geometry || !place.geometry.location) {
                alert("Koordinaten konnten nicht bestimmt werden.");
                return;
            }

            const latitude = place.geometry.location.lat();
            const longitude = place.geometry.location.lng();
            latitudeEl.value = latitude.toFixed(10); // Genauigkeit von 10 Dezimalstellen
            longitudeEl.value = longitude.toFixed(10);

            // Adresse oder Parkname ermitteln
            const placeName = place.name || ""; // Name des Ortes, z. B. Parkname
            let formattedAddress = placeName;

            // Kreuzungen erkennen
            const intersection = place.address_components.find(comp => comp.types.includes("intersection"));
            if (intersection) {
                formattedAddress = intersection.long_name;
            } else {
                // Standard: Straße und Hausnummer kombinieren
                const street = place.address_components.find(comp => comp.types.includes("route"));
                const streetNumber = place.address_components.find(comp => comp.types.includes("street_number"));

                formattedAddress = street ? street.long_name : "";
                if (streetNumber) {
                    formattedAddress += " " + streetNumber.long_name;
                }
                addressInput.value = formattedAddress.trim();
            }

            

            // Setze die berechnete Adresse in das Eingabefeld
            addressInput.value = formattedAddress.trim();


            // Stadtteil ermitteln
            const district = place.address_components.find(comp =>
                comp.types.includes("sublocality") || 
                comp.types.includes("locality") || 
                comp.types.includes("administrative_area_level_2")
            );

            if (district) {
                // Entferne das Präfix "Bezirk" und setze nur den Stadtteil
                districtEl.value = district.long_name.replace(/^Bezirk\s+/i, "").trim();
            } else {
                districtEl.value = "Stadtteil nicht gefunden"; // Fallback
            }


            // Debug: Stadtteil prüfen
            console.log("Gefundener Stadtteil: ", district ? district.long_name : "Kein Stadtteil");
            console.log("Ort gefunden: ", placeName);
        });
    }

    // Initialisierung der Autocomplete-Funktion, nachdem die Seite geladen ist
    document.addEventListener("DOMContentLoaded", initAutocomplete);

</script>
</body>
</html>