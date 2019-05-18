<?php

namespace Drupal\byteplant_email_validation;

use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\Client;

define('BYTEPLANT_OK_VALID_ADDRESS', 200);
define('BYTEPLANT_OK_CATCH_All_ACTIVE', 207);
define('BYTEPLANT_OK_CATCH_All_TEST_DELAYED', 215);

/**
 * Class EmailValidationService.
 *
 * @package Drupal\byteplant_email_validation
 */
class EmailValidationService {

  /**
   * The byteplant_email_validation.byteplant_settings config object.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * GuzzleHttp\Client definition.
   *
   * @var GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * Constructs an EmailValidation object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param GuzzleHttp\Client $http_client
   *   The guzzle http client.
   *
   */
  public function __construct(ConfigFactoryInterface $config_factory, Client $httpClient) {
    $this->config = $config_factory->get('byteplant_email_validation.byteplant_settings');
    $this->httpClient = $httpClient;
  }

  /**
   * Get request using guzzle client.
   *
   * @return mixed|string
   */
  public function verifyEmail($email) {
    // Get Byteplant Key and url by form.
    $key = $this->config->get('key');
    $url = $this->config->get('url');
    $query = 'EmailAddress=' . $email . '&APIKey=' . $key;
    $url = $url . '?' . $query;
    return $this->getRequest($url);
  }

  /**
   * Get Invalidate email message.
   *
   * @return mixed|string
   */
  public function getMessage($content) {
    // If user set message then use it, else use byteplant status.
    if ($message = $this->config->get('message')) {
      return $message;
    }
    return $content->info . ': ' . $content->details;
  }

  /**
   * Get Email validation details from BytePlant service.
   *
   * @param $url
   * @param null $options
   *
   * @return mixed|string
   */
  public function getRequest($url, $options = NULL) {
    $client = $this->httpClient;
    $request = $client->get($url, $options);
    $content = $request->getBody()->getContents();
    $content = json_decode($content);
    return $content;
  }

}
