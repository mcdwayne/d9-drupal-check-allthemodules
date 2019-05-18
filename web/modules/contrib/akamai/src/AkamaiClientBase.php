<?php

namespace Drupal\akamai;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Plugin\PluginBase;
use Akamai\Open\EdgeGrid\Client as EdgeGridClient;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\MessageFormatter;
use GuzzleHttp\Middleware;
use GuzzleHttp\HandlerStack;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Connects to the Akamai EdgeGrid.
 */
abstract class AkamaiClientBase extends PluginBase implements AkamaiClientInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * An instance of an OPEN EdgeGrid Client.
   *
   * @var \Akamai\Open\EdgeGrid\Client
   */
  protected $client;

  /**
   * A config suitable for use with Akamai\Open\EdgeGrid\Client.
   *
   * @var array
   */
  protected $akamaiClientConfig;

  /**
   * Base url to which API method names are appended.
   *
   * @var string
   */
  protected $apiBaseUrl = '/ccu/v2/';

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * A purge status logger.
   *
   * @var StatusStorage
   */
  protected $statusStorage;

  /**
   * An action to take, either 'remove' or 'invalidate'.
   *
   * @var string
   */
  protected $action = 'remove';

  /**
   * Domain to clear, either 'production' or 'staging'.
   *
   * @var string
   */
  protected $domain = 'production';

  /**
   * Type of purge, either 'arl' or 'cpcode'.
   *
   * @var string
   */
  protected $type = 'arl';

  /**
   * The domain for which Akamai is managing cache.
   *
   * @var string
   */
  protected $baseUrl;

  /**
   * Whether or not to log all requests and responses.
   *
   * @var bool
   */
  protected $logRequests = FALSE;

  /**
   * AkamaiClient constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Akamai\Open\EdgeGrid\Client $client
   *   Akamai EdgeGrid client.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\akamai\StatusStorage $status_storage
   *   A status logger for tracking purge responses.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   A messenger service.
   * @param \Drupal\akamai\KeyProviderInterface $key_provider
   *   A key provider service.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, EdgeGridClient $client, ConfigFactoryInterface $config_factory, LoggerInterface $logger, StatusStorage $status_storage, MessengerInterface $messenger, KeyProviderInterface $key_provider) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->logger = $logger;
    $this->configFactory = $config_factory;
    $this->statusStorage = $status_storage;
    $this->client = $client;

    $this
      // Set action to take based on configuration.
      ->setAction(key(array_filter($this->configFactory->get('akamai.settings')->get("action_{$plugin_id}"))))
      // Set domain (staging or production).
      ->setDomain(key(array_filter($this->configFactory->get('akamai.settings')->get('domain'))))
      // Set base url for the cache (eg, example.com).
      ->setBaseUrl($this->configFactory->get('akamai.settings')->get('basepath'))
      // Sets logging.
      ->setLogRequests($this->configFactory->get('akamai.settings')->get('log_requests'));

    // Create an authentication object so we can sign requests.
    $auth = AkamaiAuthentication::create($config_factory, $messenger, $key_provider);

    $this->akamaiClientConfig = $this->createClientConfig($auth);

    if ($this->logRequests) {
      $this->enableRequestLogging();
    }

    $this->client->__construct($this->akamaiClientConfig, $auth);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('akamai.edgegridclient'),
      $container->get('config.factory'),
      $container->get('logger.channel.akamai'),
      $container->get('akamai.status_storage'),
      $container->get('messenger'),
      $container->get('akamai.key_provider')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {}

  /**
   * Creates a config array for consumption by Akamai\Open\EdgeGrid\Client.
   *
   * @param \Drupal\akamai\AkamaiAuthentication $auth
   *   The auth instance.
   *
   * @return array
   *   The config array.
   *
   * @see Akamai\Open\EdgeGrid\Client::setBasicOptions
   */
  public function createClientConfig(AkamaiAuthentication $auth = NULL) {
    $client_config = [];
    $client_config['base_uri'] = $this->configFactory->get('akamai.settings')->get('rest_api_url');

    if ($auth && $this->configFactory->get('akamai.settings')->get('storage_method') == 'file') {
      $client_config['base_uri'] = $auth->getHost();
    }

    $client_config['timeout'] = $this->configFactory->get('akamai.settings')->get('timeout');

    return $client_config;
  }

  /**
   * Enables logging of all requests and responses.
   *
   * @return $this
   */
  public function enableRequestLogging() {
    $formatter = new MessageFormatter('<pre>' . MessageFormatter::DEBUG . '</pre>');
    $request_logger = Middleware::log($this->logger, $formatter);
    $stack = HandlerStack::create();
    $stack->push($request_logger);
    $this->akamaiClientConfig['handler'] = $stack;

    return $this;
  }

  /**
   * Sets whether or not to log requests and responses.
   *
   * @param bool $log_requests
   *   TRUE to log all requests, FALSE to not.
   *
   * @return $this
   */
  public function setLogRequests($log_requests) {
    $this->logRequests = (bool) $log_requests;
    return $this;
  }

  /**
   * Purges a single URL object.
   *
   * @param string $url
   *   A URL to clear.
   *
   * @return \GuzzleHttp\Psr7\Response
   *   Response to purge request.
   */
  public function purgeUrl($url) {
    return $this->purgeUrls([$url]);
  }

  /**
   * Purges a list of URL objects.
   *
   * @param array $urls
   *   List of URLs to purge.
   *
   * @return \GuzzleHttp\Psr7\Response
   *   Response to purge request.
   */
  public function purgeUrls(array $urls) {
    $urls = $this->normalizeUrls($urls);
    foreach ($urls as $url) {
      if ($this->isAkamaiManagedUrl($url) === FALSE) {
        throw new \InvalidArgumentException("The URL $url is not managed by Akamai. Try setting your Akamai base url.");
      }
    }
    return $this->purgeRequest($urls);
  }

  /**
   * Purges a single cpcode object.
   *
   * @param string $cpcode
   *   A cpcode to clear.
   *
   * @return \GuzzleHttp\Psr7\Response
   *   Response to purge request.
   */
  public function purgeCpCode($cpcode) {
    return $this->purgeCpCodes([$cpcode]);
  }

  /**
   * Purges a list of cpcode objects.
   *
   * @param array $cpcodes
   *   List of cpcodes to purge.
   *
   * @return \GuzzleHttp\Psr7\Response
   *   Response to purge request.
   */
  public function purgeCpCodes(array $cpcodes) {
    return $this->purgeRequest($cpcodes);
  }

  /**
   * Purges a list of tag objects.
   *
   * @param array $tags
   *   List of tags to purge.
   *
   * @return \GuzzleHttp\Psr7\Response
   *   Response to purge request.
   */
  public function purgeTags(array $tags) {
    return $this->purgeRequest($tags);
  }

  /**
   * Create an array to pass to Akamai's purge function.
   *
   * @param string[] $urls
   *   A list of URLs.
   *
   * @return array
   *   An array suitable for sending to the Akamai purge endpoint.
   */
  public function createPurgeBody(array $urls) {
    return [
      'objects' => $urls,
      'action' => $this->action,
      'domain' => $this->domain,
      'type' => $this->type,
    ];
  }

  /**
   * Given a list of URLs, ensure they are fully qualified.
   *
   * @param string[] $urls
   *   A list of URLs.
   *
   * @return string[]
   *   A list of fully qualified URls.
   */
  public function normalizeUrls(array $urls) {
    foreach ($urls as &$url) {
      $url = $this->normalizeUrl($url);
    }
    return $urls;
  }

  /**
   * Given a URL, make sure it is fully qualified.
   *
   * @param string $url
   *   A URL or Drupal path.
   *
   * @return string
   *   A fully qualified URL.
   */
  public function normalizeUrl($url) {
    if (UrlHelper::isExternal($url)) {
      return $url;
    }
    else {
      // Otherwise, try prepending the base URL.
      $url = ltrim($url, '/');
      $domain = rtrim($this->baseUrl, '/');
      return $domain . '/' . $url;
    }
  }

  /**
   * Checks whether a fully qualified URL is handled by Akamai.
   *
   * Note this is based only on local config and doesn't check upstream.
   *
   * @param string $url
   *   The URL to check.
   *
   * @return bool
   *   TRUE if a url with an Akamai managed domain, FALSE if not.
   */
  public function isAkamaiManagedUrl($url) {
    return strpos($url, $this->baseUrl) !== FALSE;
  }

  /**
   * Returns the status of a previous purge request.
   *
   * @param string $purge_id
   *   The UUID of the purge request to check.
   *
   * @return \GuzzleHttp\Psr7\Response|bool
   *   Response to purge status request, or FALSE on failure.
   */
  public function getPurgeStatus($purge_id) {
    try {
      $response = $this->client->request(
        'GET',
        $this->apiBaseUrl . 'purges/' . $purge_id
      );
      return $response;
    }
    catch (RequestException $e) {
      // @todo Better handling
      $this->logger->error($this->formatExceptionMessage($e));
      return FALSE;
    }
  }

  /**
   * Sets the type of purge.
   *
   * @param string $type
   *   The type of purge, either 'arl' or 'cpcode'.
   *
   * @return $this
   */
  public function setType($type) {
    $valid_types = ['cpcode', 'arl'];
    if (in_array($type, $valid_types)) {
      $this->type = $type;
    }
    else {
      throw new \InvalidArgumentException('Type must be one of: ' . implode(', ', $valid_types));
    }
    return $this;
  }

  /**
   * Helper function to set the action for purge request.
   *
   * @param string $action
   *   Action to be taken while purging.
   *
   * @return $this
   */
  public function setAction($action) {
    $valid_actions = $this->validActions();
    if (in_array($action, $valid_actions)) {
      $this->action = $action;
    }
    else {
      throw new \InvalidArgumentException('Action must be one of: ' . implode(', ', $valid_actions));
    }
    return $this;
  }

  /**
   * Sets the domain to clear.
   *
   * @param string $domain
   *   The domain to clear, either 'production' or 'staging'.
   *
   * @return $this
   */
  public function setDomain($domain) {
    $valid_domains = ['staging', 'production'];
    if (in_array($domain, $valid_domains)) {
      $this->domain = $domain;
    }
    else {
      throw new \InvalidArgumentException('Domain must be one of: ' . implode(', ', $valid_domains));
    }
    return $this;
  }

  /**
   * Sets Akamai base url.
   *
   * @param string $url
   *   The base url of the site Akamai is managing, eg 'http://example.com'.
   *
   * @return $this
   */
  public function setBaseUrl($url) {
    $this->baseUrl = $url;
    return $this;
  }

  /**
   * Sets API base url.
   *
   * @param string $url
   *   A url to an API, eg '/ccu/v2/'.
   *
   * @return $this
   */
  public function setApiBaseUrl($url) {
    $this->apiBaseUrl = $url;
    return $this;
  }

  /**
   * Formats a JSON error response into a string.
   *
   * @param \GuzzleHttp\Exception\RequestException $e
   *   The RequestException containing the JSON error response.
   *
   * @return string
   *   The formatted error message as a string.
   */
  protected function formatExceptionMessage(RequestException $e) {
    $message = '';
    // Get the full response to avoid truncation.
    // @see https://laracasts.com/discuss/channels/general-discussion/guzzle-error-message-gets-truncated
    if ($e->hasResponse()) {
      $body = $e->getResponse()->getBody();
      $error_detail = Json::decode($body);
      if (is_array($error_detail)) {
        foreach ($error_detail as $key => $value) {
          $message .= "$key: $value " . PHP_EOL;
        }
      }
    }
    // Fallback to the standard message.
    else {
      $message = $e->getMessage();
    }

    return $message;
  }

  /**
   * {@inheritdoc}
   */
  public function bodyIsBelowLimit(array $paths = []) {
    $body = $this->createPurgeBody($paths);
    $bytes = mb_strlen($body, '8bit');
    return $bytes < self::MAX_BODY_SIZE;
  }

  /**
   * Returns whether the client uses a queue or not.
   *
   * @return bool
   *   TRUE when this client utilises a queue, FALSE when it doesn't
   */
  public function usesQueue() {
    return (bool) $this->usesQueue;
  }

}
