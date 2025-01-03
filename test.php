<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adresse Autovervollständigung - Berlin</title>
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
            box-sizing: border-box;
        }

        .container {
            max-width: 500px;
            margin: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <label for="adresse">Adresse in Berlin (Straße Hausnummer, Bezirk):</label>
        <input type="text" id="adresse" name="adresse" placeholder="Linienstraße 128, Mitte">
    </div>

    <script>
        function initializeAutocomplete() {
            const adresseInput = document.getElementById('adresse');

            // Grenzen für Berlin definieren
            const berlinBounds = {
                north: 52.6755, // Nordgrenze
                south: 52.3382, // Südgrenze
                east: 13.7611,  // Ostgrenze
                west: 13.0883   // Westgrenze
            };

            // Google Places Autocomplete initialisieren
            const autocomplete = new google.maps.places.Autocomplete(adresseInput, {
                bounds: berlinBounds,
                strictBounds: true, // Nur Vorschläge innerhalb der Grenzen
                types: ['address'], // Nur Adressen
                componentRestrictions: { country: 'de' } // Beschränkung auf Deutschland
            });

            // Event-Listener für die Auswahl eines Vorschlags
            autocomplete.addListener('place_changed', () => {
                const place = autocomplete.getPlace();

                if (!place.geometry) {
                    console.error("Kein gültiger Ort ausgewählt.");
                    return;
                }

                // Extrahiere die Adresse im gewünschten Format
                const components = place.address_components;
                let street = '';
                let number = '';
                let district = '';

                components.forEach(component => {
                    if (component.types.includes('route')) {
                        street = component.long_name;
                    }
                    if (component.types.includes('street_number')) {
                        number = component.long_name;
                    }
                    if (component.types.includes('sublocality') || component.types.includes('locality')) {
                        district = component.long_name;
                    }
                });

                const formattedAddress = `${street} ${number}, ${district}`;
                console.log('Formatierte Adresse:', formattedAddress);
                alert(`Adresse ausgewählt: ${formattedAddress}`);
            });
        }

        // Initialisierung der Autovervollständigung
        document.addEventListener("DOMContentLoaded", initializeAutocomplete);
    </script>
</body>
</html>