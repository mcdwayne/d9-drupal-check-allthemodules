<?php

/**
 * @file
 * Contains \Drupal\geofield\Plugin\migrate\process\GeofieldLatLon.
 */
namespace Drupal\wwaf_import\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Process latitude and longitude and return the value for the D8 geofield.
 *
 * @MigrateProcessPlugin(
 *   id = "wwaf_geofield_latlon"
 * )
 */
class wwafGeofieldLatLon extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    list($lat, $lon) = $value;

//    $geocoded_lat = $row->getSourceProperty('geocoded_lat');
//    $geocoded_lng = $row->getSourceProperty('geocoded_lng');
//    if (!empty($geocoded_lng) && !empty($geocoded_lat)) {
//      if ($destination_property == 'gps/lat') {
//        return $geocoded_lat;
//      }
//      if ($destination_property == 'gps/lng') {
//        return $geocoded_lng;
//      }
//    }

    if (empty($lat) || empty($lon)) {
      // Get lat and long by address
      $location = $value;
      array_shift($location);
      array_shift($location);
      $location = implode(' ', $location);
      $prepAddr = str_replace(' ','+',$location);
      $api_key = \Drupal::config('geolocation.settings')->get('google_map_api_key');
      $geocode=file_get_contents('https://maps.google.com/maps/api/geocode/json?address='.$prepAddr.'&sensor=false&key=' . $api_key);
      $output= json_decode($geocode);
      if ($output->status == 'OK') {
        $value[0] = $output->results[0]->geometry->location->lat;
        $value[1] = $output->results[0]->geometry->location->lng;
//        $row->setSourceProperty('geocoded_lat', $value[0]);
//        $row->setSourceProperty('geocoded_lng', $value[1]);
      }
      if ($destination_property == 'gps/lat') {
        return $value[0];
      }
      if ($destination_property == 'gps/lng') {
        return $value[1];
      }
    }


    return parent::transform($value, $migrate_executable, $row, $destination_property);
  }

}
