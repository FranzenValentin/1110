<!DOCTYPE html>
<html>
  <head>
    <title>
      Simple Places Autocomplete - Berlin Restricted
    </title>
    <script type="module" src="https://ajax.googleapis.com/ajax/libs/@googlemaps/extended-component-library/0.6.11/index.min.js">
    </script>
    <style>
      body {
        padding: 25px;
        background-color: #f0f1f3;
        font-family: "Arial", sans-serif;
      }

      #place-picker-box {
        display: flex;
        justify-content: center;
        align-items: center;
      }

      #place-picker-container {
        text-align: left;
      }
    </style>
  </head>
  <body>
    <gmpx-api-loader 
      key="AIzaSyAeGER_6l0H6VCFt9CM1KWMMxKYAfuCiJE" 
      solution-channel="GMP_GE_placepicker_v2">
    </gmpx-api-loader>
    <div id="place-picker-box">
      <div id="place-picker-container">
        <gmpx-place-picker 
          placeholder="Linienstraße 128, Mitte (Adresse)" 
          region="de" 
          types="address" 
          bounds="52.3382,13.0883|52.6755,13.7611">
        </gmpx-place-picker>
      </div>
    </div>
  </body>
</html>
