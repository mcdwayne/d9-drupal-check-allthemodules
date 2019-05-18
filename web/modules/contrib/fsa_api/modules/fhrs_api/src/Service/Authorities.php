<?php

namespace Drupal\fhrs_api\Service;

use Drupal\Core\Logger\LoggerChannelFactory;

/**
 * Class Authorities.
 *
 * @package Drupal\fhrs_api\Service
 */
class Authorities {

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
   * Authorities constructor.
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
   * Get a list of Authorities.
   *
   * Returns details of all authorities, results are unbound.
   *
   * @param bool $basic
   *   Basic or Full.
   * @param bool $cacheable
   *   Cacheable.
   *
   * @return array|bool
   *   Response array.
   *   http://api.ratings.food.gov.uk/Help/Api/GET-Authorities
   *   http://api.ratings.food.gov.uk/Help/Api/GET-Authorities-basic
   */
  public function getAuthorities($basic = FALSE, $cacheable = TRUE) {
    $url = '/Authorities';

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
   * Returns details of a single authority, selected by Id.
   *
   * @param bool $id
   *   ID.
   * @param bool $cacheable
   *   Cacheable.
   *
   * @return array|bool
   *   Response array.
   *   http://api.ratings.food.gov.uk/Help/Api/GET-Authorities-id
   */
  public function getAuthority($id, $cacheable = TRUE) {
    $response = $this->client->request(
      '/Authorities/' . $id,
      [],
      $cacheable
    );

    if ($response) {
      return $response;
    }

    return FALSE;
  }

}
