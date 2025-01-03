<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WFS Berliner Adressen</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/terraformer/1.0.12/terraformer.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/terraformer-wkt-parser/1.2.0/terraformer-wkt-parser.min.js"></script>
    <style>
        #map {
            height: 600px;
            width: 100%;
        }

        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }
    </style>
</head>
<body>
    <h1 style="text-align: center;">WFS Berliner Adressen</h1>
    <div id="map"></div>
    <script>
        // Initialisieren der Karte
        const map = L.map('map').setView([52.52, 13.405], 12); // Zentrum Berlin

        // OSM-Basemap hinzufügen
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '© OpenStreetMap-Mitwirkende'
        }).addTo(map);

        // Funktion zur Abfrage der WFS-Daten
        async function fetchWFSData() {
            const wfsUrl = "https://gdi.berlin.de/services/wfs/adressen_berlin?SERVICE=WFS&REQUEST=GetFeature&TYPENAME=adressen_berlin&OUTPUTFORMAT=application/json";
            try {
                const response = await fetch(wfsUrl);
                if (!response.ok) {
                    throw new Error("Fehler beim Abrufen der WFS-Daten.");
                }
                const data = await response.json();
                return data;
            } catch (error) {
                console.error("Fehler:", error);
                return null;
            }
        }

        // Funktion zur Darstellung der Daten
        async function addWFSDataToMap() {
            const data = await fetchWFSData();

            if (!data) {
                alert("Keine Daten verfügbar.");
                return;
            }

            // GeoJSON Layer hinzufügen
            L.geoJSON(data, {
                onEachFeature: function (feature, layer) {
                    const props = feature.properties;
                    layer.bindPopup(`
                        <b>Adresse:</b> ${props.strassenname || "Nicht verfügbar"} ${props.hausnummer || ""}<br>
                        <b>Bezirk:</b> ${props.bezeichnung || "Nicht verfügbar"}<br>
                        <b>Postleitzahl:</b> ${props.postleitzahl || "Nicht verfügbar"}<br>
                    `);
                },
                pointToLayer: function (feature, latlng) {
                    return L.circleMarker(latlng, {
                        radius: 5,
                        fillColor: "#007bff",
                        color: "#000",
                        weight: 1,
                        opacity: 1,
                        fillOpacity: 0.8
                    });
                }
            }).addTo(map);
        }

        // Daten zur Karte hinzufügen
        addWFSDataToMap();
    </script>
</body>
</html>
