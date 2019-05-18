<?php

namespace Drupal\akismet\Client;

use Drupal\Core\Config\ConfigFactory;
use GuzzleHttp\ClientInterface;

/**
 * Drupal Akismet client implementation using local dummy/fake REST server.
 */
class DrupalTestLocalClient extends DrupalTestClient {

  /**
   * The test server location to use overriding any configuration.
   * @var string
   */
  public $server;

  /**
   * Overrides AkismetDrupalTest::__construct().
   */
  public function __construct(ConfigFactory $config_factory, ClientInterface $http_client) {
    // Replace server/endpoint with our local fake server.
    $server = \Drupal::request()->getHttpHost() . '/akismet-test';
    $this->server = $server;
    parent::__construct($config_factory, $http_client);
  }

  /**
   * {@inheritdoc}
   */
  public function loadConfiguration($name) {
    if ($name === 'server') {
      return $this->server;
    }
    return parent::loadConfiguration($name);
  }

  /**
   * {@inheritdoc}
   */
  public function saveConfiguration($name, $value) {
    // Save it to the class properties if applicable.
    if ($name === 'server') {
      $this->server = $value;
    }
    else {
      parent::saveConfiguration($name, $value);
    }
  }

  function getAkismetURL($authenticate) {
    if ($authenticate) {
      return $this->server . '/' . $this->key;
    }
    else {
      return $this->server . '/unauthenticated';
    }
  }

  /**
   * Overrides AkismetDrupal::request().
   *
   * Passes-through SimpleTest assertion HTTP headers from child-child-site and
   * triggers errors to make them appear in parent site (where tests are run).
   *
   * @todo Remove when in core.
   * @see http://drupal.org/node/875342
   */
  protected function request($method, $server, $path, $query = NULL, array $headers = array()) {
    $response = parent::request($method, $server, $path, $query, $headers);
    $keys = preg_grep('@^x-drupal-assertion-@', array_keys($response->headers));
    foreach ($keys as $key) {
      $header = $response->headers[$key];
      $header = unserialize(urldecode($header));
      $message = strtr('%type: @message in %function (line %line of %file).', array(
        '%type' => $header[1],
        '@message' => $header[0],
        '%function' => $header[2]['function'],
        '%line' => $header[2]['line'],
        '%file' => $header[2]['file'],
      ));
      trigger_error($message, E_USER_ERROR);
    }
    // Convert the body from Guzzle stream to string data.
    if (!empty($response->body) && is_callable(array($response->body, 'getContents'))) {
      $response->body = $response->body->getContents();
    }
    return $response;
  }
}
