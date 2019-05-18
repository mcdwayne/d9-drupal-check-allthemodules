<?php

namespace Drupal\fhrs_api\Service;

use Drupal\Core\Logger\LoggerChannelFactory;

/**
 * Class Countries.
 *
 * @package Drupal\fhrs_api\Service
 */
class Countries {

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
   * Countries constructor.
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
   * Get a list of Countries.
   *
   * Returns details of all countries, results are unbound.
   *
   * @param bool $basic
   *   Basic or Full.
   * @param bool $cacheable
   *   Cacheable.
   *
   * @return array|bool
   *   Response array.
   *   http://api.ratings.food.gov.uk/Help/Api/GET-Countries
   *   http://api.ratings.food.gov.uk/Help/Api/GET-Countries-basic
   */
  public function getCountries($basic = FALSE, $cacheable = TRUE) {
    $url = '/Countries';

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
   * Returns details of a single country, selected by Id.
   *
   * @param bool $id
   *   ID.
   * @param bool $cacheable
   *   Cacheable.
   *
   * @return array|bool
   *   Response array.
   *   http://api.ratings.food.gov.uk/Help/Api/GET-Countries-id
   */
  public function getCountry($id, $cacheable = TRUE) {
    $response = $this->client->request(
      '/Countries/' . $id,
      [],
      $cacheable
    );

    if ($response) {
      return $response;
    }

    return FALSE;
  }

}
