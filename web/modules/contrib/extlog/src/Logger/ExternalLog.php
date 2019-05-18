<?php

namespace Drupal\extlog\Logger;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Logger\LogMessageParserInterface;
use Drupal\Core\Logger\RfcLoggerTrait;
use Psr\Log\LoggerInterface;

/**
 * Logs events in a remote log server.
 */
class ExternalLog implements LoggerInterface {

  use RfcLoggerTrait;
  use DependencySerializationTrait;

  /**
   * A configuration object containing extlog settings.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * The message's place holders parser.
   *
   * @var \Drupal\Core\Logger\LogMessageParserInterface
   */
  protected $parser;

  /**
   * The current environment type
   *
   * @var string
   */
  protected $environment;

  /**
   * Constructs a SysLog object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory object.
   * @param \Drupal\Core\Logger\LogMessageParserInterface $parser
   *   The parser to use when extracting message variables.
   */
  public function __construct(ConfigFactoryInterface $config_factory, LogMessageParserInterface $parser) {
    $this->config = $config_factory->get('extlog.settings');
    $this->parser = $parser;
    $this->environment = isset($_ENV['AH_SITE_ENVIRONMENT']) ? $_ENV['AH_SITE_ENVIRONMENT'] : 'local';
  }

  /**
   * {@inheritdoc}
   */
  public function log($level, $message, array $context = []) {
    try {
      if ($this->config->get('active') !== true) {
        if (\Drupal::currentUser()->hasPermission('administer extlog')) {
          drupal_set_message(t('The module extlog is installed but is disabled at <a href="/admin/config/development/logging">configuration</a>.'), 'warning');
        }
        return;
      }
      if (strlen($this->config->get('env.' . $this->environment . '.srv_address')) < 8) {
        if (\Drupal::currentUser()->hasPermission('administer extlog')) {
          drupal_set_message(t('The module extlog is enabled but no server address is active. Please disable the module or define a valid server address.'), 'warning');
        }
        return;
      }
      if (!$this->parse_log_match($context['channel'], $message, $level)) {
        return;
      }
      $this->send_message($level, $message, $context);
    } catch (\Exception $e) {
      return;
    }
  }

  /**
   * Internal function to validate a specific log_entry against configured logger.
   *
   * @param string $event
   *  the type of event
   *
   * @param string $message
   *  the message to log
   *
   * @param int $severity
   *  level of the log
   *
   * @return boolean
   */
  private function parse_log_match($event, $message, $severity) {
    $severities = array(
        'emergency',
        'alert',
        'critical',
        'error',
        'warning',
        'notice',
        'info',
        'debug',
    );
    for ($i = 0; $i < $this->config->get('n_rules'); $i++) {
      if (
              $event === $this->config->get('conf.' . $i . '.event') &&
              @preg_match('/' . $this->config->get('conf.' . $i . '.regex') . '/', $message) &&
              $this->config->get('conf.' . $i . '.' . $severities[$severity])
      ) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Internal function for parsing a watchdog $log_entry in logger format.
   *
   * @param int $level
   *  level of the log
   *
   * @param string $message
   *  the message to log
   *
   * @param array $context
   *  the context in which the event occurred
   *
   * @return array
   *   Parsed message in logger format
   */
  private function parse_log_entry($level, $message, $context) {
    // Populate the message placeholders and then replace them in the message.
    $message_placeholders = $this->parser->parseMessagePlaceholders($message, $context);
    $event_message = empty($message_placeholders) ? $message : strtr($message, $message_placeholders);
    $account_uid = $context['user']->getAccount()->id();
    $account_name = $account_uid ? $context['user']->getAccount()->getAccountName() : 'ANONYMOUS';
    return array(
        'dateTime' => date('Ymd\THis+0100', $context['timestamp']),
        'eventResult' => $level < 4 ? 'NOK' : 'OK',
        'eventType' => $level < 4 ? 'ERROR' : 'INFO',
        'message' => $event_message,
        'operation' => $context['channel'],
        'session' => session_id() . '|' . $account_uid . '|' . $account_name,
    );
  }

  /**
   * Send message to the logger server
   *
   * @param int $level
   *  level of the log
   *
   * @param string $message
   *  the message to log
   *
   * @param array $context
   *  the context in which the event occurred
   *
   */
  private function send_message($level, $message, $context) {
    $curl_post_data = http_build_query($this->parse_log_entry($level, $message, $context));
    $header = [
        $this->config->get('env.' . $this->environment . '.srv_header') . ': ' . $this->config->get('env.' . $this->environment . '.srv_value')
    ];

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $this->config->get('env.' . $this->environment . '.srv_address'));
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $curl_post_data);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
    curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    $curl_response = curl_exec($curl);
    curl_close($curl);
  }

}
