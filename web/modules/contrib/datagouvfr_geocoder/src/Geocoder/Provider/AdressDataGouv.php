<?php

namespace Drupal\datagouvfr_geocoder\Geocoder\Provider;

use Geocoder\Exception\NoResult;
use Geocoder\Exception\HttpError;
use Geocoder\Exception\InvalidArgument;
use Geocoder\Provider\AbstractProvider;
use Geocoder\Provider\Provider;

/**
 * Provides a geocoder handler based on adress.data.gouv.fr API .
 */
class AdressDataGouv extends AbstractProvider implements Provider {

  private $baseUrl = "https://api-adresse.data.gouv.fr/";
  const SEARCH = 0;
  const REVERSE = 1;

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return 'adress_data_gouv_fr';
  }

  /**
   * {@inheritdoc}
   */
  public function geocode($address) {

    $result = $this->callApi(self::SEARCH, $address);

    $output = $this->makeOutput($result['features'][0]);

    if ($output != NULL) {
      return $output;
    }
    else {
      throw new NoResult(sprintf('Could not resolved address : "%s".', $address));
    }

  }

  /**
   * {@inheritdoc}
   */
  public function reverse($latitude, $longitude) {

    $result = $this->callApi(self::REVERSE, ['lat' => $latitude, 'lng' => $longitude]);

    $output = $this->makeOutput($result['features'][0]);

    if ($output != NULL) {
      return $output;
    }
    else {
      $position = 'latitude:' . $latitude . ',longitude:' . $longitude;
      throw new NoResult(sprintf('Could not reverse position : "%s".', $position));
    }
  }

  /**
   * Extract data form service.
   *
   * @param mixed $result
   *   Output item of API.
   *
   * @return \Geocoder\Model\AddressCollection|null
   *   Results.
   */
  private function makeOutput($result) {

    if (isset($result['geometry']['coordinates'])) {

      $coord = $result['geometry']['coordinates'];
      $properties = $result['properties'];

      return $this->returnResults([[
        'latitude' => $coord[1],
        'longitude' => $coord[0],
        'streetNumber' => $properties['housenumber'],
        'streetName'   => $properties['street'],
        'locality'     => $properties['city'],
        'postalCode'   => $properties['postcode'],
        'country'      => 'FR',
        'countryCode'  => 'FR',
        'timezone'     => 'Europe/Paris',
      ] + $this->getDefaults(),
      ]);
    }
    else {
      return NULL;
    }
  }

  /**
   * Method which callilng service api.
   *
   * @param mixed $operation
   *   A value between self::SEARCH and self::REVERSE.
   * @param mixed $parameters
   *   Parameters needs for call.
   *
   * @return array
   *   API response if usable
   *
   * @throws \Exception
   */
  private function callApi($operation, $parameters) {

    if ($operation != self::SEARCH && $operation != self::REVERSE) {
      throw new InvalidArgument("Invalid operation provided.");
    }

    $queue = '';
    if ($operation == self::SEARCH) {
      $queue = 'search/?limit=1&q=' . urlencode($parameters);
    }
    if ($operation == self::REVERSE) {
      $queue = 'reverse/?lon=' . $parameters['lng'] . '&lat=' . $parameters['lat'];
    }

    $client = \Drupal::httpClient();
    $request = $client->get($this->baseUrl . $queue);
    $response = $request->getBody()->getContents();

    $out = json_decode($response, TRUE);
    if ($out != NULL) {
      return $out;
    }
    else {
      throw new HttpError('Error when decoding response.');
    }
  }

}
