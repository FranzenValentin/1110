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

        #coordinates {
            margin-top: 15px;
            font-weight: bold;
            text-align: center;
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
                        const formattedAddress = place.formatted_address.replace(/, Berlin, Deutschland$/, '');
                        addressInput.value = formattedAddress;

                        // Zeige die Koordinaten an
                        const latitude = place.geometry.location.lat();
                        const longitude = place.geometry.location.lng();
                        latitudeEl.textContent = latitude.toFixed(10); // Breite
                        longitudeEl.textContent = longitude.toFixed(10); // Länge

                        console.log("Valid address:", formattedAddress);
                        console.log("Koordinaten:", latitude, longitude);
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
