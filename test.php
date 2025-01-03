<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Google Places Autovervollständigung</title>
    <script
        src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAeGER_6l0H6VCFt9CM1KWMMxKYAfuCiJE&libraries=places"
        async
        defer
    ></script>
    <style>
        #adresse {
            width: 100%;
            padding: 10px;
            font-size: 16px;
        }
    </style>
</head>
<body>
    <table>
        <tr>
            <td id="dick">
                <div>
                    <input type="text" id="adresse" name="adresse" placeholder="Linienstraße 128, Mitte (Adresse)">
                </div>
            </td>
        </tr>
    </table>

    <script>
        // Funktion, um das Autocomplete-Feature zu aktivieren
        function initializeAutocomplete() {
            const adresseInput = document.getElementById('adresse');
            
            // Google Places Autocomplete initialisieren
            const autocomplete = new google.maps.places.Autocomplete(adresseInput, {
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

                console.log('Gewählte Adresse:', place.formatted_address);
            });
        }

        // Warten, bis die API geladen ist
        document.addEventListener("DOMContentLoaded", initializeAutocomplete);
    </script>
</body>
</html>
