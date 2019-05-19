<?php

/**
 * @file
 * Contains \Drupal\wunderground_weather\Controller\LocationAutocompleteController.
 */

namespace Drupal\wunderground_weather\Controller;

use Drupal\Core\Controller\ControllerBase;
use GuzzleHttp\Client;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;


/**
 * Returns autocomplete responses for wunderground weather.
 */
class LocationAutocompleteController extends ControllerBase {

  /**
   * Autocomplete for searching locations.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request made from the autocomplete widget.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A json respoonse to use in the autocomplete widget.
   */
  public function autocomplete(Request $request) {
    $text = $request->query->get('q');
    $client = new Client(['base_uri' => 'http://autocomplete.wunderground.com']);

    $response = $client->get('aq?query=' . $text)->getBody();
    $data = json_decode($response->getContents());

    // Extract key and value from the returned array.
    $results = [];
    foreach ($data->RESULTS as $result) {
      $results[] = ['value' => $result->name . ' [' . $result->l . ']', 'label' => $result->name];
    }

    return new JsonResponse($results);
  }

}
