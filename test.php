<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adresse Autovervollständigung - Berlin</title>
    <script type="module" src="https://unpkg.com/@googlemaps/extended-component-library@0.6"></script>
    <script>
        async function init() {
            await customElements.whenDefined("gmpx-api-loader");

            const placePicker = document.getElementById("place-picker");
            const districtField = document.getElementById("district");

            // Beschränkung der Ergebnisse auf Berlin
            placePicker.bounds = {
                north: 52.6755, // Nordgrenze
                south: 52.3382, // Südgrenze
                east: 13.7611,  // Ostgrenze
                west: 13.0883   // Westgrenze
            };
            placePicker.strictBounds = true;

            placePicker.addEventListener("gmpx-placechange", () => {
                const place = placePicker.value;

                if (!place || !place.addressComponents) {
                    alert("Keine gültige Adresse ausgewählt.");
                    return;
                }

                // Bezirk aus den Adresskomponenten extrahieren
                const components = place.addressComponents;
                let district = "";

                components.forEach(component => {
                    if (component.types.includes("sublocality") || component.types.includes("locality")) {
                        district = component.longName || component.shortName;
                    }
                });

                // Bezirk ins Feld eintragen
                districtField.value = district || "Bezirk nicht verfügbar";
            });
        }

        document.addEventListener("DOMContentLoaded", init);
    </script>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            padding: 20px;
        }

        .container {
            max-width: 500px;
            margin: auto;
        }

        .field {
            margin-bottom: 15px;
        }

        input {
            width: 100%;
            padding: 10px;
            font-size: 16px;
            box-sizing: border-box;
        }
    </style>
</head>
<body>
    <script type="module" src="https://unpkg.com/@googlemaps/extended-component-library@0.6"></script>
    <gmpx-api-loader
        key="AIzaSyAeGER_6l0H6VCFt9CM1KWMMxKYAfuCiJE"
        solution-channel="GMP_CCS_autocomplete_v4">
    </gmpx-api-loader>

    <div class="container">
        <h1>Adresse Autovervollständigung - Berlin</h1>
        <div class="field">
            <label for="place-picker">Adresse:</label>
            <gmpx-place-picker id="place-picker" placeholder="Straße und Hausnummer eingeben"></gmpx-place-picker>
        </div>
        <div class="field">
            <label for="district">Bezirk:</label>
            <input type="text" id="district" placeholder="Bezirk" readonly>
        </div>
    </div>
</body>
</html>
