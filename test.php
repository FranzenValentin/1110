<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Berliner Hausadressen Autovervollständigung</title>
    <style>
        #autocomplete-list {
            position: absolute;
            border: 1px solid #ccc;
            border-radius: 4px;
            background-color: white;
            max-height: 200px;
            overflow-y: auto;
            z-index: 1000;
        }

        #autocomplete-list div {
            padding: 10px;
            cursor: pointer;
        }

        #autocomplete-list div:hover {
            background-color: #e9e9e9;
        }

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
    </style>
</head>
<body>
    <header style="text-align: center; padding: 20px;">
        <h1>Berliner Hausadressen Autovervollständigung</h1>
    </header>

    <main>
        <div class="form-container">
            <label for="address-input">Hausadresse eingeben:</label>
            <input type="text" id="address-input" placeholder="Geben Sie eine Adresse in Berlin ein" autocomplete="off">
            <div id="autocomplete-list"></div>
        </div>
    </main>

    <script>
        const input = document.getElementById("address-input");
        const autocompleteList = document.getElementById("autocomplete-list");

        input.addEventListener("input", async function () {
            const query = this.value.trim();

            // Clear previous suggestions
            autocompleteList.innerHTML = "";

            if (query.length < 3) return; // Start searching only after 3 characters

            try {
                // Define a viewbox for Berlin (coordinates: South-West and North-East corners)
                const berlinViewBox = "13.0884,52.3383,13.7612,52.6755"; // Approx Berlin bounding box

                // Fetch suggestions from Nominatim API limited to the Berlin area
                const response = await fetch(
                    `https://nominatim.openstreetmap.org/search?format=json&addressdetails=1&q=${encodeURIComponent(query)}&viewbox=${berlinViewBox}&bounded=1`
                );

                const data = await response.json();

                if (data.length === 0) return;

                // Filter results to include only those with house numbers
                const filteredData = data.filter((item) => {
                    return item.address && item.address.house_number; // Only addresses with house numbers
                });

                // Populate suggestions
                filteredData.forEach((item) => {
                    const suggestion = document.createElement("div");
                    suggestion.textContent = `${item.address.road} ${item.address.house_number}, ${item.address.city || item.address.town || item.address.village}`;
                    suggestion.addEventListener("click", function () {
                        input.value = suggestion.textContent; // Set the selected value
                        autocompleteList.innerHTML = ""; // Clear suggestions
                    });
                    autocompleteList.appendChild(suggestion);
                });
            } catch (error) {
                console.error("Fehler beim Abrufen der Daten:", error);
            }
        });

        // Close the list if clicked outside
        document.addEventListener("click", function (event) {
            if (!autocompleteList.contains(event.target) && event.target !== input) {
                autocompleteList.innerHTML = ""; // Clear suggestions
            }
        });
    </script>
</body>
</html>
