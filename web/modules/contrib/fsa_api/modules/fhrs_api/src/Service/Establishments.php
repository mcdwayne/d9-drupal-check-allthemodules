<?php

namespace Drupal\fhrs_api\Service;

use Drupal\Core\Logger\LoggerChannelFactory;

/**
 * Class Establishments.
 *
 * @package Drupal\fhrs_api\Service
 */
class Establishments {

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
   * Establishments constructor.
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
   * Get a list of Establishments.
   *
   * Returns a collection of establishment details,
   * based on provided search parameters.
   * All search parameters are optional.
   *
   * @param array $args
   *   Search params.
   *   name
   *   address
   *   longitude
   *   latitude
   *   maxDistanceLimit
   *   businessTypeId
   *   schemeTypeKey
   *   ratingKey
   *   ratingOperatorKey
   *   localAuthorityId
   *   countryId
   *   sortOptionKey
   *   pageNumber
   *   pageSize.
   * @param bool $cacheable
   *   Cacheable.
   *
   * @return array|bool
   *   Response array.
   *   http://api.ratings.food.gov.uk/Help/Api/GET-Establishments_name_address_longitude_latitude_maxDistanceLimit_businessTypeId_schemeTypeKey_ratingKey_ratingOperatorKey_localAuthorityId_countryId_sortOptionKey_pageNumber_pageSize
   */
  public function searchEstablishments(array $args = [], $cacheable = TRUE) {
    if (!isset($args['pageSize'])) {
      $args['pageSize'] = 1;
    }

    $response = $this->client->request(
      '/Establishments',
      $args,
      $cacheable
    );

    if ($response) {
      return $response;
    }

    return FALSE;
  }

  /**
   * Get a list of Establishments.
   *
   * Returns details of all Establishments, results are unbound.
   *
   * @param bool $basic
   *   Basic or Full.
   * @param bool $cacheable
   *   Cacheable.
   *
   * @return array|bool
   *   Response array.
   *   http://api.ratings.food.gov.uk/Help/Api/GET-Establishments-basic
   */
  public function getEstablishments($basic = FALSE, $cacheable = TRUE) {
    $url = '/Establishments';

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
   * Returns details of a single Establishment, selected by Id.
   *
   * @param bool $id
   *   ID.
   * @param bool $cacheable
   *   Cacheable.
   *
   * @return array|bool
   *   Response array.
   *   http://api.ratings.food.gov.uk/Help/Api/GET-Establishments-id
   */
  public function getEstablishment($id, $cacheable = TRUE) {
    $response = $this->client->request(
      '/Establishments/' . $id,
      [],
      $cacheable
    );

    if ($response) {
      return $response;
    }

    return FALSE;
  }

}
