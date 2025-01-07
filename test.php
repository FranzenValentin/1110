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

    const berlinCenter = { lat: 52.5200, lng: 13.4050 };

    const options = {
        types: ['address'],
        componentRestrictions: { country: "DE" },
    };

    const autocomplete = new google.maps.places.Autocomplete(addressInput, options);

    autocomplete.setBounds(
        new google.maps.Circle({
            center: berlinCenter,
            radius: 15000
        }).getBounds()
    );
    autocomplete.setOptions({ strictBounds: true });

    autocomplete.addListener("place_changed", () => {
        const place = autocomplete.getPlace();

        if (!place.geometry || !place.geometry.location) {
            alert("Koordinaten konnten nicht bestimmt werden.");
            return;
        }

        const city = place.address_components.find(component =>
            component.types.includes("locality")
        );

        const district = place.address_components.find(component =>
            component.types.includes("sublocality_level_1") || component.types.includes("political")
        );

        const housenumber = place.address_components.find(component =>
            component.types.includes("street_number")
        );

        if (!city || city.long_name !== "Berlin" || !housenumber) {
            alert("Bitte wählen Sie eine Hausadresse in Berlin aus.");
            addressInput.value = "";
            districtInput.value = "";
            latitudeEl.textContent = "n/a";
            longitudeEl.textContent = "n/a";
            return;
        }

        if (district) {
            districtInput.value = district.long_name;
        } else {
            districtInput.value = city.long_name; // Fallback
        }

        let formattedAddress = place.formatted_address.replace(/, Berlin, Deutschland$/, '').replace(/, Deutschland$/, '');
        addressInput.value = formattedAddress;

        const latitude = place.geometry.location.lat();
        const longitude = place.geometry.location.lng();
        latitudeEl.textContent = latitude.toFixed(6);
        longitudeEl.textContent = longitude.toFixed(6);
    });
}

document.addEventListener("DOMContentLoaded", initAutocomplete);

    </script>
</body>
</html>