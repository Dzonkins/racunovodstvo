/*
var user_location;
(function ($, Drupal, drupalSettings){
    'use strict';
    user_location = drupalSettings.user_location;
    Drupal.behaviors.appendVar = {
        attach: function(context, settings) {

            var myvar = 'this is the var i want to send';
            $('#edit-map-test').once('appendVar').val(user_location);
        }
    }
})(jQuery, Drupal, drupalSettings);
*/
let
  maps = {},
  markers = {},
  infoWindow,
  defaultLatlng = { lat: -38.34246924289325, lng: 143.58532751145233 },
  current_location_marker = {};
  current_marker_red = {}
  previousMarker = {},
  user_location = {};

function initMap()
{
    for (var webform_key in drupalSettings.webform_gmap_field) {
        let map_config = drupalSettings.webform_gmap_field[webform_key];

        user_location[webform_key] = JSON.parse(map_config.user_location);

        maps[webform_key] = new google.maps.Map(
            document.getElementById(webform_key + "-map"), {
                zoom: 10,
                center: defaultLatlng,
            }
        );

        // For edit wbform display (retrive the stored value and displayo on map)
        if (user_location[webform_key]) {
            previousMarker[webform_key] = new google.maps.Marker(
                {
                    position: user_location[webform_key],
                    map: maps[webform_key]
                }
            );
            maps[webform_key].setCenter(user_location[webform_key]);
            maps[webform_key].setZoom(14);
        }
        if (drupalSettings.webform_gmap_field[webform_key].editable) {
            if (!user_location[webform_key]) {
                setFormLatLng(webform_key, defaultLatlng.lat, defaultLatlng.lng);
                locateMe(webform_key, maps[webform_key]);

            }
            // Configure the click listener.
            maps[webform_key].addListener(
                "click", (mapsMouseEvent) => {
                let latLng = mapsMouseEvent.latLng.toJSON();
                setFormLatLng(webform_key, latLng.lat, latLng.lng);
                if (previousMarker[webform_key]) {
                    previousMarker[webform_key].setMap(null);
                }
                if (current_marker_red[webform_key]) {
                    current_marker_red[webform_key].setMap(null);
                }
                previousMarker[webform_key] = new google.maps.Marker(
                        {
                        position: mapsMouseEvent.latLng,
                        map: maps[webform_key]
                        }
                    );
                }
            );

            infoWindow = new google.maps.InfoWindow();
            const locationButton = document.createElement("div");
            locationButton.className = "locate_btn";
            locationButton.textContent = "Locate me";
            locationButton.classList.add("custom-map-control-button");
            maps[webform_key].controls[google.maps.ControlPosition.TOP_CENTER].push(locationButton);
            locationButton.addEventListener(
                "click", () => {
                    locateMe(webform_key, maps[webform_key], true);
                }
            );
        }

        geocoder = new google.maps.Geocoder();
    }
}

function locateMe(webform_key, map, isclicked = false)
{
    // Try HTML5 geolocation.
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            (position) => {
                const pos = {
                    lat: position.coords.latitude,
                    lng: position.coords.longitude,
                };
                /*infoWindow.setPosition(pos);
                infoWindow.setContent("Location found.");
                infoWindow.open(map);*/

                // document.getElementById("webform_gmap_field").value = JSON.stringify(pos, null, 2);
                setFormLatLng(webform_key, pos.lat, pos.lng);
                map.setCenter(pos);
                map.setZoom(14);
                const locate_icon = "/modules/contrib/webform_gmap_field/images/locate.png";
                const red_icon = "/modules/contrib/webform_gmap_field/images/red_marker.png";
                //codeLatLng(pos.lat, pos.lng);

                if (current_location_marker[webform_key] && current_marker_red[webform_key]) {
                    current_location_marker[webform_key].setMap(null);
                    current_marker_red[webform_key].setMap(null);
                }
                if (previousMarker[webform_key]) {
                    previousMarker[webform_key].setMap(null);
                }
                current_location_marker[webform_key] = new google.maps.Marker(
                    {
                        position: pos,
                        icon: locate_icon,
                        map: map
                    }
                );
            current_marker_red[webform_key] = new google.maps.Marker(
                {
                    position: pos,
                    map: map,
                    zIndex: 100,
                    icon: red_icon
                }
            );
            },
            () => {
                if (isclicked == true) {
                    alert("Your location service is off, please turn it on first from the browser setting.");
                }
                //handleLocationError(true, infoWindow, map.getCenter());
            }
        );
    } else {
        //console.log("no browser location support");
        // Browser doesn't support Geolocation
        handleLocationError(false, infoWindow, map.getCenter());
    }
}

function handleLocationError(browserHasGeolocation, infoWindow, pos)
{
    infoWindow.setPosition(pos);
    infoWindow.setContent(
        browserHasGeolocation
        ? "Error: The Geolocation service failed."
        : "Error: Your browser doesn't support geolocation."
    );
    //infoWindow.open(map);
}

function codeLatLng(lat, lng)
{
    var latlng = new google.maps.LatLng(lat, lng);
    geocoder.geocode(
        { 'latLng': latlng }, function (results, status) {
            if (status == google.maps.GeocoderStatus.OK) {
                //console.log(results);
                if (results[1]) {
                    var indice = 0;
                    for (var j = 0; j < results.length; j++) {
                        if (results[j].types[0] == 'locality') {
                            indice = j;
                            break;
                        }
                    }
                    alert('The good number is: ' + j);
                    console.log(results[j]);
                    for (var i = 0; i < results[j].address_components.length; i++) {
                        if (results[j].address_components[i].types[0] == "locality") {
                            //this is the object you are looking for City
                            city = results[j].address_components[i];
                        }
                        if (results[j].address_components[i].types[0] == "administrative_area_level_1") {
                            //this is the object you are looking for State
                            region = results[j].address_components[i];
                        }
                        if (results[j].address_components[i].types[0] == "country") {
                              //this is the object you are looking for
                              country = results[j].address_components[i];
                        }
                    }

                    //city data
                    alert(city.long_name + " || " + region.long_name + " || " + country.short_name)

                } else {
                    alert("No results found");
                }
                //}
            } else {
                alert("Geocoder failed due to: " + status);
            }
        }
    );
}

function setFormLatLng(webform_key, lat, lng)
{
    let
    lat_selector = webform_key + '[lat]',
    lng_selector = webform_key + '[lng]';

    document.getElementsByName(lat_selector)[0].value = lat;
    document.getElementsByName(lng_selector)[0].value = lng;
}
