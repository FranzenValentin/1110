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
                    $fahrzeugeStmt = $pdo->prepare("SELECT name FROM fahrzeuge ORDER BY name");
                    $fahrzeugeStmt->execute();
                    $fahrzeuge = $fahrzeugeStmt->fetchAll(PDO::FETCH_ASSOC);

                    // Stichworte laden, sortiert nach Kategorie und Stichwort
                    $stichworteStmt = $pdo->prepare("SELECT id, kategorie, stichwort FROM stichworte ORDER BY kategorie, stichwort");
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

                        // Koordinaten abrufen
                        $coordinates = fetchCoordinates($adresse, $stadtteil);


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

                            // Weitere Verarbeitung
                            echo "Gefundener Dienst: $dienst_id";
                        } catch (Exception $e) {
                            echo "<p>Fehler: " . htmlspecialchars($e->getMessage()) . "</p>";
                            error_log($e->getMessage());
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
                            ':latitude' => $coordinates["latitude"],
                            ':longitude' => $coordinates["longitude"]
                        ]);
                
                        echo "<p style='color: green;'>Einsatz wurde erfolgreich gespeichert.</p>";
                    } catch (Exception $e) {
                        echo "<p style='color: red;'>Fehler: " . htmlspecialchars($e->getMessage()) . "</p>";
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
                    // Funktion zur Abfrage der Koordinaten von OpenStreetMap
                    function fetchCoordinates($address, $district) {
                        $fullAddress = $address . ', ' . $district . ', Deutschland';
                        $url = "https://nominatim.openstreetmap.org/search?format=json&q=" . urlencode($fullAddress);
                        
                        $options = [
                            "http" => [
                                "header" => "User-Agent: EinsatzGeoCoder/1.0 (kontakt@example.com)\r\n"
                            ]
                        ];
                        $context = stream_context_create($options);

                        try {
                            $response = file_get_contents($url, false, $context);
                            $data = json_decode($response, true);
                            
                            if (!empty($data)) {
                                return [
                                    "latitude" => $data[0]["lat"],
                                    "longitude" => $data[0]["lon"]
                                ];
                            } else {
                                return ["latitude" => null, "longitude" => null];
                            }
                        } catch (Exception $e) {
                            error_log("Fehler beim Abrufen der Koordinaten: " . $e->getMessage());
                            return ["latitude" => null, "longitude" => null];
                        }
                    }

                    function syncZurueckzeit() {
                        const alarmzeitInput = document.getElementById('alarmuhrzeit');
                        const zurueckzeitInput = document.getElementById('zurueckzeit');

                        // Nur Wert kopieren, wenn Alarmzeit ausgefüllt ist
                        if (alarmzeitInput.value) {
                            zurueckzeitInput.value = alarmzeitInput.value;
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
                        "Kaulsdorf", "Kladow", "Konradshöhe", "Köpenick", "Kreuzberg", "Lankwitz", "Lichterfelde",
                        "Lichtenberg", "Lübars", "Malchow", "Marienfelde", "Mariendorf", "Marzahn", "Mitte",
                        "Moabit", "Müggelheim", "Neukölln", "Niederschöneweide", "Nikolassee",
                        "Oberschöneweide", "Pankow", "Plänterwald", "Prenzlauer Berg", "Rahnsdorf",
                        "Reinickendorf", "Rosenthal", "Rudow", "Schmargendorf", "Schmöckwitz", "Schöneberg",
                        "Siemensstadt", "Spandau", "Staaken", "Steglitz", "Tegel", "Tempelhof", "Tiergarten", "Treptow",
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


        
    </main>
</body>
</html>
