<?php

namespace Drupal\carto_sync;

use Drupal\Core\Url;
use GuzzleHttp\Client;
use Drupal\Core\Config\ConfigFactory;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;

/**
 * Class CartoSyncApi.
 *
 * @package Drupal\carto_sync
 */
class CartoSyncApi implements CartoSyncApiInterface {

  /**
   * GuzzleHttp\Client definition.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * Drupal\Core\Config\ConfigFactory definition.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * The CARTO user ID.
   *
   * @var string
   */
  protected $cartoId;

  /**
   * The CARTO API Key.
   *
   * @var string
   */
  protected $cartoApiKey;

  /**
   * Boolean indicating whether the service is available or not.
   *
   * @var bool
   */
  protected $validCredentials;

  /**
   * Constructor.
   */
  public function __construct(Client $http_client, ConfigFactory $config_factory) {
    $this->httpClient = $http_client;
    $this->configFactory = $config_factory;

    $this->cartoId = $this->configFactory->get('carto_sync.settings')->get('carto_id');
    $this->cartoApiKey = $this->configFactory->get('carto_sync.settings')->get('carto_api_key');

    $this->validCredentials = $this->validCredentials();
  }

  /**
   * {@inheritdoc}
   */
  public function available() {
    return $this->validCredentials;
  }

  /**
   * {@inheritdoc}
   */
  public function datasetExists($dataset) {
    try {
      $this->getDatasetRows($dataset);
    }

    catch(CartoSyncException $exception) {
      if (preg_match('/^relation \"(.*)" does not exist$/', $exception->getMessage())) {
        return FALSE;
      }
      throw  $exception;
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getDatasetRows($dataset) {
    $query = 'SELECT COUNT(*) FROM ' . $dataset;
    $result = $this->executeGetQuery($query);
    return $result->rows['0']->count;
  }

  /**
   * {@inheritdoc}
   */
  public function getDatasetUrl($dataset) {
    return Url::fromUri('https://' . $this->cartoId . '.carto.com/dataset/' . $dataset);
  }

  /**
   * Tries a fake request to CARTO to validate user credentials.
   *
   * @return bool
   *   TRUE if the credentials are valid, otherwise FALSE.
   */
  protected function validCredentials() {
    try {
      $this->datasetExists(substr(str_shuffle("abcdefghijklmnopqrstuvwxyz"), 0, 12));
      return TRUE;
    }
    catch (CartoSyncException $e) {
      return FALSE;
    }
  }

  /**
   * Builds a CARTO SQL API URL given a SQL query.
   *
   * @param string $query
   *   The SQL query to perform.
   *
   * @return string
   *   The CARTO API URL for the given query.
   */
  protected function buildSqlUrl($query) {
    $options = [
      'query' => [
        'api_key' => $this->cartoApiKey,
        'q' => $query,
      ],
    ];
    return Url::fromUri('https://' . $this->cartoId . '.carto.com/api/v2/sql', $options)
      ->toString();
  }

  /**
   * Builds a CARTO SQL API URL given a SQL query.
   *
   * @return string
   *   The CARTO API import URL.
   */
  protected function buildImportUrl() {
    $options = [
      'query' => [
        'api_key' => $this->cartoApiKey,
      ],
    ];
    return Url::fromUri('https://' . $this->cartoId . '.carto.com/api/v1/imports', $options)
      ->toString();
  }

  /**
   * Performs a GET query against the CARTO SQL API.
   *
   * @param string $query
   *   The SQL query to perform.
   *
   * @return \stdClass
   *   The object returned by CARTO.
   *
   * @throws CartoSyncException
   */
  protected function executeGetQuery($query) {
    $url = $this->buildSqlUrl($query);
    try {
      $data = $this->httpClient->get($url);
      $output = json_decode($data->getBody());
    }
    catch (ConnectException $e) {
      throw new CartoSyncException('Unable to connect to the service');
    }
    catch (RequestException $e) {
      $error = json_decode($e->getResponse()->getBody()->getContents());
      throw new CartoSyncException($error->error[0]);
    }
    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function importDataset($path) {
    try {
      $data = $this->httpClient->request('POST', $this->buildImportUrl(), [
        'multipart' => [
          [
            'name' => 'file',
            'contents' => file_get_contents($path),
            'filename' => basename($path),
          ],
        ],
      ]);
    }
    catch (RequestException $e) {
      watchdog_exception('carto_sync', $e);
      return FALSE;
    }
    $body = json_decode($data->getBody());
    return !empty($body->success);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteDataset($dataset) {

    try {
      $query = 'DELETE FROM ' . $dataset;
      $this->executeGetQuery($query);
      $query = 'DROP TABLE ' . $dataset;
      $this->executeGetQuery($query);
    } catch (CartoSyncException $e) {
      return FALSE;
    }
    return TRUE;
  }

}
