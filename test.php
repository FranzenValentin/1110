<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adresse Informationen mit OpenStreetMap</title>
    <script>
        async function fetchAddressDetails(query) {
            const apiUrl = `https://nominatim.openstreetmap.org/search?format=json&addressdetails=1&q=${encodeURIComponent(query)}`;

            try {
                const response = await fetch(apiUrl);
                if (!response.ok) {
                    throw new Error("Fehler beim Abrufen der Daten.");
                }
                const data = await response.json();

                if (data.length === 0) {
                    return null;
                }

                return data[0]; // Erstes Ergebnis verwenden
            } catch (error) {
                console.error("Fehler:", error);
                return null;
            }
        }

        async function handleAddressSearch() {
            const query = document.getElementById("address-input").value;
            const resultContainer = document.getElementById("result");

            if (!query) {
                alert("Bitte geben Sie eine Adresse ein.");
                return;
            }

            resultContainer.innerHTML = "Suche nach Adresse...";

            const addressDetails = await fetchAddressDetails(query);

            if (!addressDetails) {
                resultContainer.innerHTML = "<p>Keine Daten für die eingegebene Adresse gefunden.</p>";
                return;
            }

            // Details aufbereiten
            const { lat, lon, display_name, address } = addressDetails;
            const addressComponents = address
                ? Object.entries(address).map(([key, value]) => `<li><strong>${key}:</strong> ${value}</li>`).join("")
                : "<li>Keine Adresskomponenten verfügbar</li>";

            resultContainer.innerHTML = `
                <h2>Adresse Details</h2>
                <p><strong>Formatierte Adresse:</strong> ${display_name}</p>
                <p><strong>Geografische Position:</strong> Lat: ${lat}, Lng: ${lon}</p>
                <h3>Adresskomponenten:</h3>
                <ul>${addressComponents}</ul>
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
            max-width: 600px;
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

        ul {
            padding-left: 20px;
        }

        ul li {
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Adresse Informationen mit OpenStreetMap</h1>
        <div class="field">
            <label for="address-input">Adresse eingeben:</label>
            <input type="text" id="address-input" placeholder="Straße, Hausnummer, Stadt eingeben">
        </div>
        <div class="field">
            <button onclick="handleAddressSearch()">Adresse suchen</button>
        </div>
        <div id="result">
            <p>Geben Sie eine Adresse ein und klicken Sie auf "Adresse suchen", um Informationen zu erhalten.</p>
        </div>
    </div>
</body>
</html>
