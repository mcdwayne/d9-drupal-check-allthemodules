<?php
/**
 * Drupal-specific implementation of the Akismet PHP client.
 */

namespace Drupal\akismet\Client;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\akismet\Utility\Logger;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Stream;
use Symfony\Component\DependencyInjection\ContainerInterface;


class DrupalClient extends Client implements DrupalClientInterface {

  /**
   * The settings configuration.
   *
   * @var \Drupal\Core\Config\Config
   */
  public $config;

  /**
   * The HTTP client.
   */
  public $client;

  /**
   * Mapping of configuration names to Drupal variables.
   *
   * @var array
   *
   * @see Akismet::loadConfiguration()
   */
  protected $configuration_map = array(
    'key' => 'api_key',
  );

  /**
   * Overrides the connection timeout based on module configuration.
   *
   * Constructor.
   * @param ConfigFactory $config_factory
   * @param ClientInterface $http_client
   *
   * @see Akismet::__construct().
   */
  public function __construct(ConfigFactory $config_factory, ClientInterface $http_client) {
    $this->config = $config_factory->getEditable('akismet.settings');
    $this->requestTimeout = $this->config->get('connection_timeout_seconds');
    $this->client = $http_client;
    parent::__construct();
    $this->requestTimeout = $this->config->get('connection_timeout_seconds');
  }

  /**
   * Factory method for DrupalClient.
   *
   * When Drupal builds this class it does not call the constructor directly.
   * Instead, it relies on this method to build the new object. Why? The class
   * constructor may take multiple arguments that are unknown to Drupal. The
   * create() method always takes one parameter -- the container. The purpose
   * of the create() method is twofold: It provides a standard way for Drupal
   * to construct the object, meanwhile it provides you a place to get needed
   * constructor parameters from the container.
   *
   * In this case, we ask the container for an config.factory factory and a http_client. We then
   * pass the factory and the http client to our class as a constructor parameter.
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('config.factory'), $container->get('http_client'));
  }

  /**
   * Implements Akismet::loadConfiguration().
   */
  public function loadConfiguration($name) {
    $name = $this->configuration_map[$name];
    return $this->config->get($name);
  }

  /**
   * Implements Akismet::saveConfiguration().
   */
  public function saveConfiguration($name, $value) {
    // Save it to the class properties if applicable.
    if (property_exists('\Drupal\akismet\Client\DrupalClient', $name)) {
      $this->{$name} = $value;
    }
    // Persist in Drupal too.
    $name = $this->configuration_map[$name];
    $this->config->set($name, $value)->save();
  }

  /**
   * Implements Akismet::deleteConfiguration().
   */
  public function deleteConfiguration($name) {
    $name = $this->configuration_map[$name];
    $this->config->clear($name)->save();
  }

  /**
   * Implements Akismet::getClientInformation().
   */
  public function getClientInformation() {
    // Retrieve Drupal distribution and installation profile information.
    $profile = drupal_get_profile();
    $profile_info = system_get_info('module', $profile) + array(
      'distribution_name' => 'Drupal',
      'version' => \Drupal::VERSION,
    );

    // Retrieve Akismet module information.
    $akismet_info = system_get_info('module', 'akismet');
    if (empty($akismet_info['version'])) {
      // Manually build a module version string for repository checkouts.
      $akismet_info['version'] = \Drupal::CORE_COMPATIBILITY . '-1.x-dev';
    }

    $data = array(
      'platformName' => $profile_info['distribution_name'],
      'platformVersion' => $profile_info['version'],
      'clientName' => $akismet_info['name'],
      'clientVersion' => $akismet_info['version'],
    );
    return $data;
  }

  /**
   * Overrides Akismet::getSiteURL().
   */
  public function getSiteURL() {
    return $GLOBALS['base_url'];
  }

  /**
   * Overrides Akismet::writeLog().
   */
  function writeLog() {
    foreach ($this->log as $entry) {
      $response = $entry['response'];
      $response = ($response instanceof Stream) ? $response->getContents() : $response;

      $entry['Request: ' . $entry['request']] = !empty($entry['data']) ? $entry['data'] : NULL;
      unset($entry['request'], $entry['data']);

      $entry['Request headers:'] = $entry['headers'];
      unset($entry['headers']);

      $entry['Response: ' . $entry['response_code'] . ' ' . $entry['response_message'] . ' (' . number_format($entry['response_time'], 3) . 's)'] = $response;
      unset($entry['response'], $entry['response_code'], $entry['response_message'], $entry['response_time']);

      // The client class contains the logic for recovering from certain errors,
      // and log messages are only written after that happened. Therefore, we
      // can normalize the severity of all log entries to the overall success or
      // failure of the attempted request.
      // @see Akismet::query()
      Logger::addMessage($entry, $this->lastResponse->isError ? RfcLogLevel::WARNING : NULL);
    }

    // After writing log messages, empty the log.
    $this->purgeLog();
  }

  /**
   * Implements Akismet::request().
   */
  protected function request($method, $server, $path, $query = NULL, array $headers = array()) {
    $options = array(
      'timeout' => $this->requestTimeout,
    );
    if (isset($query)) {
      if ($method === 'GET') {
        $path .= '?' . $query;
      }
      else {
        $options['body'] = $query;
      }
    }
    $request = new Request($method, $server . '/' . $path, $headers);

    try {
      $response = $this->client->send($request, $options);
    }
    catch( \Exception $e ){
      //Logger::addMessage(array('message' => 'Response error: <pre>' . print_r($e, TRUE) . '</pre>'));

      if ($e instanceof ClientException) {
        $akismet_response = array(
          'code' => $e->getCode(),
          'message' => $e->getResponse()->getReasonPhrase(),
          'headers' => $e->getResponse()->getHeaders(),
          'body' => $e->getResponse()->getBody(),
        );
      }
      else {
        Logger::addMessage(array(
            'message' => 'failed to connect. Message @message',
            'arguments' => array('@message' => $e->getMessage())
          ), RfcLogLevel::ERROR);
        return (object) array(
          'code' => '0',
          'message' => $e->getMessage(),
          'headers' => array(),
          'body' => '',
        );
      }
    }

    if (empty($akismet_response)) {
      $akismet_response = array(
        'code' => $response->getStatusCode(),
        'message' => ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) ? $response->getReasonPhrase() : NULL,
        'headers' => $response->getHeaders(),
        'body' => $response->getBody(),
      );
    }
    // Convert headers to expected and consistent format.
    $headers = array();
    foreach ($akismet_response['headers'] as $key => $header) {
      $headers[Unicode::strtolower($key)] = $header[0];
    }
    $akismet_response['headers'] = $headers;
    return (object) $akismet_response;
  }
}
