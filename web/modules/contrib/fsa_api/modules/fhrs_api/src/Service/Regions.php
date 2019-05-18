<?php

namespace Drupal\fhrs_api\Service;

use Drupal\Core\Logger\LoggerChannelFactory;

/**
 * Class Regions.
 *
 * @package Drupal\fhrs_api\Service
 */
class Regions {

  /**
   * Client.
   *
   * @var \Drupal\fhrs_api\Service\Client
   */
  protected $client;

  /**
   * Logger Factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactory
   */
  protected $loggerFactory;

  /**
   * Regions constructor.
   *
   * @param \Drupal\fhrs_api\Service\Client $client
   *   Client.
   * @param \Drupal\Core\Logger\LoggerChannelFactory $loggerFactory
   *   LoggerChannelFactory.
   */
  public function __construct(Client $client,
                              LoggerChannelFactory $loggerFactory) {
    // FHRS API Client.
    $this->client = $client;
    $this->loggerFactory = $loggerFactory;
  }

  /**
   * Get a list of Regions.
   *
   * Returns details of all regions, results are unbound.
   *
   * @param bool $basic
   *   Basic or Full.
   * @param bool $cacheable
   *   Cacheable.
   *
   * @return array|bool
   *   Response array.
   *   http://api.ratings.food.gov.uk/Help/Api/GET-Regions
   *   http://api.ratings.food.gov.uk/Help/Api/GET-Regions-basic
   */
  public function getRegions($basic = FALSE, $cacheable = TRUE) {
    $url = '/Regions';

    if ($basic == TRUE) {
      $url = $url . '/basic';
    }

    $response = $this->client->request(
      $url,
      [],
      $cacheable
    );

    if ($response) {
      return $response;
    }

    return FALSE;
  }

  /**
   * Returns details of a single region, selected by Id.
   *
   * @param bool $id
   *   ID.
   * @param bool $cacheable
   *   Cacheable.
   *
   * @return array|bool
   *   Response array.
   *   http://api.ratings.food.gov.uk/Help/Api/GET-Regions-id
   */
  public function getRegion($id, $cacheable = TRUE) {
    $response = $this->client->request(
      '/Regions/' . $id,
      [],
      $cacheable
    );

    if ($response) {
      return $response;
    }

    return FALSE;
  }

}
