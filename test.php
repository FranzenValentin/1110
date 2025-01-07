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
        // Kommentare ignorieren
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        // Zeilen in Schlüssel-Wert-Paare aufteilen
        $parts = explode('=', $line, 2);

        if (count($parts) == 2) {
            $key = trim($parts[0]);
            $value = trim($parts[1]);

            // Entferne Anführungszeichen, falls vorhanden
            $value = trim($value, '"\'');
            
            // Speichere die Variable in $_ENV und $_SERVER
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
    </style>
    <!-- Google Places API laden -->
    <script src="https://maps.googleapis.com/maps/api/js?key=<?= htmlspecialchars($apiKey) ?>&libraries=places"></script>
</head>
<body>
    <header style="text-align: center; padding: 20px;">
        <h1>Berliner Hausadressen Autovervollständigung</h1>
    </header>

    <main>
        <div class="form-container">
            <label for="address-input">Hausadresse eingeben:</label>
            <input type="text" id="address-input" placeholder="Geben Sie eine Adresse in Berlin ein">
        </div>
    </main>

    <script>
        function initAutocomplete() {
            const input = document.getElementById("address-input");

            // Einschränkungen auf Berlin und Adresstypen
            const options = {
                types: ['address'], // Nur Adressen
                componentRestrictions: { country: "DE" }, // Nur Deutschland
            };

            // Google Places Autocomplete initialisieren
            const autocomplete = new google.maps.places.Autocomplete(input, options);

            // Filtern der Ergebnisse nur für Berlin
            autocomplete.addListener("place_changed", () => {
                const place = autocomplete.getPlace();

                // Überprüfen, ob die Adresse in Berlin liegt
                if (place.address_components) {
                    const city = place.address_components.find(component =>
                        component.types.includes("locality")
                    );
                    const housenumber = place.address_components.find(component =>
                        component.types.includes("street_number")
                    );

                    if (city && city.long_name === "Berlin" && housenumber) {
                        console.log("Valid address:", place.formatted_address);
                    } else {
                        alert("Bitte wählen Sie eine Hausadresse in Berlin aus.");
                        input.value = ""; // Eingabe leeren
                    }
                }
            });
        }

        // Initialisiere Autocomplete bei Seitenladevorgang
        document.addEventListener("DOMContentLoaded", initAutocomplete);
    </script>
</body>
</html>
