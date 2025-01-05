<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Heatmap</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
</head>
<body>
    <h2>Heatmap der Einsätze</h2>
    <div id="map" style="width: 100%; height: 500px;"></div>

    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet.heat/dist/leaflet-heat.js"></script>

    <script>
        // Beispiel-Daten
        const stadtteile = [
            {latitude: 52.5200, longitude: 13.4050, anzahl: 15}, // Mitte
            {latitude: 52.5002, longitude: 13.4192, anzahl: 10}, // Kreuzberg
            {latitude: 52.4885, longitude: 13.4527, anzahl: 5}   // Alt-Treptow
        ];

        // Karte initialisieren
        const map = L.map('map').setView([52.5200, 13.4050], 11);

        // OpenStreetMap-Layer hinzufügen
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);

        // Heatmap-Daten
        const heatData = stadtteile.map(stadtteil => [stadtteil.latitude, stadtteil.longitude, stadtteil.anzahl]);

        // Heatmap hinzufügen
        L.heatLayer(heatData, {
            radius: 25,
            blur: 15,
            maxZoom: 17
        }).addTo(map);
    </script>
</body>
</html>
