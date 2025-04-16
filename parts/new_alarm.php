<!-- Neuer Alarm -->
<section id="box">
    <h2>Neuer Alarm</h2>
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

                $format = "d.m.y H:i";

                $start = DateTime::createFromFormat($format, $alarmuhrzeit);
                $ende = DateTime::createFromFormat($format, $zurueckzeit);

                $einsatzdauer = null;

                $diff = $start->diff($ende);
                $einsatzdauer = sprintf("%02d:%02d", $diff->h, $diff->i);
                

                // Beispiel-Einsatzdaten
                    $einsatztext = "🚨 *Neuer Alarm - $fahrzeug_name*\n\n📟 Stichwort: $stichwort\n📍 Stadtteil: $stadtteil\n🕒 Alarmzeit: $alarmuhrzeit \n⏳ Dauer: $einsatzdauer";

                    // Telegram senden
                    $url = "https://api.telegram.org/bot$bot_token/sendMessage";
                    $data = [
                        'chat_id' => $chat_id,
                        'text' => $einsatztext,
                        'parse_mode' => 'Markdown'
                    ];

                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $url);
                    curl_setopt($ch, CURLOPT_POST, 1);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    $response = curl_exec($ch);
                    curl_close($ch);

                    echo "Telegram-Antwort: " . $response;

                echo "<p style='color: green;'>Einsatz wurde erfolgreich gespeichert.</p>";
            } catch (Exception $e) {
                echo "<p style='color: red;'>Fehler: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
        } catch (Exception $e) {
        }
    }
    ?>

    <form method="POST">
        <div class="responsive-form">

            <!-- Einsatznummer LTS -->
            <div class="form-group">
                <label for="einsatznummer_lts">Einsatznummer LTS</label>
                <input 
                    type="text" 
                    id="einsatznummer_lts" 
                    name="einsatznummer_lts" 
                    placeholder="Einsatznummer LTS" 
                    inputmode="numeric">
            </div>

            <!-- Alarmzeit -->
            <div class="form-group">
                <label for="alarmuhrzeit">Alarmzeit</label>
                <input 
                    type="datetime-local" 
                    id="alarmuhrzeit" 
                    name="alarmuhrzeit">
            </div>

            <!-- Zurückzeit -->
            <div class="form-group">
                <label for="zurueckzeit">Zurückzeit (Status 1)</label>
                <input 
                    type="datetime-local" 
                    id="zurueckzeit" 
                    name="zurueckzeit">
            </div>

            <!-- Stichwort -->
            <div class="form-group">
                <label for="stichwort">Stichwort</label>
                <select id="stichwort" name="stichwort" required>
                    <option value="">Bitte Stichwort auswählen</option>
                    <?php foreach ($stichworte as $stichwort): ?>
                        <option value="<?= htmlspecialchars($stichwort['stichwort']) ?>">
                            <?= htmlspecialchars($stichwort['stichwort']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Straße Hausnummer -->
            <div class="form-group">
                <label for="address-input">Adresse</label>
                <input 
                    type="text" 
                    id="address-input" 
                    name="adresse" 
                    placeholder="Linienstraße 128" 
                    required>
                <input type="hidden" id="latitude" name="latitude">
                <input type="hidden" id="longitude" name="longitude">
            </div>

            <!-- Stadtteil -->
            <div class="form-group">
                <label for="stadtteil">Stadtteil</label>
                <input 
                    type="text" 
                    id="stadtteil" 
                    name="stadtteil" 
                    placeholder="Bezirk" 
                    readonly>
            </div>

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
            <div class="form-group">
                <label for="fahrzeug_name">Fahrzeug</label>
                <select id="fahrzeug_name" name="fahrzeug_name" required>
                    <?php foreach ($fahrzeuge as $fahrzeug): ?>
                        <option value="<?= htmlspecialchars($fahrzeug['name']) ?>">
                            <?= htmlspecialchars($fahrzeug['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <button type="submit" name="save" class="submit-button">Speichern</button>
            </div>
        </div>
    </form>
</section>
