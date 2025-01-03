<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adresse Informationen</title>
    <script type="module" src="https://unpkg.com/@googlemaps/extended-component-library@0.6"></script>
    <script>
        async function init() {
            await customElements.whenDefined("gmpx-api-loader");

            const placePicker = document.getElementById("place-picker");
            const resultContainer = document.getElementById("result");

            placePicker.addEventListener("gmpx-placechange", () => {
                const place = placePicker.value;

                if (!place) {
                    alert("Keine gültige Adresse ausgewählt.");
                    return;
                }

                // Extrahiere alle verfügbaren Informationen
                const addressComponents = place.addressComponents || [];
                const formattedAddress = place.formattedAddress || "Nicht verfügbar";
                const location = place.location
                    ? `Lat: ${place.location.lat}, Lng: ${place.location.lng}`
                    : "Nicht verfügbar";
                const viewport = place.viewport
                    ? JSON.stringify(place.viewport.toJSON())
                    : "Nicht verfügbar";

                // Adresskomponenten auflisten
                let componentsHTML = "<ul>";
                addressComponents.forEach(component => {
                    componentsHTML += `<li><strong>${component.types.join(", ")}:</strong> ${component.longName}</li>`;
                });
                componentsHTML += "</ul>";

                // Ergebnisse anzeigen
                resultContainer.innerHTML = `
                    <h2>Adresse Details</h2>
                    <p><strong>Formatierte Adresse:</strong> ${formattedAddress}</p>
                    <p><strong>Geografische Position:</strong> ${location}</p>
                    <p><strong>Viewport:</strong> ${viewport}</p>
                    <h3>Adresskomponenten:</h3>
                    ${componentsHTML}
                `;
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
            max-width: 600px;
            margin: auto;
        }

        .field {
            margin-bottom: 15px;
        }

        #result {
            margin-top: 20px;
            padding: 15px;
            border: 1px solid #ddd;
            background-color: #f9f9f9;
            border-radius: 5px;
        }

        ul {
            padding-left: 20px;
        }

        ul li {
            margin-bottom: 5px;
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
        <h1>Alle Informationen zur Adresse</h1>
        <div class="field">
            <label for="place-picker">Adresse eingeben:</label>
            <gmpx-place-picker id="place-picker" placeholder="Straße und Hausnummer eingeben"></gmpx-place-picker>
        </div>
        <div id="result">
            <p>Wähle eine Adresse aus, um Informationen zu sehen.</p>
        </div>
    </div>
</body>
</html>
