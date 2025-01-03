<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Berliner Adressen Vervollständigung</title>
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

        #suggestions {
            margin-top: 5px;
            border: 1px solid #ddd;
            background-color: #fff;
            max-height: 150px;
            overflow-y: auto;
        }

        .suggestion {
            padding: 10px;
            cursor: pointer;
        }

        .suggestion:hover {
            background-color: #f0f0f0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Berliner Adressvervollständigung</h1>
        <div class="field">
            <label for="address-input">Adresse eingeben:</label>
            <input type="text" id="address-input" placeholder="Straße und Hausnummer eingeben" oninput="handleInput(event)">
            <div id="suggestions"></div>
        </div>
    </div>
    <script>
        async function fetchAddressSuggestions(query) {
            const baseUrl = "https://gdi.berlin.de/services/wfs/adressen_berlin";
            const params = new URLSearchParams({
                service: "WFS",
                request: "GetFeature",
                typename: "adressen_berlin",
                outputFormat: "application/json",
                cql_filter: `strassenname LIKE '%${query}%' OR hausnummer LIKE '%${query}%'`,
            });

            try {
                const response = await fetch(`${baseUrl}?${params}`);
                if (!response.ok) {
                    throw new Error("Fehler beim Abrufen der Daten.");
                }
                const data = await response.json();
                return data.features || [];
            } catch (error) {
                console.error("Fehler:", error);
                return [];
            }
        }

        async function handleInput(event) {
            const query = event.target.value.trim();
            const suggestionsContainer = document.getElementById("suggestions");

            if (query.length < 3) {
                suggestionsContainer.innerHTML = "";
                return;
            }

            const suggestions = await fetchAddressSuggestions(query);

            if (suggestions.length === 0) {
                suggestionsContainer.innerHTML = "<p>Keine Vorschläge gefunden.</p>";
                return;
            }

            suggestionsContainer.innerHTML = suggestions
                .map(
                    (feature) => `
                        <div class="suggestion" onclick="selectAddress('${feature.properties.strassenname}', '${feature.properties.hausnummer}')">
                            ${feature.properties.strassenname} ${feature.properties.hausnummer}, ${feature.properties.bezeichnung || ""}
                        </div>`
                )
                .join("");
        }

        function selectAddress(street, houseNumber) {
            const inputField = document.getElementById("address-input");
            const suggestionsContainer = document.getElementById("suggestions");

            inputField.value = `${street} ${houseNumber}`;
            suggestionsContainer.innerHTML = "";
        }
    </script>
</body>
</html>
