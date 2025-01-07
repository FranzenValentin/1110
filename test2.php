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
    <title>Google Places - Detaillierte Adressinformationen</title>
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

        .form-container label {
            font-weight: bold;
            margin-bottom: 5px;
            display: block;
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
    <script src="https://maps.googleapis.com/maps/api/js?key=<?= htmlspecialchars($apiKey) ?>&libraries=places"></script>
</head>
<body>
    <header style="text-align: center; padding: 20px;">
        <h1>Google Places - Detaillierte Adressinformationen</h1>
    </header>

    <main>
        <div class="form-container">
            <label for="address-input">Hausadresse eingeben:</label>
            <input type="text" id="address-input" placeholder="Linienstraße 128">
            
            <div class="details" id="details">
                <h3>Details zur Adresse:</h3>
                <p><strong>Formatted Address:</strong> <span id="formatted-address">n/a</span></p>
                <p><strong>Koordinaten:</strong> Breite: <span id="latitude">n/a</span>, Länge: <span id="longitude">n/a</span></p>
                <p><strong>Postleitzahl:</strong> <span id="postal-code">n/a</span></p>
                <p><strong>Stadt:</strong> <span id="city">n/a</span></p>
                <p><strong>Bundesland:</strong> <span id="state">n/a</span></p>
                <p><strong>Land:</strong> <span id="country">n/a</span></p>
                <h4>Alle Adresskomponenten:</h4>
                <div id="all-components">n/a</div>
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
    const allComponentsEl = document.getElementById("all-components");

    const options = {
        types: ['geocode'], // Erlaubt Adressen
        componentRestrictions: { country: "DE" },
        fields: ['address_components', 'formatted_address', 'geometry'], // Nur relevante Felder
    };

    const autocomplete = new google.maps.places.Autocomplete(addressInput, options);

    autocomplete.addListener("place_changed", () => {
        const place = autocomplete.getPlace();

        if (!place.geometry || !place.geometry.location) {
            alert("Koordinaten konnten nicht bestimmt werden.");
            return;
        }

        // Adresskomponenten extrahieren
        const addressComponents = place.address_components;
        const formattedAddress = place.formatted_address;
        const latitude = place.geometry.location.lat();
        const longitude = place.geometry.location.lng();

        // Einzelne Felder zuweisen
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

        // HTML mit den extrahierten Daten aktualisieren
        formattedAddressEl.textContent = formattedAddress || "n/a";
        latitudeEl.textContent = latitude.toFixed(6);
        longitudeEl.textContent = longitude.toFixed(6);
        postalCodeEl.textContent = postalCode ? postalCode.long_name : "n/a";
        cityEl.textContent = city ? city.long_name : "n/a";
        stateEl.textContent = state ? state.long_name : "n/a";
        countryEl.textContent = country ? country.long_name : "n/a";

        // Alle Adresskomponenten anzeigen
        allComponentsEl.innerHTML = addressComponents.map(component => `
            <p><strong>${component.types.join(", ")}:</strong> ${component.long_name}</p>
        `).join("");
    });
}

document.addEventListener("DOMContentLoaded", initAutocomplete);
    </script>
</body>
</html>
