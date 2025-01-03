<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Berliner Adressdaten (WMS)</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
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

        .info {
            padding: 15px;
            background: white;
            box-shadow: 0 0 5px rgba(0, 0, 0, 0.3);
            border-radius: 5px;
            max-width: 400px;
            margin: 20px auto;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="info">
        <h1>Berliner Adressdaten</h1>
        <p>Die Karte zeigt Adresspunkte der Berliner Adressen aus dem amtlichen Adressbestand.</p>
    </div>
    <div id="map"></div>
    <script>
        // Initialisieren der Karte
        const map = L.map('map').setView([52.52, 13.405], 12); // Mittelpunkt Berlin

        // OSM-Basemap hinzufügen
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '© OpenStreetMap-Mitwirkende'
        }).addTo(map);

        // WMS-Layer hinzufügen
        const wmsLayer = L.tileLayer.wms("https://fbinter.stadt-berlin.de/fb/wms/senstadt/s_adressen", {
            layers: "fis:s_adressen",
            format: "image/png",
            transparent: true,
            version: "1.1.1",
            attribution: 'Geoportal Berlin',
            crs: L.CRS.EPSG3857
        }).addTo(map);
    </script>
</body>
</html>
