<?php

namespace Drupal\experian_validation\Services;

use GuzzleHttp\ClientInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Logger\LoggerChannelFactory;

/**
 * Class ExperianValidationService.
 *
 * @package Drupal\experian_validation\Services
 */
class ExperianValidationService {
  /**
   * The state keyvalue collection.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;
  /**
   * Drupal http client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;
  /**
   * Logger Factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactory
   */
  protected $loggerFactory;

  /**
   * Constructs State Service Object.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   State Service Object.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   Guzzle HttpClient Service Object.
   * @param \Drupal\Core\Logger\LoggerChannelFactory $loggerFactory
   *   Logger Service Object.
   */
  public function __construct(StateInterface $state, ClientInterface $http_client, LoggerChannelFactory $loggerFactory) {
    $this->state = $state;
    $this->httpClient = $http_client;
    $this->loggerFactory = $loggerFactory->get('experian_validation');
  }

  /**
   * Return a configured Client object.
   */
  public function validateEmail($emailAddress) {
    $state = $this->state;
    $endPoint = $state->get('expEmailEndPoint');
    $token = $state->get('expEmailToken');
    $data = ["Email" => $emailAddress];

    try {
      $response = $this->httpClient->post($endPoint, [
        'body' => Json::encode($data),
        'headers' => [
          'Auth-Token' => $token,
          'Content-Type' => 'application/json',
        ],
      ]);
      return $response;

    }
    catch (\RequestException $e) {
      $this->loggerFactory->error('There is an error to validate the email address.');
    }

  }

  /**
   * Return a configured Client object.
   */
  public function validatePhone($phoneNumber, $countryCode) {
    $state = $this->state;
    $endPoint = $state->get('expPhoneEndPoint');
    $token = $state->get('expPhoneToken');
    $data = [
      "Number" => $phoneNumber,
      "DefaultCountryCode" => $countryCode,
    ];

    try {
      $response = $this->httpClient->post($endPoint, [
        'body' => Json::encode($data),
        'headers' => [
          'Auth-Token' => $token,
          'Content-Type' => 'application/json',
        ],
      ]);
      return $response;
    }
    catch (\RequestException $e) {
      $this->loggerFactory->error('There is an error to validate the phone number.');
    }
  }

}
