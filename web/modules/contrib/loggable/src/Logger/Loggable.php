<?php

namespace Drupal\loggable\Logger;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Promise;
use Drupal\Core\Path\PathMatcherInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Drupal\Component\Utility\Unicode;
use Drupal\user\Entity\User;
use Drupal\Core\Logger\RfcLoggerTrait;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Logs events to Loggable.
 */
class Loggable implements LoggerInterface {

  use RfcLoggerTrait;

  /**
   * The request timeout length, in seconds.
   *
   * @var int
   */
  const TIMEOUT = 3;

  /**
   * The message max length.
   *
   * @var int
   */
  const MESSAGE_MAXLENGTH = 5000;

  /**
   * The generic field max length.
   *
   * @var int
   */
  const FIELD_MAXLENGTH = 64;

  /**
   * The amount of async requests to make at a time.
   *
   * @var int
   */
  const ASYNC_MAX_REQUESTS = 5;

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The path matcher.
   *
   * @var \Drupal\Core\Path\PathMatcherInterface
   */
  protected $pathMatcher;

  /**
   * The modue configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new Loggable object.
   *
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The HTTP client.
   * @param \Drupal\Core\Path\PathMatcherInterface $path_matcher
   *   The path matcher.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(ClientInterface $http_client, PathMatcherInterface $path_matcher, ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager) {
    $this->httpClient = $http_client;
    $this->pathMatcher = $path_matcher;
    $this->config = $config_factory->get('loggable.settings');
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function log($level, $message, array $context = []) {
    // Stop if the module is not configured.
    if (!$this->isConfigured()) {
      return;
    }

    // Skip this event if it does not match a filter.
    if (!$this->matchFilter($level, $context['channel'])) {
      return;
    }

    // Check if an acting user.
    if (!empty($context['uid']) && is_numeric($context['uid'])) {
      // Load the account.
      $account = User::load($context['uid']);
    }
    // Check for the current user.
    elseif (!empty($context['user'])) {
      $account = $context['user'];
    }

    // Get the account name.
    if (!empty($account)) {
      $account_name = $account->getAccountName() ? $account->getAccountName() : $account->getDisplayName();
    }

    // Map possible severity levels.
    $levels = [
      RfcLogLevel::DEBUG => LogLevel::DEBUG,
      RfcLogLevel::INFO => LogLevel::INFO,
      RfcLogLevel::NOTICE => LogLevel::NOTICE,
      RfcLogLevel::WARNING => LogLevel::WARNING,
      RfcLogLevel::ERROR => LogLevel::ERROR,
      RfcLogLevel::CRITICAL => LogLevel::CRITICAL,
      RfcLogLevel::ALERT => LogLevel::ALERT,
      RfcLogLevel::EMERGENCY => LogLevel::EMERGENCY,
    ];

    // Generate the message placeholders.
    $placeholders = [];
    foreach ($context as $key => $value) {
      // Ignore arrays and objects.
      if (is_array($value) || is_object($value)) {
        continue;
      }

      // Ignore non-placeholders.
      if (!in_array(substr($key, 0, 1), ['@', ':', '%'])) {
        continue;
      }

      // Store the placeholder.
      $placeholders[$key] = $value;
    }

    // Build the event payload.
    $data = [
      'type' => Unicode::substr($context['channel'], 0, self::FIELD_MAXLENGTH),
      'severity' => $levels[$level],
      'user' => !empty($account_name) ? $account_name : NULL,
      // TODO: Use link somehow?
      'url' => $context['request_uri'],
      'message' => Unicode::substr(strtr($message, $placeholders), 0, self::MESSAGE_MAXLENGTH),
      'created' => $context['timestamp'],
    ];

    // Queue the request.
    $this->queueRequest($data);
  }

  /**
   * Determine if the module is configured.
   *
   * @return bool
   *   TRUE if the module has sufficient configuration to send events to
   *   Loggable, otherwise FALSE.
   */
  public function isConfigured() {
    if (!$this->config->get('api_key') || !$this->config->get('channel_id')) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Get all enabled loggable filter configuration entities.
   *
   * @return array
   *   An array of enabled Loggable filter entities.
   */
  public function getFilters() {
    $filters = &drupal_static(__METHOD__, NULL);

    // Check if the filters haven't been loaded yet.
    if ($filters === NULL) {
      // Load filter storage.
      $storage = $this->entityTypeManager
        ->getStorage('loggable_filter');

      // Query to find all enabled filters.
      $ids = $storage
        ->getQuery()
        ->condition('enabled', 1)
        ->execute();

      // Load the filters.
      $filters = $ids ? $storage->loadMultiple($ids) : [];
    }

    return $filters;
  }

  /**
   * Determine if the event is a match for a filter.
   *
   * @param int $level
   *   The event severity level.
   * @param string $type
   *   The event type.
   *
   * @return bool
   *   TRUE if the event matches an enable filter, otherwise FALSE.
   */
  public function matchFilter($level, string $type) {
    // Load and iterate enabled filters.
    foreach ($this->getFilters() as $filter) {
      // Check if the level is a match.
      if (in_array((int) $level, $filter->getSeverityLevels())) {
        // Check if there is a type restriction.
        if ($filter->getTypes()) {
          // Check if the type is a match.
          if ($this->pathMatcher->matchPath($type, implode("\n", $filter->getTypes()))) {
            return TRUE;
          }

          // Skip to the next filter.
          continue;
        }

        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Queue a request to Loggable.
   *
   * @param array $data
   *   An array of event data.
   */
  public function queueRequest(array $data) {
    $requests = &drupal_static(__METHOD__, []);

    // Register a shutdown function if this is the first request to be queued.
    if (empty($requests)) {
      drupal_register_shutdown_function([$this, 'executeQueuedRequests']);
    }

    // Build the request options.
    $options = [
      'headers' => [
        'Accept' => 'application/vnd.api+json',
        'Content-Type' => 'application/vnd.api+json',
        'api-key' => $this->config->get('api_key'),
      ],
      'timeout' => self::TIMEOUT,
      'json' => [
        'data' => [
          'type' => 'event',
          'attributes' => $data,
          'relationships' => [
            'channel' => [
              'data' => [
                'type' => 'channel',
                'id' => $this->config->get('channel_id'),
              ],
            ],
          ],
        ],
      ],
    ];

    // Build the uri.
    $uri = $this->config->get('domain') . '/api/event';

    // Create and store the request.
    $requests[] = $this
      ->httpClient
      ->requestAsync('POST', $uri, $options);
  }

  /**
   * Asynchronously execute all queued HTTP requests.
   *
   * @see queueRequest()
   */
  public static function executeQueuedRequests() {
    $requests = &drupal_static('Drupal\loggable\Logger\Loggable::queueRequest', []);
    $requests = array_chunk(array_reverse($requests), self::ASYNC_MAX_REQUESTS);

    foreach ($requests as $batch) {
      Promise\settle($batch)->wait();
    }
  }

}
