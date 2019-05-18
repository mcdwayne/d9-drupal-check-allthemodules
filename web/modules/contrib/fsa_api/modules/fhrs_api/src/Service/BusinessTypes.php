<?php

namespace Drupal\fhrs_api\Service;

use Drupal\Core\Logger\LoggerChannelFactory;

/**
 * Class BusinessTypes.
 *
 * @package Drupal\fhrs_api\Service
 */
class BusinessTypes {

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
   * BusinessTypes constructor.
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
   * Get a list of BusinessTypes.
   *
   * Returns details of all businessTypes, results are unbound.
   *
   * @param bool $basic
   *   Basic or Full.
   * @param bool $cacheable
   *   Cacheable.
   *
   * @return array|bool
   *   Response array.
   *   http://api.ratings.food.gov.uk/Help/Api/GET-BusinessTypes
   *   http://api.ratings.food.gov.uk/Help/Api/GET-BusinessTypes-basic
   */
  public function getBusinessTypes($basic = FALSE, $cacheable = TRUE) {
    $url = '/BusinessTypes';

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
   * Returns details of a single business type, selected by Id.
   *
   * @param bool $id
   *   ID.
   * @param bool $cacheable
   *   Cacheable.
   *
   * @return array|bool
   *   Response array.
   *   http://api.ratings.food.gov.uk/Help/Api/GET-BusinessTypes-id
   */
  public function getBusinessType($id, $cacheable = TRUE) {
    $response = $this->client->request(
      '/BusinessTypes/' . $id,
      [],
      $cacheable
    );

    if ($response) {
      return $response;
    }

    return FALSE;
  }

}
