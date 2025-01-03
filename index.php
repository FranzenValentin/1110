<?php
session_start();
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header('Location: login.php'); // Weiterleitung zur Login-Seite
    exit;
}
require 'db.php';
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

        <!-- Neuer Alarm -->
        <section id="neuer-alarm">
            <h2>Neuen Einsatz eintragen</h2>
            <?php
                try {
                    // Fahrzeuge laden
                    $fahrzeugeStmt = $pdo->prepare("SELECT name FROM Fahrzeuge ORDER BY name");
                    $fahrzeugeStmt->execute();
                    $fahrzeuge = $fahrzeugeStmt->fetchAll(PDO::FETCH_ASSOC);

                    // Stichworte laden, sortiert nach Kategorie und Stichwort
                    $stichworteStmt = $pdo->prepare("SELECT id, kategorie, stichwort FROM Stichworte ORDER BY kategorie, stichwort");
                    $stichworteStmt->execute();
                    $stichworte = $stichworteStmt->fetchAll(PDO::FETCH_ASSOC);
                } catch (PDOException $e) {
                    die("Fehler beim Laden der Daten: " . $e->getMessage());
                }

                if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
                    try {
                        // Werte aus dem Formular abrufen
                        $einsatznummer_lts = $_POST['einsatznummer_lts'] ?? null;
                        $stichwort = $_POST['stichwort'] ?? null;
                        $alarmuhrzeit = $_POST['alarmuhrzeit'] ?? null;
                        $zurueckzeit = $_POST['zurueckzeit'] ?? null;
                        $adresse = $_POST['adresse'] ?? null;
                        $stadtteil =$_POST['stadtteil'] ?? null;
                        $fahrzeug_name = $_POST['fahrzeug_name'] ?? null;
                
                        // Konvertierung des Formats (ISO -> dd.mm.yy hh:mm)
                        if ($alarmuhrzeit) {
                            $alarmuhrzeit = DateTime::createFromFormat('Y-m-d\TH:i', $alarmuhrzeit)->format('d.m.y H:i');
                        }
                        if ($zurueckzeit) {
                            $zurueckzeit = DateTime::createFromFormat('Y-m-d\TH:i', $zurueckzeit)->format('d.m.y H:i');
                        }
                
                        // Format prüfen (dd.mm.yy hh:mm)
                        if (!preg_match('/^\d{2}\.\d{2}\.\d{2} \d{2}:\d{2}$/', $alarmuhrzeit) || 
                            ($zurueckzeit && !preg_match('/^\d{2}\.\d{2}\.\d{2} \d{2}:\d{2}$/', $zurueckzeit))) {
                            throw new Exception("Die Uhrzeiten müssen im Format dd.mm.yy hh:mm vorliegen.");
                        }
                
                        // Besatzung für das ausgewählte Fahrzeug abrufen
                        $besatzungStmt = $pdo->prepare("
                            SELECT b.id 
                            FROM Besatzung b 
                            JOIN Fahrzeuge f ON f.name = :fahrzeug_name 
                            WHERE b.fahrzeug_id = f.id 
                            ORDER BY b.id DESC LIMIT 1
                        ");
                        $besatzungStmt->execute([':fahrzeug_name' => $fahrzeug_name]);
                        $besatzung_id = $besatzungStmt->fetchColumn();
                
                        if (!$besatzung_id) {
                            throw new Exception("Keine gültige Besatzung für das ausgewählte Fahrzeug gefunden.");
                        }
                
                        // SQL-Statement vorbereiten und ausführen
                        $sql = "INSERT INTO Einsaetze 
                                (einsatznummer_lts, stichwort, alarmuhrzeit, zurueckzeit, adresse, stadtteil, fahrzeug_name, besatzung_id)
                                VALUES (:einsatznummer_lts, :stichwort, :alarmuhrzeit, :zurueckzeit, :adresse, :stadtteil, :fahrzeug_name, :besatzung_id)";
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([
                            ':einsatznummer_lts' => $einsatznummer_lts,
                            ':stichwort' => $stichwort,
                            ':alarmuhrzeit' => $alarmuhrzeit,
                            ':zurueckzeit' => $zurueckzeit,
                            ':adresse' => $adresse,
                            ':stadtteil' => $stadtteil,
                            ':fahrzeug_name' => $fahrzeug_name,
                            ':besatzung_id' => $besatzung_id
                        ]);
                
                        echo "<p>Einsatz wurde erfolgreich gespeichert.</p>";
                    } catch (Exception $e) {
                        echo "<p>Fehler: " . htmlspecialchars($e->getMessage()) . "</p>";
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
                        <input type="text" id="adresse" name="adresse" placeholder="Straße + Hausnummer" >
                    </div>
                </td>
                <!-- Stadtteil -->
                <td id="dick">
                    <div>
                        <input type="text" id="stadtteil" name="stadtteil" placeholder="Stadtteil eingeben" autocomplete="off">
                        <div id="autocomplete-list" class="autocomplete-items"></div>
                    </div>
                </td>

                <script>
                    const stadtteile = [
                        "Adlershof", "Alt-Hohenschönhausen", "Alt-Treptow", "Baumschulenweg", "Biesdorf",
                        "Blankenburg", "Blankenfelde", "Bohnsdorf", "Borsigwalde", "Britz", "Buch", "Buckow",
                        "Charlottenburg", "Charlottenburg-Nord", "Dahlem", "Falkenberg", "Falkenhagener Feld",
                        "Fennpfuhl", "Friedrichsfelde", "Friedrichshagen", "Friedrichshain", "Frohnau",
                        "Gatow", "Gesundbrunnen", "Gropiusstadt", "Grunewald", "Hakenfelde", "Halensee",
                        "Haselhorst", "Heiligensee", "Hermsdorf", "Johannisthal", "Karlshorst", "Karow",
                        "Kaulsdorf", "Kladow", "Konradshöhe", "Köpenick", "Lankwitz", "Lichterfelde",
                        "Lichtenberg", "Lübars", "Malchow", "Marienfelde", "Mariendorf", "Marzahn", "Mitte",
                        "Moabit", "Müggelheim", "Neukölln", "Niederschöneweide", "Nikolassee",
                        "Oberschöneweide", "Pankow", "Plänterwald", "Prenzlauer Berg", "Rahnsdorf",
                        "Reinickendorf", "Rosenthal", "Rudow", "Schmargendorf", "Schmöckwitz", "Schöneberg",
                        "Siemensstadt", "Spandau", "Staaken", "Steglitz", "Tegel", "Tempelhof", "Treptow",
                        "Waidmannslust", "Wannsee", "Wedding", "Weißensee", "Westend", "Wilhelmstadt",
                        "Wilmersdorf", "Wittenau", "Zehlendorf"
                    ];

                    const input = document.getElementById("stadtteil");
                    const autocompleteList = document.getElementById("autocomplete-list");

                    input.addEventListener("input", function() {
                        const value = this.value.trim().toLowerCase();
                        autocompleteList.innerHTML = ""; // Clear previous suggestions

                        if (!value) return;

                        // Filter Stadtteile based on input
                        const filtered = stadtteile.filter(stadtteil => stadtteil.toLowerCase().startsWith(value));

                        // Create list items for suggestions
                        filtered.forEach(stadtteil => {
                            const item = document.createElement("div");
                            item.textContent = stadtteil;
                            item.addEventListener("click", function() {
                                input.value = stadtteil; // Set input value to clicked item
                                autocompleteList.innerHTML = ""; // Clear suggestions
                            });
                            autocompleteList.appendChild(item);
                        });
                    });

                    // Close the list if clicked outside
                    document.addEventListener("click", function(event) {
                        if (!autocompleteList.contains(event.target) && event.target !== input) {
                            autocompleteList.innerHTML = ""; // Clear suggestions
                        }
                    });
                </script>

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




<!-- Aktuelle Besatzung -->
<section id="aktuelle-besatzung">
    <h2>
        Aktuelle Besatzung
        <form method="GET" class="dropdown-form" style="display: inline;">
            <select name="fahrzeug" onchange="this.form.submit()">
                <option value="1" <?php echo (!isset($_GET['fahrzeug']) || $_GET['fahrzeug'] == 1) ? 'selected' : ''; ?>>LHF 1110/1</option>
                <option value="2" <?php echo (isset($_GET['fahrzeug']) && $_GET['fahrzeug'] == 2) ? 'selected' : ''; ?>>LHF 1110/2</option>
            </select>
        </form>
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

            // Fahrzeug-ID bestimmen (Standard: LHF 1110/1)
            $fahrzeugId = isset($_GET['fahrzeug']) && $_GET['fahrzeug'] == 2 ? 2 : 1;

            // Abfrage der aktuellen Besatzung basierend auf der Fahrzeug-ID
            $besatzungStmt = $pdo->prepare("SELECT * FROM Besatzung WHERE fahrzeug_id = :fahrzeugId ORDER BY id DESC LIMIT 1");
            $besatzungStmt->execute([':fahrzeugId' => $fahrzeugId]);
            $latestBesatzung = $besatzungStmt->fetch();

            foreach ($roles as $key => $label) {
                echo "<tr>";
                echo "<td>$label</td>";

                if ($latestBesatzung && $latestBesatzung[$key . '_id']) {
                    // Zuweisung der Person abrufen
                    $personStmt = $pdo->prepare("SELECT CONCAT(vorname, ' ', nachname) AS name FROM Personal WHERE id = :id");
                    $personStmt->execute([':id' => $latestBesatzung[$key . '_id']]);
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
        <button onclick="location.href='besatzung.php'">Besatzung ändern</button>
    </div>
</section>





        <!-- Letzte Einsätze -->
        <section id="letzte-einsaetze">
            <h2>Letzte 15 Alarme</h2>
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
                        FROM Einsaetze e
                        LEFT JOIN Besatzung b ON e.besatzung_id = b.id
                        LEFT JOIN Personal p1 ON b.stf_id = p1.id
                        LEFT JOIN Personal p2 ON b.ma_id = p2.id
                        LEFT JOIN Personal p3 ON b.atf_id = p3.id
                        LEFT JOIN Personal p4 ON b.atm_id = p4.id
                        LEFT JOIN Personal p5 ON b.wtf_id = p5.id
                        LEFT JOIN Personal p6 ON b.wtm_id = p6.id
                        LEFT JOIN Personal p7 ON b.prakt_id = p7.id
                        ORDER BY 
                            CAST(SUBSTRING_INDEX(e.interne_einsatznummer, '_', 1) AS UNSIGNED) DESC,
                            CAST(SUBSTRING_INDEX(e.interne_einsatznummer, '_', -1) AS UNSIGNED) DESC
                        LIMIT 15
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

        <!-- Navigation als Buttons -->
        <section id="navigation-buttons">
            <h2>Einstellungen</h2>
            <div class="button-container">
                <button onclick="location.href='neuer_benutzer.php'">Neuer Benutzer</button>
                <button onclick="location.href='stichworte.php'">Stichworte verwalten</button>
                <button onclick="location.href='statistiken.php'">Gesamtstatistiken anzeigen</button>
                <button onclick="location.href='statistiken_personal.php'">Personal-Statistiken anzeigen</button>
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
</body>
</html>
