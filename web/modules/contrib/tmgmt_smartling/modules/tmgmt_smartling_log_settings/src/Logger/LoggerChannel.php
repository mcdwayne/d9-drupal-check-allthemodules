<?php

namespace Drupal\tmgmt_smartling_log_settings\Logger;

use Drupal\Core\Logger\LoggerChannel as LoggerChannelCore;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Serialization\Yaml;

/**
 * Defines a logger channel that most implementations will use.
 *
 * Same as core's LoggerChannel but with "suppress logging" feature.
 */
class LoggerChannel extends LoggerChannelCore {

  /**
   * @var array|mixed
   */
  private $severity_mapping;

  /**
   * {@inheritdoc}
   */
  public function __construct($channel) {
    $severity_mapping = &drupal_static(get_called_class());

    if (!isset($severity_mapping)) {
      $config = \Drupal::configFactory()
        ->getEditable('tmgmt_smartling_log_settings.settings')
        ->get('severity_mapping');
      $severity_mapping = Yaml::decode($config);
    }

    $this->severity_mapping = $severity_mapping;

    parent::__construct($channel);
  }

  /**
   * {@inheritdoc}
   */
  public function log($level, $message, array $context = []) {
    if ($this->callDepth == self::MAX_CALL_DEPTH) {
      return;
    }
    $this->callDepth++;

    // Merge in defaults.
    $context += [
      'channel' => $this->channel,
      'link' => '',
      'user' => NULL,
      'uid' => 0,
      'request_uri' => '',
      'referer' => '',
      'ip' => '',
      'timestamp' => time(),
    ];
    // Some context values are only available when in a request context.
    if ($this->requestStack && $request = $this->requestStack->getCurrentRequest()) {
      $context['request_uri'] = $request->getUri();
      $context['referer'] = $request->headers->get('Referer', '');
      $context['ip'] = $request->getClientIP();
      try {
        if ($this->currentUser) {
          $context['user'] = $this->currentUser;
          $context['uid'] = $this->currentUser->id();
        }
      }
      catch (\Exception $e) {
        // An exception might be thrown if the database connection is not
        // available or due to another unexpected reason. It is more important
        // to log the error that we already have so any additional exceptions
        // are ignored.
      }
    }

    if (is_string($level)) {
      // Convert to integer equivalent for consistency with RFC 5424.
      $level = $this->levelTranslation[$level];
    }
    // Call all available loggers.
    foreach ($this->sortLoggers() as $logger) {
      $logger_class = get_class($logger);

      // Log records into Smartling anyway. BufferLogger is subscribed ONLY
      // to Smartling related channels (smartling_api, tmgmt_smartling and
      // tmgmt_extension_suit). On/Off logic happens inside BufferLogger::log().
      if ($logger_class == 'Drupal\tmgmt_smartling\Logger\BufferLogger') {
        $logger->log($level, $message, $context);
      }
      else {
        $config_level = !empty($this->severity_mapping[$this->channel]) ? $this->levelTranslation[$this->severity_mapping[$this->channel]] : RfcLogLevel::DEBUG;

        if (!empty($this->severity_mapping[$this->channel])) {
          if ($level <= $config_level) {
            $logger->log($level, $message, $context);
          }
        }
        else {
          $logger->log($level, $message, $context);
        }
      }
    }

    $this->callDepth--;
  }

}
