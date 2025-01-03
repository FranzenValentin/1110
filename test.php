<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Berliner Adressdaten Export</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            padding: 20px;
        }

        #data-container {
            white-space: pre-wrap;
            background: #f4f4f4;
            padding: 15px;
            border: 1px solid #ddd;
            overflow-x: auto;
            max-height: 500px;
        }

        button {
            padding: 10px 20px;
            font-size: 16px;
            background-color: #007bff;
            color: #fff;
            border: none;
            cursor: pointer;
            border-radius: 5px;
            margin-top: 15px;
        }

        button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <h1>Berliner Adressdaten</h1>
    <p>Klicken Sie auf den Button, um alle Adressdaten aus der Datenbank abzurufen.</p>
    <button onclick="fetchAllData()">Daten abrufen</button>
    <div id="data-container">Die Daten werden hier angezeigt...</div>

    <script>
        const WFS_URL = "https://gdi.berlin.de/services/wfs/adressen_berlin";

        async function fetchAllData() {
            const dataContainer = document.getElementById("data-container");
            dataContainer.textContent = "Lade Daten...";

            const params = new URLSearchParams({
                service: "WFS",
                request: "GetFeature",
                typename: "adressen_berlin", // Stellen Sie sicher, dass dieser Name korrekt ist!
                outputFormat: "application/json",
            });

            try {
                const response = await fetch(`${WFS_URL}?${params.toString()}`);
                if (!response.ok) {
                    throw new Error(`Fehler beim Abrufen der Daten: ${response.statusText}`);
                }

                const data = await response.json();
                console.log("Daten erfolgreich geladen:", data);
                dataContainer.textContent = JSON.stringify(data, null, 2); // Formatiertes JSON
            } catch (error) {
                console.error("Fehler:", error);
                dataContainer.textContent = `Fehler beim Abrufen der Daten: ${error.message}`;
            }
        }
    </script>
</body>
</html>
