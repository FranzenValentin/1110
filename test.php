
<!DOCTYPE html>
<!--
 @license
 Copyright 2024 Google LLC. All Rights Reserved.
 SPDX-License-Identifier: Apache-2.0
-->
<html>
  <head>
    <title>Place Autocomplete</title>
    <script>
      /**
       * @license
       * Copyright 2024 Google LLC. All Rights Reserved.
       * SPDX-License-Identifier: Apache-2.0
       */
      async function init() {
        await customElements.whenDefined('gmp-map');

        const map = document.querySelector("gmp-map");
        const marker = document.getElementById("marker");
        const strictBoundsInputElement = document.getElementById("use-strict-bounds");
        const placePicker = document.getElementById("place-picker");
        const infowindowContent = document.getElementById("infowindow-content");
        const infowindow = new google.maps.InfoWindow();

        map.innerMap.setOptions({mapTypeControl: false});
        infowindow.setContent(infowindowContent);

        placePicker.addEventListener('gmpx-placechange', () => {
          const place = placePicker.value;

          if (!place.location) {
            window.alert(
              "No details available for input: '" + place.name + "'"
            );
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

        // Sets a listener on a radio button to change the filter type on the place picker
        function setupClickListener(id, type) {
          const radioButton = document.getElementById(id);
          radioButton.addEventListener("click", () => {
            placePicker.type = type;
          });
        }
        setupClickListener("changetype-all", "");
        setupClickListener("changetype-address", "address");
        setupClickListener("changetype-establishment", "establishment");
        setupClickListener("changetype-geocode", "geocode");
        setupClickListener("changetype-cities", "(cities)");
        setupClickListener("changetype-regions", "(regions)");

        strictBoundsInputElement.addEventListener("change", () => {
          placePicker.strictBounds = strictBoundsInputElement.checked;
        });
      }

      document.addEventListener('DOMContentLoaded', init);
    </script>
    <style>
      /**
       * @license
       * Copyright 2024 Google LLC. All Rights Reserved.
       * SPDX-License-Identifier: Apache-2.0
       */

      /*
       * Optional: Makes the sample page fill the window.
       */
      html, body {
        height: 100%;
        margin: 0;
        padding: 0;
      }

      /*
       * Prevent a flash of unstyled content before the custom element is defined.
       */
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
    <!--
     This loads helper components from the Extended Component Library,
     https://github.com/googlemaps/extended-component-library.
     Please note unpkg.com is unaffiliated with Google Maps Platform.
    -->
    <script type="module" src="https://unpkg.com/@googlemaps/extended-component-library@0.6">
    </script>
    <gmpx-api-loader
        key="AIzaSyAeGER_6l0H6VCFt9CM1KWMMxKYAfuCiJE"
        solution-channel="GMP_CCS_autocomplete_v4">
    </gmpx-api-loader>
    <gmp-map id="map" center="40.749933,-73.98633" zoom="13" map-id="DEMO_MAP_ID">
      <div slot="control-block-start-inline-start" class="pac-card" id="pac-card">
        <div>
          <div id="title">Autocomplete search</div>
          <div id="type-selector" class="pac-controls">
            <input type="radio" name="type" id="changetype-all" checked="checked" />
            <label for="changetype-all">All</label>

            <input type="radio" name="type" id="changetype-establishment" />
            <label for="changetype-establishment">establishment</label>

            <input type="radio" name="type" id="changetype-address" />
            <label for="changetype-address">address</label>

            <input type="radio" name="type" id="changetype-geocode" />
            <label for="changetype-geocode">geocode</label>

            <input type="radio" name="type" id="changetype-cities" />
            <label for="changetype-cities">(cities)</label>

            <input type="radio" name="type" id="changetype-regions" />
            <label for="changetype-regions">(regions)</label>
          </div>
          <br />
          <div id="strict-bounds-selector" class="pac-controls">
            <input type="checkbox" id="use-strict-bounds" value="" />
            <label for="use-strict-bounds">Restrict to map viewport</label>
          </div>
        </div>
        <gmpx-place-picker id="place-picker" for-map="map"></gmpx-place-picker>
      </div>
      <gmp-advanced-marker id="marker"></gmp-advanced-marker>
    </gmp-map>
    <div id="infowindow-content">
      <span id="place-name" class="title" style="font-weight: bold;"></span><br />
      <span id="place-address"></span>
    </div>
  </body>
</html>
