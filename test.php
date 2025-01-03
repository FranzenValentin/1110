<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Straßen- und Suburb-Informationen - Berlin</title>
    <script>
        async function fetchStreetSuggestions(query) {
            // Suche auf Straßen in Berlin beschränken
            const apiUrl = `https://nominatim.openstreetmap.org/search?format=json&addressdetails=1&extratags=0&limit=5&countrycodes=de&q=${encodeURIComponent(query + ", Berlin")}`;

            try {
                const response = await fetch(apiUrl);
                if (!response.ok) {
                    throw new Error("Fehler beim Abrufen der Vorschläge.");
                }
                const data = await response.json();
                
                // Nur Ergebnisse mit "road" zurückgeben
                return data.filter(item => item.address && item.address.road);
            } catch (error) {
                console.error("Fehler:", error);
                return [];
            }
        }

        async function handleStreetInput(event) {
            const query = event.target.value;
            const suggestionsContainer = document.getElementById("street-suggestions");

            if (query.length < 3) {
                suggestionsContainer.innerHTML = "";
                return;
            }

            const suggestions = await fetchStreetSuggestions(query);

            if (suggestions.length === 0) {
                suggestionsContainer.innerHTML = "<p>Keine Vorschläge gefunden.</p>";
                return;
            }

            suggestionsContainer.innerHTML = suggestions
                .map(
                    suggestion => `
                        <div class="suggestion" onclick="selectStreet('${suggestion.address.road}')">
                            ${suggestion.address.road}
                        </div>`
                )
                .join("");
        }

        function selectStreet(street) {
            const streetField = document.getElementById("street");
            const suggestionsContainer = document.getElementById("street-suggestions");

            streetField.value = street; // Nur den Straßennamen übernehmen
            suggestionsContainer.innerHTML = "";
        }

        async function fillSuburb() {
            const street = document.getElementById("street").value;
            const houseNumber = document.getElementById("house-number").value;
            const suburbField = document.getElementById("suburb");

            if (!street || !houseNumber) {
                alert("Bitte geben Sie sowohl Straße als auch Hausnummer ein.");
                return;
            }

            const query = `${street} ${houseNumber}, Berlin, Deutschland`;
            const result = await fetchStreetSuggestions(query);

            if (result.length === 0 || !result[0].address) {
                suburbField.value = "Suburb nicht gefunden";
                return;
            }

            suburbField.value = result[0].address.suburb || "Suburb nicht verfügbar";
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

        #street-suggestions {
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
        <h1>Adresse und Suburb-Informationen - Berlin</h1>
        <div class="field">
            <label for="street">Straße:</label>
            <input type="text" id="street" oninput="handleStreetInput(event)" placeholder="Straße eingeben">
            <div id="street-suggestions"></div>
        </div>
        <div class="field">
            <label for="house-number">Hausnummer:</label>
            <input type="text" id="house-number" placeholder="Hausnummer eingeben">
        </div>
        <div class="field">
            <label for="suburb">Suburb:</label>
            <input type="text" id="suburb" placeholder="Suburb" readonly>
        </div>
        <div class="field">
            <button onclick="fillSuburb()">Suburb abrufen</button>
        </div>
    </div>
</body>
</html>
