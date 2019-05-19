<?php

namespace Drupal\tmgmt_smartling\Logger;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LogMessageParserInterface;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\tmgmt_smartling\Smartling\ConfigManager\SmartlingConfigManager;
use Drupal\tmgmt_smartling\Smartling\ConnectorInfo;
use Exception;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use Psr\Log\LogLevel;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class BufferLogger
 *
 * @package Drupal\tmgmt_smartling\Logger
 */
class BufferLogger implements LoggerInterface {

  use LoggerTrait;

  /**
   * Map of RFC 5424 log constants to PSR3 log constants.
   *
   * @var array
   */
  protected $levelTranslation = [
    RfcLogLevel::EMERGENCY => LogLevel::EMERGENCY,
    RfcLogLevel::ALERT => LogLevel::ALERT,
    RfcLogLevel::CRITICAL => LogLevel::CRITICAL,
    RfcLogLevel::ERROR => LogLevel::ERROR,
    RfcLogLevel::WARNING => LogLevel::WARNING,
    RfcLogLevel::NOTICE => LogLevel::NOTICE,
    RfcLogLevel::INFO => LogLevel::INFO,
    RfcLogLevel::DEBUG => LogLevel::DEBUG,
  ];

  /**
   * @var array
   */
  private $buffer;

  /**
   * @var int
   */
  private $bufferLimit;

  /**
   * @var array
   */
  private $channels;

  /**
   * @var int
   */
  private $timeOut;

  /**
   * @var LogMessageParserInterface
   */
  private $parser;

  /**
   * @var ConfigFactoryInterface
   */
  private $configFactory;

  /**
   * @var array
   */
  private $providerSettings = [];

  /**
   * @var RequestStack
   */
  private $requestStack;

  /**
   * @var int
   */
  private $logLevel;

  /**
   * @var string
   */
  private $host;

  /**
   * @var Client
   */
  private $httpClient;

  /**
   * @var string
   */
  private $uid;

  /**
   * @var SmartlingConfigManager
   */
  private $configManager;

  /**
   * BufferLogger constructor.
   *
   * @param Client $http_client
   * @param LogMessageParserInterface $parser
   * @param ConfigFactoryInterface $config_factory
   * @param RequestStack $request_stack
   * @param array $channels
   * @param int $log_level
   * @param string $host
   * @param int $buffer_limit
   * @param int $time_out
   */
  public function __construct(
    Client $http_client,
    LogMessageParserInterface $parser,
    ConfigFactoryInterface $config_factory,
    RequestStack $request_stack,
    $channels = ['tmgmt_smartling', 'tmgmt_smartling_context_debug', 'tmgmt_extension_suit', 'smartling_api'],
    $log_level = RfcLogLevel::DEBUG,
    $host = 'https://api.smartling.com/updates/status',
    $buffer_limit = 100,
    $time_out = 5
  ) {
    $this->buffer = [];
    $this->httpClient = $http_client;
    $this->channels = $channels;
    $this->parser = $parser;
    $this->configFactory = $config_factory;
    $this->requestStack = $request_stack;
    $this->logLevel = $log_level;
    $this->host = $host;
    $this->bufferLimit = $buffer_limit;
    $this->timeOut = $time_out;
    $this->uid = uniqid();

    drupal_register_shutdown_function([$this, 'flush']);
  }

  /**
   * Setter for config manager.
   *
   * @param \Drupal\tmgmt_smartling\Smartling\ConfigManager\SmartlingConfigManager $config_manager
   */
  public function setConfigManager(SmartlingConfigManager $config_manager) {
    $this->configManager = $config_manager;

    // Pick up first available Smartling provider settings.
    $smartling_provider = $this->configManager->getAvailableConfigs();

    if (!empty($smartling_provider)) {
      $this->providerSettings = $smartling_provider[0]->get();
    }
  }
  /**
   * Logs with an arbitrary level.
   *
   * @param mixed $level
   * @param string $message
   * @param array $context
   *
   * @return void
   */
  public function log($level, $message, array $context = []) {
    // Log only records from needed channels.
    if (empty($this->channels) ||
      (in_array($context['channel'], $this->channels))
    ) {
      // Key "enable_smartling_logging" might be absent in provider settings
      // after module update. Consider this situation as "checked" by default.
      $logging_key_is_not_set = !isset($this->providerSettings['settings']['enable_smartling_logging']);
      $enabled_logging = ($logging_key_is_not_set || $this->providerSettings['settings']['enable_smartling_logging'] === TRUE);

      // Buffer log records with $this->log_level or lover.
      if (
        $level <= $this->logLevel &&
        !empty($this->providerSettings['settings']['project_id']) &&
        $enabled_logging
      ) {
        $this->buffer[] = [
          'level' => $level,
          'message' => $message,
          'context' => $context,
        ];

        // Flush buffer on overflow.
        if (count($this->buffer) == $this->bufferLimit) {
          $this->flush();
        }
      }
    }
  }

  /**
   * Log messages into needed destination.
   */
  public function flush() {
    if (empty($this->buffer)) {
      return;
    }

    try {
      $records = [];
      $project_id = $this->providerSettings['settings']['project_id'];
      $host = php_uname('n');
      $http_host = $this->requestStack->getCurrentRequest()->getHost();
      $tmgmt_smartling_version = ConnectorInfo::getLibVersion();
      $dependencies = ConnectorInfo::getDependenciesVersionsAsString();

      // Assemble records.
      foreach ($this->buffer as $drupal_log_record) {
        $message_placeholders = $this->parser->parseMessagePlaceholders($drupal_log_record['message'], $drupal_log_record['context']);
        $records[] = [
          'level_name' => $this->levelTranslation[$drupal_log_record['level']],
          'channel' => 'drupal-tmgmt-connector',
          'datetime' => date('Y-m-d H:i:s', $drupal_log_record['context']['timestamp']),
          'context' => [
            'projectId' => $project_id,
            'host' => $host,
            'http_host' => $http_host,
            'moduleVersion' => $tmgmt_smartling_version,
            'dependencies' => $dependencies,
            'remoteChannel' => $drupal_log_record['context']['channel'],
            'requestId' => $this->uid,
          ],
          'message' => strtr($drupal_log_record['message'], $message_placeholders),
        ];
      }

      $this->httpClient->request('POST', $this->host, [
        'json' => [
          'records' => $records,
        ],
        'timeout' => $this->timeOut,
        'headers' => [
          'User-Agent' => ConnectorInfo::getLibName() . '/' . ConnectorInfo::getLibVersion(),
        ],
      ]);
    }
    catch (Exception $e) {
    }

    $this->buffer = [];
  }

}
