<?php

/**
 * @file
 * Provides Drupal\external_data_source\Plugin\ExternalWsSource\Countries.
 */

namespace Drupal\external_data_source\Plugin\ExternalDataSource;

use Drupal\external_data_source\Plugin\ExternalDataSourceBase;
use Symfony\Component\HttpFoundation\Request;
use GuzzleHttp\Client;
use GuzzleHttp\Exception as GuzzleException;

/**
 * Provides a 'Countries' ExternalDataSource.
 *
 * @ExternalDataSource(
 *   id = "countries",
 *   name = @Translation("Countries"),
 *   description = @Translation("This Plugin will gather countries list.")
 * )
 */
class Countries extends ExternalDataSourceBase {

  /**
   *
   * @return string
   */
  public function getPluginId() {
    return 'countries';
  }

  /**
   *
   * @return string
   */
  public function getPluginDefinition() {
    return $this->t('This Plugin will gather countries list.');
  }

  /**
   * setRequest
   * Setting sent request
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   */
  public function setRequest(Request $request) {
    $this->request = $request;
  }


  /**
   * getResponse
   * Call WS to retrieve data
   * @return array
   */
  public function getResponse() {
    if ($this->request && !is_null($this->request->get('q'))) {
      $this->q = $this->request->get('q');
    }
    $data = [];
      $client = new Client();
      try {
        //case it's an auto complete:
        if (!is_null($this->q)) {
          $response = $client->get('https://restcountries.eu/rest/v2/name/' . $this->q);
        }
        else {
          $response = $client->get('https://restcountries.eu/rest/v2/all');
        }
        $data = json_decode($response->getBody()->getContents());
      } catch (GuzzleException $e) {
        watchdog_exception('external_data_source', $e->getMessage());
      }
    return $this->formatResponse($data);
  }

  /**
   * formatResponse
   *
   * @param array $response
   * Formatting data retrieved from ws to match [{"value":"","label":""},
   *   {"value":"", "label":""}] return array $collection retrieved suggestions
   *
   * @return array $collection
   */
  public function formatResponse(array $response) {
    $collection = [];
    foreach ($response as $entry) {
      $collection[] = [
        'value' => $entry->name,
        'label' => t($entry->name),
      ];
    }
    return $collection;
  }

}
