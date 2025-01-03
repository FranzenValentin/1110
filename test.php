<!DOCTYPE html>
<!--
 @license
 Copyright 2024 Google LLC. All Rights Reserved.
 SPDX-License-Identifier: Apache-2.0
-->
<html>
  <head>
    <title>Place Autocomplete</title>
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