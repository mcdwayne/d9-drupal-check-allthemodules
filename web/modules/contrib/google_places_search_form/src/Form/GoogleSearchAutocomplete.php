<?php

namespace Drupal\google_places_search_form\Form;

/**
 * @file
 * Contains \Drupal\google_places_search_form\Form\GoogleSearchAutocomplete.
 */

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;

/**
 * Class to define skeleton of Google Places Search form.
 */
class GoogleSearchAutocomplete extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'google_places_search_form_autocomplete';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['google_places_search'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Searchfield'),
      '#autocomplete_route_name' => 'google_places_search_form.autocomplete',
      '#required' => TRUE,
    ];
    $config = $this->config('google_places_search_form.admin_settings');
    $showDistance = $config->get('show_distance_field');
    if ($showDistance) {
      $form['distance'] = [
        '#type' => 'number',
        '#title' => $this->t('Distance'),
      ];
    }
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Search'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('google_places_search_form.admin_settings');
    $apiKey = trim($config->get('google_api_key'));
    $destinationPagePath = trim($config->get('destination_page_path'), '/');
    $placeId = $this->getPlaceId($form_state->getValue('google_places_search'));
    $showDistance = $config->get('show_distance_field');
    $distanceIn = $config->get('distance_parameter');
    if ($showDistance) {
      $distance = $form_state->getValue('distance');
    }
    else {
      $distance = 100;
    }
    $client = \Drupal::httpClient();
    $endpoint = 'https://maps.googleapis.com/maps/api/place/details/json?placeid=' . $placeId . '&key=' . $apiKey;
    try {
      $request = $client->get($endpoint);
      $response = $request->getBody()->getContents();
      $jsonResponse = json_decode($response);
      $lat = $jsonResponse->result->geometry->location->lat;
      $lng = $jsonResponse->result->geometry->location->lng;
      $url = $destinationPagePath . '/' . $lat . '%2C' . $lng . '<=' . $distance . $distanceIn;
      $response = new TrustedRedirectResponse($url);
      $form_state->setResponse($response);
    }
    catch (RequestException $e) {
      return($this->t('Error occured.'));
    }
  }

  /**
   * Function to return formated string.
   */
  public function getFormatedString($string) {
    $stringWithoutComma = preg_replace("/,/", "", $string);
    $formatedString = strtolower(preg_replace("/ /", "+", $stringWithoutComma));
    return $formatedString;
  }

  /**
   * Function to return the place id os the provided address.
   */
  public function getPlaceId($enteredString) {
    $formatedAddress = $this->getFormatedString($enteredString);
    $config = $this->config('google_places_search_form.admin_settings');
    $apiKey = trim($config->get('google_api_key'));
    $client = \Drupal::httpClient();
    $endpoint = 'https://maps.googleapis.com/maps/api/place/autocomplete/json?input=' . $formatedAddress . '&key=' . $apiKey;
    try {
      $request = $client->get($endpoint);
      $response = $request->getBody()->getContents();
      $jsonResponse = json_decode($response);
      for ($i = 0; $i < count($jsonResponse->predictions); $i++) {
        $description = $jsonResponse->predictions[$i]->description;
        if ($description == $enteredString) {
          $placeId = $jsonResponse->predictions[$i]->place_id;
          break;
        }
      }
      return($placeId);
    }
    catch (RequestException $e) {
      return($this->t('Error occured.'));
    }
  }

}
