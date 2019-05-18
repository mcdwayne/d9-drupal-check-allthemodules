<?php

namespace Drupal\geolocation_arcgis;

interface ArcGISManagerInterface {
    public function getMapTypes();
    public function getGeocodeApiUrl();
    public function getApiToken();
    public function getHomeLocation();
    public function setHomeLocation($location);
    public function setHomeLocationCoords($latitude, $longitude);
}
