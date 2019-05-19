<?php

namespace Drupal\eloqua_api_auth_fallback\Commands;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\eloqua_api_redux\Service\EloquaApiClient;
use Drush\Commands\DrushCommands;
use Drupal\eloqua_api_redux\Service\EloquaAuthFallbackInterface;

/**
 * A drush command for eloqua api authentication using resource owner grants.
 */
class EloquaAuthTokensGenerate extends DrushCommands implements EloquaAuthFallbackInterface {

  /**
   * Immutable Config.
   *
   * @var \Drupal\Core\Config\Config|\Drupal\Core\Config\ImmutableConfig
   */
  protected $configSettings;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * Eloqua API client service.
   *
   * @var \Drupal\eloqua_api_redux\Service\EloquaApiClient
   */
  protected $eloquaApiClient;

  /**
   * EloquaAuthTokensGenerate constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   An instance of ConfigFactory.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   LoggerChannelFactoryInterface.
   * @param \Drupal\eloqua_api_redux\Service\EloquaApiClient $eloqua_api_client
   *   Eloqua API client service.
   */
  public function __construct(ConfigFactory $config_factory,
                              LoggerChannelFactoryInterface $logger_factory,
                              EloquaApiClient $eloqua_api_client) {
    parent::__construct();
    $this->configSettings = $config_factory->get('eloqua_api_auth_fallback.settings');
    $this->loggerFactory = $logger_factory;
    $this->eloquaApiClient = $eloqua_api_client;
  }

  /**
   * Calls eloqua authentication API service to generate tokens.
   *
   * Access and refresh tokens are generated using resource owner password
   * credentials grant method.
   *
   * @command eloqua_api_auth_fallback:generate-tokens
   * @aliases eloqua-gt
   * @usage eloqua_api_auth_fallback:generate-tokens
   *   Calls eloqua authentication API and generate tokens
   *   using Resource Owner Password Credentials grant.
   *
   * @return bool
   *   TRUE if tokens are generated/renewed from eloqua API.
   */
  public function generateTokensByResourceOwner() {
    $sitename = $this->configSettings->get('sitename');
    $username = $this->configSettings->get('username');
    $password = $this->configSettings->get('password');
    if (empty($sitename) || empty($username) || empty($password)) {
      $message = 'Eloqua authentication credentials are not set.';
      $this->output()
        ->writeln($message);
      $this->loggerFactory->get('eloqua_api_auth_fallback')->error($message);
      return FALSE;
    }

    $params = [
      'grant_type' => 'password',
      'scope' => 'full',
      'username' => $sitename . '\\' . $username,
      'password' => $password,
    ];
    $response = $this->eloquaApiClient->doTokenRequest($params);

    if (!empty($response) && !empty($response['refresh_token'])) {
      $message = 'Eloqua refresh and access tokens updated successfully.';
      $this->output()
        ->writeln($message);
      $this->loggerFactory->get('eloqua_api_auth_fallback')->info($message);
      return TRUE;
    }

    return FALSE;
  }

}
