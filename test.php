<!DOCTYPE html>
<html>
  <head>
    <title>Place Autocomplete - Berlin Restricted</title>
    <script>
      async function init() {
        await customElements.whenDefined("gmp-map");

        const map = document.querySelector("gmp-map");
        const marker = document.getElementById("marker");
        const strictBoundsInputElement = document.getElementById("use-strict-bounds");
        const placePicker = document.getElementById("place-picker");
        const infowindowContent = document.getElementById("infowindow-content");
        const infowindow = new google.maps.InfoWindow();

        // Set Berlin as the map center and bounds
        const berlinBounds = {
          north: 52.6755, // Nordgrenze
          south: 52.3382, // Südgrenze
          east: 13.7611,  // Ostgrenze
          west: 13.0883,  // Westgrenze
        };

        map.innerMap.setOptions({
          mapTypeControl: false,
          center: { lat: 52.5200, lng: 13.4050 }, // Berlin Zentrum
          zoom: 13,
        });

        // Apply Berlin bounds to the place picker
        placePicker.addEventListener("gmpx-placechange", () => {
          const place = placePicker.value;

          if (!place.location) {
            window.alert("Keine Details verfügbar für: '" + place.name + "'");
            infowindow.close();
            marker.position = null;
            return;
          }

          if (place.viewport) {
            map.innerMap.fitBounds(place.viewport);
          } else {
            map.center = place.location;
            map.zoom = 17;
          }

          marker.position = place.location;
          infowindowContent.children["place-name"].textContent = place.displayName;
          infowindowContent.children["place-address"].textContent = place.formattedAddress;
          infowindow.open(map.innerMap, marker);
        });

        // Apply strict bounds for Berlin
        strictBoundsInputElement.checked = true;
        strictBoundsInputElement.addEventListener("change", () => {
          placePicker.strictBounds = strictBoundsInputElement.checked;
        });

        // Sets place picker bounds to Berlin
        placePicker.bounds = berlinBounds;
      }

      document.addEventListener("DOMContentLoaded", init);
    </script>
    <style>
      html,
      body {
        height: 100%;
        margin: 0;
        padding: 0;
      }

      gmp-map:not(:defined) {
        display: none;
      }

      #title {
        color: #fff;
        background-color: #4d90fe;
        font-size: 25px;
        font-weight: 500;
        padding: 6px 12px;
      }

      #infowindow-content {
        display: none;
      }

      .pac-card {
        background-color: #fff;
        border-radius: 2px;
        box-shadow: 0 1px 4px -1px rgba(0, 0, 0, 0.3);
        margin: 10px;
        font: 400 18px Roboto, Arial, sans-serif;
        overflow: hidden;
      }

      .pac-controls {
        display: inline-block;
        padding: 5px 11px;
      }

      .pac-controls label {
        font-size: 13px;
        font-weight: 300;
      }

      #place-picker {
        box-sizing: border-box;
        width: 100%;
        padding: 0.5rem 1rem 1rem;
      }
    </style>
  </head>
  <body>
    <script type="module" src="https://unpkg.com/@googlemaps/extended-component-library@0.6"></script>
    <gmpx-api-loader
        key="AIzaSyAeGER_6l0H6VCFt9CM1KWMMxKYAfuCiJE"
        solution-channel="GMP_CCS_autocomplete_v4">
    </gmpx-api-loader>
    <gmp-map id="map" map-id="DEMO_MAP_ID">
      <div slot="control-block-start-inline-start" class="pac-card" id="pac-card">
        <div>
          <div id="title">Autocomplete search - Berlin</div>
          <div id="strict-bounds-selector" class="pac-controls">
            <input type="checkbox" id="use-strict-bounds" value="" checked />
            <label for="use-strict-bounds">Nur innerhalb Berlins suchen</label>
          </div>
        </div>
        <gmpx-place-picker
          id="place-picker"
          for-map="map"
          bounds="52.3382,13.0883|52.6755,13.7611">
        </gmpx-place-picker>
      </div>
      <gmp-advanced-marker id="marker"></gmp-advanced-marker>
    </gmp-map>
    <div id="infowindow-content">
      <span id="place-name" class="title" style="font-weight: bold;"></span><br />
      <span id="place-address"></span>
    </div>
  </body>
</html>
