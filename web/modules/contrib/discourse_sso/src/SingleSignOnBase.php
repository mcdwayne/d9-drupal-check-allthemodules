<?php

namespace Drupal\discourse_sso;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use GuzzleHttp\ClientInterface;
use Drupal\Core\Config\ConfigFactory;
use GuzzleHttp\RequestOptions;

/**
 * Discourse single sign on base class.
 */
class SingleSignOnBase {

  use StringTranslationTrait;

  /**
   * GuzzleHttp\Client definition.
   *
   * @var GuzzleHttp\Client
   */
  protected $client;

  /**
   * @var bool
   * 
   * @see GuzzleHttp\RequestOptions::HTTP_ERRORS
   */
  protected $http_errors = FALSE;
  protected $url;
  protected $api_username;
  protected $api_key;

  public function __construct(ClientInterface $http_client, ConfigFactory $config) {
    $this->client = $http_client;

    $this->url = $config->get('discourse_sso.settings')->get('discourse_server');
    $this->api_username = $config->get('discourse_sso.settings')->get('api_username');
    $this->api_key = $config->get('discourse_sso.settings')->get('api_key');
  }

  protected function getDefaultParameter($name = NULL) {
    $parameters = [
      RequestOptions::QUERY => [
        'api_key' => $this->api_key,
        'api_username' => $this->api_username,
      ],
      RequestOptions::HTTP_ERRORS => $this->http_errors,
    ];

    if (isset($name)) {
      return (array_key_exists($name, $parameters)) ? $parameters[$name] : FALSE;
    }

    return $parameters;
  }
}
