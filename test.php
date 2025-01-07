<?php

try {
    loadEnv(__DIR__ . '/../config.env');
} catch (Exception $e) {
    echo "Fehler: " . $e->getMessage();
}

// Definiere den Zugangscode
$apiKey = $_ENV['GOOGLE_MAPS_API_KEY'];

// Funktion, um die .env-Datei zu laden
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
    <title>Google Places Autovervollständigung</title>
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
    </style>
    <script src="https://maps.googleapis.com/maps/api/js?key=<?= htmlspecialchars($apiKey) ?>&libraries=places"></script>
</head>
<body>
    <header style="text-align: center; padding: 20px;">
        <h1>Berliner Adress- und Stadtteil-Autovervollständigung</h1>
    </header>

    <main>
        <div class="form-container">
            <label for="address-input">Hausadresse eingeben:</label>
            <input type="text" id="address-input" placeholder="Linienstraße 128">
            
            <label for="district-input">Stadtteil:</label>
            <input type="text" id="district-input" placeholder="Mitte" readonly>

            <div id="coordinates">
                <p>Koordinaten: <span id="latitude"></span>, <span id="longitude"></span></p>
            </div>
        </div>
    </main>

    <script>
        function initAutocomplete() {
            const addressInput = document.getElementById("address-input");
            const districtInput = document.getElementById("district-input");
            const latitudeEl = document.getElementById("latitude");
            const longitudeEl = document.getElementById("longitude");

            // Create a LatLng for Berlin's center
            const berlinCenter = { lat: 52.5200, lng: 13.4050 };

            // Einschränkungen auf Adresstypen und Land
            const options = {
                types: ['address'], // Nur Adressen
                componentRestrictions: { country: "DE" }, // Nur Deutschland
            };

            // Google Places Autocomplete initialisieren
            const autocomplete = new google.maps.places.Autocomplete(addressInput, options);

            // Set location and radius with strict bounds
            autocomplete.setBounds(
                new google.maps.Circle({
                    center: berlinCenter,
                    radius: 15000 // 15 km radius to cover Berlin
                }).getBounds()
            );
            autocomplete.setOptions({ strictBounds: true });

            // Automatisches Ausfüllen des Stadtteils, Bereinigung der Adresse, und Koordinaten
            autocomplete.addListener("place_changed", () => {
                const place = autocomplete.getPlace();

                if (place.address_components) {
                    const city = place.address_components.find(component =>
                        component.types.includes("locality")
                    );
                    const district = place.address_components.find(component =>
                        component.types.includes("sublocality_level_1") || component.types.includes("political")
                    );
                    const housenumber = place.address_components.find(component =>
                        component.types.includes("street_number")
                    );

                    // Überprüfen, ob die Adresse in Berlin liegt
                    if (city && city.long_name === "Berlin" && housenumber) {
                        if (district) {
                            districtInput.value = district.long_name;
                        } else {
                            districtInput.value = "Unbekannt";
                        }

                        // Bereinige die Adresse: Entferne "Berlin, Deutschland"
                        let formattedAddress = place.formatted_address;
                        formattedAddress = formattedAddress.replace(/, Berlin, Deutschland$/, ''); // Entferne genau "Berlin, Deutschland"
                        formattedAddress = formattedAddress.replace(/, Deutschland$/, ''); // Fallback: Entferne "Deutschland" falls nötig
                        addressInput.value = formattedAddress;

                        // Zeige die Koordinaten an
                        const latitude = place.geometry.location.lat();
                        const longitude = place.geometry.location.lng();
                        latitudeEl.textContent = latitude.toFixed(6); // Breite
                        longitudeEl.textContent = longitude.toFixed(6); // Länge
                    } else {
                        alert("Bitte wählen Sie eine Hausadresse in Berlin aus.");
                        addressInput.value = ""; // Adresse leeren
                        districtInput.value = ""; // Stadtteil leeren
                        latitudeEl.textContent = "n/a";
                        longitudeEl.textContent = "n/a";
                    }
                }
            });

        }

        // Initialisiere Autocomplete bei Seitenladevorgang
        document.addEventListener("DOMContentLoaded", initAutocomplete);
    </script>
</body>
</html>