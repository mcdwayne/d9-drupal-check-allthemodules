<?php

namespace Drupal\fhrs_api\Service;

use Drupal\Core\Logger\LoggerChannelFactory;

/**
 * Class ScoreDescriptors.
 *
 * @package Drupal\fhrs_api\Service
 */
class ScoreDescriptors {

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
   * ScoreDescriptors constructor.
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
   * Returns details of all scoreDescriptor, results are unbound.
   *
   * @param bool $establishmentId
   *   Establishment Id.
   * @param bool $cacheable
   *   Cacheable.
   *
   * @return array|bool
   *   Response array.
   *   http://api.ratings.food.gov.uk/Help/Api/GET-ScoreDescriptors_establishmentId
   */
  public function getScoreDescriptors($establishmentId, $cacheable = TRUE) {
    $response = $this->client->request(
      '/ScoreDescriptors/',
      ['establishmentId' => $establishmentId],
      $cacheable
    );

    if ($response) {
      return $response;
    }

    return FALSE;
  }

}
