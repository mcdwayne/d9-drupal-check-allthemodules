<?php
namespace Drupal\geofield_proximity_filter\Controller;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Component\Utility\Tags;
use Drupal\Component\Utility\Unicode;

/**
 * Provides route responses for the Example module.
 */
class ProximityController extends ControllerBase {

  /**
   *
   * @return array
   *   A simple renderable array.
   */
  public function getLnt(Request $request){
  $term = $request->query->get('q');
  $api_key = \Drupal::config('geofield_map.settings')->get('gmap_api_key');
  $url = "https://maps.googleapis.com/maps/api/geocode/json?address=$term&sensor=false&key=api_key";
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_PROXYPORT, 3128);
  curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
  $response = curl_exec($ch);
  curl_close($ch);
  $result = json_decode($response);
  $line = [];
  $lat = $result->results[0]->geometry->location->lat;
  $long = $result->results[0]->geometry->location->lng;
  $line['lat'] = $lat;
  $line['long'] = $long;
  return new JsonResponse($line);
 }
}
