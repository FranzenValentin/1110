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
        </div>
    </main>

    <script>
        function initAutocomplete() {
            const addressInput = document.getElementById("address-input");
            const districtInput = document.getElementById("district-input");

            // Einschränkungen auf Berlin und Adresstypen
            const options = {
                types: ['address'], // Nur Adressen
                componentRestrictions: { country: "DE" }, // Nur Deutschland
                location: new google.maps.LatLng(52.5200, 13.4050), // Mittelpunkt von Berlin
                radius: 15000 // Umkreis von 15 km
            };



            // Google Places Autocomplete initialisieren
            const autocomplete = new google.maps.places.Autocomplete(addressInput, options);

            // Filtern der Ergebnisse nur für Berlin und automatisches Ausfüllen des Stadtteils
            autocomplete.addListener("place_changed", () => {
                const place = autocomplete.getPlace();

                // Überprüfen, ob die Adresse in Berlin liegt
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

                    // Gültigkeit überprüfen
                    if (city && city.long_name === "Berlin" && housenumber) {
                        if (district) {
                            districtInput.value = district.long_name;
                        } else {
                            districtInput.value = "Unbekannt";
                        }
                        console.log("Valid address:", place.formatted_address);
                    } else {
                        alert("Bitte wählen Sie eine Hausadresse in Berlin aus.");
                        addressInput.value = ""; // Adresse leeren
                        districtInput.value = ""; // Stadtteil leeren
                    }
                }
            });
        }

        // Initialisiere Autocomplete bei Seitenladevorgang
        document.addEventListener("DOMContentLoaded", initAutocomplete);
    </script>
</body>
</html>
