<?php

namespace Drupal\google_places_search_form\Controller;

/**
 * @file
 * Autocomplete controller for custom google search.
 */

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Component\Utility\Tags;
use Drupal\Component\Utility\Unicode;
use GuzzleHttp\Exception\RequestException;

/**
 * Controller class to define autocomplete workflow.
 */
class AutocompleteController extends ControllerBase {

  /**
   * Function to handle autocomplete request.
   */
  public function handleAutocomplete(Request $request) {
    // Get the typed string from the URL, if it exists.
    if ($input = $request->query->get('q')) {
      $typedString = Tags::explode($input);
      $typedString = Unicode::strtolower(array_pop($typedString));
      $trimedString = preg_replace("/\s+/", "+", $typedString);
      $results = $this->showRelatedPlaces($trimedString);
    }
    return new JsonResponse($results);
  }

  /**
   * Function to shows related places to the typed in the autocomplete field.
   */
  public function showRelatedPlaces($trimedString) {
    $results = [];
    $config = $this->config('google_places_search_form.admin_settings');
    $apiKey = trim($config->get('google_api_key'));
    $client = \Drupal::httpClient();
    $endpoint = 'https://maps.googleapis.com/maps/api/place/autocomplete/json?input=' . $trimedString . '&key=' . $apiKey;
    try {
      $request = $client->get($endpoint);
      $response = $request->getBody()->getContents();
      $jasonResponse = json_decode($response);
      for ($i = 0; $i < count($jasonResponse->predictions); $i++) {
        $description = $jasonResponse->predictions[$i]->description;
        $main_text = $jasonResponse->predictions[$i]->structured_formatting->main_text;
        $secondary_text = $jasonResponse->predictions[$i]->structured_formatting->secondary_text;
        $searchedPlace = '<span class="main-text">' . $main_text . '</span>, <span class="secondary-text">' . $secondary_text . '</span>';
        $results[] = [
          'value' => $description,
          'label' => $searchedPlace,
        ];
      }
      return($results);
    }
    catch (RequestException $e) {
      return($this->t('Error occured.'));
    }
  }

}
