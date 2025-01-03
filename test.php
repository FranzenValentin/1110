<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Berliner Adresssuche</title>
    <script>
        async function fetchAddressData(street, houseNumber) {
            const baseUrl = "https://fbinter.stadt-berlin.de/fb/wfs/data/senstadt/s_adressen";
            const params = new URLSearchParams({
                service: "WFS",
                version: "2.0.0",
                request: "GetFeature",
                typename: "fis:s_adressen",
                outputFormat: "application/json",
                filter: `
                    <Filter xmlns="http://www.opengis.net/ogc">
                        <And>
                            <PropertyIsLike wildCard="%" singleChar="_" escapeChar="\\">
                                <PropertyName>strassenname</PropertyName>
                                <Literal>%${street}%</Literal>
                            </PropertyIsLike>
                            <PropertyIsEqualTo>
                                <PropertyName>hausnummer</PropertyName>
                                <Literal>${houseNumber}</Literal>
                            </PropertyIsEqualTo>
                        </And>
                    </Filter>
                `
            });

            try {
                const response = await fetch(`${baseUrl}?${params}`);
                if (!response.ok) {
                    throw new Error("Fehler beim Abrufen der Daten.");
                }
                return await response.json();
            } catch (error) {
                console.error("Fehler:", error);
                return null;
            }
        }

        async function handleSearch() {
            const street = document.getElementById("street").value;
            const houseNumber = document.getElementById("house-number").value;
            const resultContainer = document.getElementById("result");

            if (!street || !houseNumber) {
                alert("Bitte geben Sie sowohl Straße als auch Hausnummer ein.");
                return;
            }

            resultContainer.innerHTML = "Daten werden geladen...";

            const data = await fetchAddressData(street, houseNumber);

            if (!data || !data.features || data.features.length === 0) {
                resultContainer.innerHTML = "<p>Keine Daten gefunden.</p>";
                return;
            }

            const feature = data.features[0];
            const properties = feature.properties;

            resultContainer.innerHTML = `
                <h2>Adressdaten</h2>
                <p><strong>Straße:</strong> ${properties.strassenname || "Nicht verfügbar"}</p>
                <p><strong>Hausnummer:</strong> ${properties.hausnummer || "Nicht verfügbar"}</p>
                <p><strong>Bezirk:</strong> ${properties.bezeichnung || "Nicht verfügbar"}</p>
                <p><strong>Postleitzahl:</strong> ${properties.postleitzahl || "Nicht verfügbar"}</p>
                <p><strong>Koordinaten:</strong> Lat: ${feature.geometry.coordinates[1]}, Lng: ${feature.geometry.coordinates[0]}</p>
            `;
        }
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

        button {
            padding: 10px 20px;
            font-size: 16px;
            background-color: #007bff;
            color: #fff;
            border: none;
            cursor: pointer;
            border-radius: 5px;
        }

        button:hover {
            background-color: #0056b3;
        }

        #result {
            margin-top: 20px;
            padding: 15px;
            border: 1px solid #ddd;
            background-color: #f9f9f9;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Berliner Adresssuche</h1>
        <div class="field">
            <label for="street">Straße:</label>
            <input type="text" id="street" placeholder="Straße eingeben">
        </div>
        <div class="field">
            <label for="house-number">Hausnummer:</label>
            <input type="text" id="house-number" placeholder="Hausnummer eingeben">
        </div>
        <div class="field">
            <button onclick="handleSearch()">Suche starten</button>
        </div>
        <div id="result">
            <p>Geben Sie eine Straße und Hausnummer ein und klicken Sie auf "Suche starten", um Adressinformationen zu erhalten.</p>
        </div>
    </div>
</body>
</html>
