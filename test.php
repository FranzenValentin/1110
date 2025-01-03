<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Google Places Autovervollständigung - Berlin</title>
    <script
        src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAeGER_6l0H6VCFt9CM1KWMMxKYAfuCiJE&libraries=places"
        async
        defer
    ></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            padding: 20px;
        }

        #adresse {
            width: 100%;
            padding: 10px;
            font-size: 16px;
        }

        .container {
            max-width: 500px;
            margin: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <label for="adresse">Adresse in Berlin:</label>
        <input type="text" id="adresse" name="adresse" placeholder="Linienstraße 128, Mitte (Adresse)">
    </div>

    <script>
        function initializeAutocomplete() {
            const adresseInput = document.getElementById('adresse');

            // Debugging: Prüfen, ob das Eingabefeld geladen wurde
            if (!adresseInput) {
                console.error("Eingabefeld nicht gefunden!");
                return;
            }

            // Grenzen für Berlin definieren
            const berlinBounds = {
                north: 52.6755, // Nordgrenze
                south: 52.3382, // Südgrenze
                east: 13.7611,  // Ostgrenze
                west: 13.0883   // Westgrenze
            };

            try {
                // Google Places Autocomplete initialisieren
                const autocomplete = new google.maps.places.Autocomplete(adresseInput, {
                    bounds: berlinBounds,
                    strictBounds: true,
                    types: ['geocode'],
                    componentRestrictions: { country: 'de' }
                });

                // Debugging: Zeige die Einstellungen der Autocomplete-Instanz
                console.log("Autocomplete erfolgreich initialisiert:", autocomplete);

                // Event-Listener für die Auswahl eines Vorschlags
                autocomplete.addListener('place_changed', () => {
                    const place = autocomplete.getPlace();

                    if (!place.geometry) {
                        console.error("Kein gültiger Ort ausgewählt.");
                        return;
                    }

                    console.log('Gewählte Adresse:', place.formatted_address);
                });
            } catch (error) {
                console.error("Fehler bei der Initialisierung von Autocomplete:", error);
            }
        }

        // Initialisierung der Autovervollständigung
        document.addEventListener("DOMContentLoaded", () => {
            console.log("DOM geladen. Initialisiere Autocomplete...");
            initializeAutocomplete();
        });
    </script>
</body>
</html>
