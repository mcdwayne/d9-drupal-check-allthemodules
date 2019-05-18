<?php

namespace Drupal\recruiterbox;
/**
 * @file
 * Contains \Drupal\recruiterbox\RecruiterBoxApiService.
 */

use GuzzleHttp\Client;
use Drupal\Core\Config\ConfigFactory;
use Psr\Log\LoggerInterface;

/**
 * Class ImportService.
 *
 * @package Drupal\recruiterbox
 */
class RecruiterBoxApiService {

  /**
   * GuzzleHttp\Client definition.
   *
   * @var GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * Drupal\Core\Config\ConfigFactory definition.
   *
   * @var Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * Psr\Log\LoggerInterface definition.
   *
   * @var Psr\Log\LoggerInterface;
   */
  protected $logger;

  /**
   * Constructor.
   */
  public function __construct(Client $httpClient, ConfigFactory $configFactory, LoggerInterface $logger) {
    $this->httpClient = $httpClient;
    $this->configFactory = $configFactory;
    $this->logger = $logger;
  }

  /**
   * Map Drupal form fields value with Recruiter Box Api.
   */
  public function recruiterApply($recruiter_box_data_json) {
    $config = $this->configFactory->get('recruiterbox.recruiterboxsettings');
    $username = $config->get('recruiterbox_api_key');
    $password = '';
    
    $response = $this->httpClient->request('POST', 'https://api.recruiterbox.com/v2/candidates', [
      'Content-type' => 'application/json',
      'Content-Length' => count($recruiter_box_data_json),
      'auth' => [$username, $password, 'Basic'],
      'http_errors' => FALSE,
      'json' => $recruiter_box_data_json,
    ]);
    $this->logger
        ->info('User records save at Recruiter box. Response code: @code, Response Message: @message', [
          '@code' => $response->getStatusCode(),
          '@message' => $response->getReasonPhrase(),
        ]
        );
  }

}
