<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Place Autocomplete</title>
    <script type="module" src="https://unpkg.com/@googlemaps/extended-component-library@0.6"></script>
    <script>
      async function init() {
        await customElements.whenDefined("gmpx-api-loader");

        const placePicker = document.getElementById("place-picker");
        const infowindowContent = document.getElementById("infowindow-content");

        placePicker.addEventListener("gmpx-placechange", () => {
          const place = placePicker.value;

          if (!place || !place.displayName) {
            window.alert("Keine Details verfügbar für die Eingabe.");
            return;
          }

          const formattedAddress = place.displayName;
          const fullAddress = place.formattedAddress || "Unbekannte Adresse";

          console.log("Ausgewählte Adresse:", formattedAddress);
          alert(`Ausgewählte Adresse: ${formattedAddress}\nVollständige Adresse: ${fullAddress}`);
        });
      }

      document.addEventListener("DOMContentLoaded", init);
    </script>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            padding: 20px;
        }

        #place-picker {
            width: 100%;
            padding: 10px;
            font-size: 16px;
            box-sizing: border-box;
        }

        .container {
            max-width: 500px;
            margin: auto;
        }
    </style>
</head>
<body>
    <script type="module" src="https://unpkg.com/@googlemaps/extended-component-library@0.6"></script>
    <gmpx-api-loader
        key="AIzaSyAeGER_6l0H6VCFt9CM1KWMMxKYAfuCiJE"
        solution-channel="GMP_CCS_autocomplete_v4">
    </gmpx-api-loader>

    <div class="container">
        <h1>Adresse Autovervollständigung</h1>
        <gmpx-place-picker id="place-picker" placeholder="Geben Sie eine Adresse ein"></gmpx-place-picker>
    </div>

    <div id="infowindow-content" style="display: none;">
        <span id="place-name" class="title"></span><br />
        <span id="place-address"></span>
    </div>
</body>
</html>
