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

// OpenStreetMap API-Aufruf
if (isset($_GET['lat']) && isset($_GET['lon'])) {
    $lat = htmlspecialchars($_GET['lat']);
    $lon = htmlspecialchars($_GET['lon']);
    $url = "https://nominatim.openstreetmap.org/reverse?format=json&lat=$lat&lon=$lon";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'YourAppName/1.0 (your@email.com)');
    $response = curl_exec($ch);
    curl_close($ch);

    header('Content-Type: application/json');
    echo $response;
    exit;
}
?>


<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Google Places & OpenStreetMap - Stadtteilanzeige</title>
    <style>
        .form-container {
            max-width: 600px;
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

        .details {
            margin-top: 20px;
            font-size: 14px;
            color: #333;
        }

        .details p {
            margin: 5px 0;
        }
    </style>
    <!-- Google Maps API -->
    <script src="https://maps.googleapis.com/maps/api/js?key=<?= htmlspecialchars($apiKey) ?>&libraries=places"></script>
</head>
<body>
    <header style="text-align: center; padding: 20px;">
        <h1>Google Places & OpenStreetMap - Stadtteilanzeige</h1>
    </header>

    <main>
        <div class="form-container">
            <label for="address-input">Hausadresse eingeben:</label>
            <input type="text" id="address-input" placeholder="Linienstraße 128">
            
            <div class="details">
                <h3>Details zur Adresse:</h3>
                <p><strong>Formatted Address:</strong> <span id="formatted-address">n/a</span></p>
                <p><strong>Koordinaten:</strong> Breite: <span id="latitude">n/a</span>, Länge: <span id="longitude">n/a</span></p>
                <p><strong>Postleitzahl:</strong> <span id="postal-code">n/a</span></p>
                <p><strong>Stadt:</strong> <span id="city">n/a</span></p>
                <p><strong>Bundesland:</strong> <span id="state">n/a</span></p>
                <p><strong>Land:</strong> <span id="country">n/a</span></p>
                <h4>OpenStreetMap Stadtteil:</h4>
                <p><strong>Stadtteil:</strong> <span id="osm-district">n/a</span></p>
            </div>
        </div>
    </main>

    <script>
        function initAutocomplete() {
            const addressInput = document.getElementById("address-input");
            const formattedAddressEl = document.getElementById("formatted-address");
            const latitudeEl = document.getElementById("latitude");
            const longitudeEl = document.getElementById("longitude");
            const postalCodeEl = document.getElementById("postal-code");
            const cityEl = document.getElementById("city");
            const stateEl = document.getElementById("state");
            const countryEl = document.getElementById("country");
            const osmDistrictEl = document.getElementById("osm-district");

            // Google Maps Autocomplete Initialisierung
            const autocomplete = new google.maps.places.Autocomplete(addressInput, {
                types: ['geocode'],
                componentRestrictions: { country: "DE" },
            });

            // Event Listener: Google Maps liefert neue Daten
            autocomplete.addListener("place_changed", () => {
                const place = autocomplete.getPlace();

                if (!place.geometry || !place.geometry.location) {
                    alert("Koordinaten konnten nicht bestimmt werden.");
                    return;
                }

                // Google Maps: Koordinaten
                const latitude = place.geometry.location.lat();
                const longitude = place.geometry.location.lng();
                latitudeEl.textContent = latitude.toFixed(6);
                longitudeEl.textContent = longitude.toFixed(6);

                // Google Maps: Adresskomponenten
                const addressComponents = place.address_components;
                const postalCode = addressComponents.find(component =>
                    component.types.includes("postal_code")
                );
                const city = addressComponents.find(component =>
                    component.types.includes("locality")
                );
                const state = addressComponents.find(component =>
                    component.types.includes("administrative_area_level_1")
                );
                const country = addressComponents.find(component =>
                    component.types.includes("country")
                );

                // Daten im Frontend anzeigen
                formattedAddressEl.textContent = place.formatted_address || "n/a";
                postalCodeEl.textContent = postalCode ? postalCode.long_name : "n/a";
                cityEl.textContent = city ? city.long_name : "n/a";
                stateEl.textContent = state ? state.long_name : "n/a";
                countryEl.textContent = country ? country.long_name : "n/a";

                // OpenStreetMap API: Stadtteil abfragen
                fetch(`?lat=${latitude}&lon=${longitude}`)
                    .then(response => response.json())
                    .then(data => {
                        const district = data.address.suburb || data.address.neighbourhood || "n/a";
                        osmDistrictEl.textContent = district; // Stadtteil von OSM
                    })
                    .catch(error => {
                        console.error("Fehler beim Abrufen der Daten von OpenStreetMap:", error);
                        osmDistrictEl.textContent = "Fehler";
                    });
            });
        }

        // Initialisierung von Autocomplete
        document.addEventListener("DOMContentLoaded", initAutocomplete);
    </script>
</body>
</html>