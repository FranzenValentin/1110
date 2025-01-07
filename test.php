<?php
try {
    loadEnv(__DIR__ . '/../config.env');
} catch (Exception $e) {
    die("Fehler: " . $e->getMessage());
}

$apiKey = $_ENV['GOOGLE_MAPS_API_KEY'] ?? null;

if (empty($apiKey)) {
    die("Google Maps API-Key fehlt oder ist ungültig.");
}

function loadEnv($filePath)
{
    if (!file_exists($filePath)) {
        throw new Exception("Die Datei $filePath wurde nicht gefunden.");
    }

    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        $parts = explode('=', $line, 2);
        if (count($parts) == 2) {
            $key = trim($parts[0]);
            $value = trim($parts[1]);
            $value = trim($value, '"\'');
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }
}


?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Google Places Autovervollständigung - Berlin</title>
    <style>
        .form-container {
            max-width: 500px;
            margin: 50px auto;
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 8px;
            background-color: #f9f9f9;
        }

        .form-container input {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        .form-container label {
            font-weight: bold;
            margin-bottom: 5px;
            display: block;
        }

        #coordinates {
            font-size: 14px;
            margin-top: 10px;
            color: #333;
        }
    </style>
    <script src="https://maps.googleapis.com/maps/api/js?key=<?= htmlspecialchars($apiKey) ?>&libraries=places"></script>
</head>
<body>
    <header style="text-align: center; padding: 20px;">
        <h1>Berliner Adress-Autovervollständigung</h1>
    </header>

    <main>
        <div class="form-container">
            <label for="address-input">Hausadresse eingeben:</label>
            <input type="text" id="address-input" placeholder="Linienstraße 128">

            <div id="coordinates">
                <p>Koordinaten: <span id="latitude">n/a</span>, <span id="longitude">n/a</span></p>
            </div>
        </div>
    </main>

    <script>
        function initAutocomplete() {
            const addressInput = document.getElementById("address-input");
            const latitudeEl = document.getElementById("latitude");
            const longitudeEl = document.getElementById("longitude");

            // Grenzen von Berlin definieren (strict bounds)
            const berlinBounds = {
                north: 52.6755, // Nördlichster Punkt von Berlin
                south: 52.3383, // Südlichster Punkt von Berlin
                east: 13.7612,  // Östlichster Punkt von Berlin
                west: 13.0884,  // Westlichster Punkt von Berlin
            };

            const options = {
                types: ['geocode'], // Nur Adressen erlauben
                componentRestrictions: { country: "DE" }, // Nur Deutschland
                fields: ['address_components', 'geometry'], // Nur relevante Felder abrufen
            };

            const autocomplete = new google.maps.places.Autocomplete(addressInput, options);

            // Setze die Begrenzungen (Bounds) auf Berlin
            const bounds = new google.maps.LatLngBounds(
                { lat: berlinBounds.south, lng: berlinBounds.west },
                { lat: berlinBounds.north, lng: berlinBounds.east }
            );

            autocomplete.setBounds(bounds);
            autocomplete.setOptions({ strictBounds: true }); // Aktiviert strictBounds

            // Listener für Änderungen bei der Adressauswahl
            autocomplete.addListener("place_changed", () => {
                const place = autocomplete.getPlace();

                if (!place.geometry || !place.geometry.location) {
                    alert("Koordinaten konnten nicht bestimmt werden.");
                    return;
                }

                // Koordinaten abrufen und anzeigen
                const latitude = place.geometry.location.lat();
                const longitude = place.geometry.location.lng();
                latitudeEl.textContent = latitude.toFixed(6);
                longitudeEl.textContent = longitude.toFixed(6);

                // Adressfeld bereinigen (nur Straße und Hausnummer)
                const street = place.address_components.find(component =>
                    component.types.includes("route")
                );
                const streetNumber = place.address_components.find(component =>
                    component.types.includes("street_number")
                );

                let formattedAddress = street ? street.long_name : "";
                if (streetNumber) {
                    formattedAddress +=  ${streetNumber.long_name};
                }

                addressInput.value = formattedAddress.trim();
            });
        }

        // Initialisierung von Google Autocomplete
        document.addEventListener("DOMContentLoaded", initAutocomplete);
    </script>
</body>
</html>