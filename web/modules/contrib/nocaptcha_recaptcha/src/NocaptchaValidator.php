<?php

/**
 * @file
 * Contains \Drupal\nocaptcha_recaptcha\NocaptchaValidator.
 */

namespace Drupal\nocaptcha_recaptcha;

use Behat\Mink\Exception\Exception;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Logger\LoggerChannelFactory;
use GuzzleHttp\Client;

/**
 * Class NocaptchaValidator.
 *
 * @package Drupal\nocaptcha_recaptcha
 */
class NocaptchaValidator implements NocaptchaValidatorInterface {

  /**
   * Drupal\Core\Config\ConfigFactory definition.
   *
   * @var Drupal\Core\Config\ConfigFactory
   */
  protected $config_factory;

  /**
   * Drupal\Core\Logger\LoggerChannelFactory definition.
   *
   * @var Drupal\Core\Logger\LoggerChannelFactory
   */
  protected $logger_factory;

  /**
   * GuzzleHttp\Client definition.
   *
   * @var GuzzleHttp\Client
   */
  protected $http_client;
  /**
   * Constructor.
   */
  public function __construct(ConfigFactory $config_factory, LoggerChannelFactory $logger_factory, Client $http_client) {
    $this->config_factory = $config_factory;
    $this->logger_factory = $logger_factory;
    $this->http_client = $http_client;
  }


  public function validate($gResponse) {
    $data['response'] = $gResponse;
    $data['secret'] = $this->config_factory->get('nocaptcha_recaptcha.settings')->get('nocaptcha_secret_key');
    try{
      $response = $this->http_client->get('https://www.google.com/recaptcha/api/siteverify', ['query' => $data]);
    }
    catch(Exception $e) {
      \Drupal::logger('nocaptcha_recaptcha')->error('An error occured while validating captcha from google.');
    }

    if (($response->getStatusCode() == 200) && ($response->getReasonPhrase() == "OK")) {
      $body = json_decode($response->getBody()->getContents());
      if ($body['success'] == TRUE) return TRUE;
    }

    return FALSE;
  }
}
