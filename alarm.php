<?php
require_once 'parts/session_check.php';
require 'parts/db.php';
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alarme nachtragen</title>
    <link rel="stylesheet" href="styles.css">
    <script src="https://maps.googleapis.com/maps/api/js?key=<?= htmlspecialchars($apiKey) ?>&libraries=places"></script>
</head>
<body>
    <header>
        <h1>Alarme nachtragen</h1>
        <?php include 'parts/menue.php'; ?>
    </header>

    <main>
        <?php include 'parts/new_alarm.php'; ?>
    </main>
    <script>
        function initAutocomplete() {
            const addressInput = document.getElementById("address-input");
            const latitudeEl = document.getElementById("latitude");
            const longitudeEl = document.getElementById("longitude");
            const districtEl = document.getElementById("stadtteil"); // Das Stadtteil-Feld

            const berlinBounds = {
                north: 52.6755,
                south: 52.3383,
                east: 13.7612,
                west: 13.0884,
            };

            const options = {
                types: ["geocode", "establishment"], // Parks und andere Orte einbeziehen
                componentRestrictions: { country: "DE" },
                fields: ["address_components", "geometry", "name"], // Name für Orte wie Parks hinzufügen
            };

            const autocomplete = new google.maps.places.Autocomplete(addressInput, options);
            autocomplete.setBounds(new google.maps.LatLngBounds(
                { lat: berlinBounds.south, lng: berlinBounds.west },
                { lat: berlinBounds.north, lng: berlinBounds.east }
            ));
            autocomplete.setOptions({ strictBounds: true });

            autocomplete.addListener("place_changed", () => {
                const place = autocomplete.getPlace();

                if (!place.geometry || !place.geometry.location) {
                    alert("Koordinaten konnten nicht bestimmt werden.");
                    return;
                }

                const latitude = place.geometry.location.lat();
                const longitude = place.geometry.location.lng();
                latitudeEl.value = latitude.toFixed(10); // Genauigkeit von 10 Dezimalstellen
                longitudeEl.value = longitude.toFixed(10);

                // Adresse oder Parkname ermitteln
                const placeName = place.name || ""; // Name des Ortes, z. B. Parkname
                let formattedAddress = placeName;

                // Kreuzungen erkennen
                const intersection = place.address_components.find(comp => comp.types.includes("intersection"));
                if (intersection) {
                    formattedAddress = intersection.long_name;
                } else {
                    // Standard: Straße und Hausnummer kombinieren
                    const street = place.address_components.find(comp => comp.types.includes("route"));
                    const streetNumber = place.address_components.find(comp => comp.types.includes("street_number"));

                    formattedAddress = street ? street.long_name : "";
                    if (streetNumber) {
                        formattedAddress += " " + streetNumber.long_name;
                    }
                    addressInput.value = formattedAddress.trim();
                }

                // Setze die berechnete Adresse in das Eingabefeld
                addressInput.value = formattedAddress.trim();

                // Stadtteil ermitteln
                const district = place.address_components.find(comp =>
                    comp.types.includes("sublocality") || 
                    comp.types.includes("locality") || 
                    comp.types.includes("administrative_area_level_2")
                );

                if (district) {
                    // Entferne das Präfix "Bezirk" und setze nur den Stadtteil
                    districtEl.value = district.long_name.replace(/^Bezirk\s+/i, "").trim();
                } else {
                    districtEl.value = "Stadtteil nicht gefunden"; // Fallback
                }

                // Debug: Stadtteil prüfen
                console.log("Gefundener Stadtteil: ", district ? district.long_name : "Kein Stadtteil");
                console.log("Ort gefunden: ", placeName);
            });
        }

        // Initialisierung der Autocomplete-Funktion, nachdem die Seite geladen ist
        document.addEventListener("DOMContentLoaded", initAutocomplete);
    </script>

</body>
</html>