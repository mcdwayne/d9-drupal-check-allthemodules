<?php

namespace Drupal\emaillog\Logger;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Logger\LogMessageParserInterface;
use Drupal\Core\Logger\RfcLoggerTrait;
use Psr\Log\LoggerInterface;

class EmailLogger implements LoggerInterface {
  use RfcLoggerTrait;

  /**
   * A configuration object containin syslog settings.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * The message's placeholders parser.
   *
   * @var \Drupal\Core\Logger\LogMessageParserInterface
   */
  protected $parser;

  /**
   * Stores whether there is a system logger connection opened or not.
   *
   * @var bool
   */
  protected $connectionOpened = FALSE;

  /**
   * Constructs a SysLog object.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The configuration factory object.
   * @param \Drupal\Core\Logger\LogMessageParserInterface $parser
   *   The parser to use when extracting message variables.
   */
  public function __construct(ConfigFactory $config_factory, LogMessageParserInterface $parser) {
    $this->config = $config_factory->get('emaillog.settings');
    $this->parser = $parser;
  }

  /**
   * {@inheritdoc}
   */
  public function log($level, $message, array $context = array()) {
    // Do stuff
    $language = \Drupal::languageManager()->getCurrentLanguage()->getId();

    // Send email only if there is an email address.
    // Otherwise the message is ignored by this module.
    if (!$to = $this->config->get('emaillog_' . $level)) {
      return;
    }

    // Assume that current message is not a repetition.
    $message_count = 1;

    // Check if main email repetition restricting options are set.
    // It's enough to check only emaillog_max_similar_emails variable,
    // as setting it requires emaillog_max_similarity_level to be set as well.
    // Saving few unnecessary database queries this way if it's not set.
    $max_similar_emails = $this->config->get('emaillog_max_similar_emails');
    if ($max_similar_emails) {
      $max_similarity_level = $this->config->get('emaillog_max_similarity_level');

      // Get previously sent message data and compare its content with current one.
      $last_message = $this->config->get('emaillog_last_message');
      $max_length = isset($last_message['message']) ? max(strlen($message), strlen($last_message['message'])) : strlen($message);
      $similarity = 0;
      if ($max_length > 0) {
        similar_text($message, $last_message['message'], $similarity);
        $similarity /= 100;
      }

      // If similarity level is higher than allowed in module configuration,
      // and if maximum number of similar messages to sent was reached,
      // stop execution and return - no email should be sent in such case.
      if ($similarity > $max_similarity_level) {
        if ($last_message['count'] >= $max_similar_emails) {
          // Also make sure that those similar emails are consecutive,
          // ie. were sent during a specific period of time (if defined).
          $max_consecutive_timespan = $this->config->get('emaillog_max_consecutive_timespan');
          if (!$max_consecutive_timespan || $last_message['time'] >= time() - $max_consecutive_timespan * 60) {
            // No email should be sent - stop function execution.
            return;
          }
          // Reset last message count if max consecutive time has already passed.
          $last_message['count'] = 0;
        }
        // Email should and will be sent, so increase counter for this message.
        $message_count = ++$last_message['count'];
      }
    }

    // Add additional debug info (PHP predefined variables, debug backtrace etc.)
    $log['debug_info'] = array();
    $debug_info_settings = $this->config->get('emaillog_debug_info');

    foreach (_emaillog_get_debug_info_callbacks() as $debug_info_key => $debug_info_callback) {
      if (isset($debug_info_settings[$level][$debug_info_key]) && $debug_info_settings[$level][$debug_info_key]) {
        eval('$log[\'debug_info\'][\'' . $debug_info_callback . '\'] = ' . $debug_info_callback . ';');
      }
    }
    \Drupal::moduleHandler()->alter('emaillog_debug_info', $log['debug_info']);

    // Make sure that $log['variables'] is always an array to avoid
    // errors like in issue http://drupal.org/node/1325938

    // Send email alert.
    $site_mail = \Drupal::config('system.site')->get('mail_notification');
    // If the custom site notification email has not been set, we use the site
    // default for this.
    if (empty($site_mail)) {
      $site_mail = \Drupal::config('system.site')->get('mail');
    }
    if (empty($site_mail)) {
      $site_mail = ini_get('sendmail_from');
    }

    $params = array(
      'message' => $message,
      'severity' => $level,
      'variables' => $context,
      'debug_info' => $log['debug_info']
    );

    \Drupal::service('plugin.manager.mail')->mail('emaillog', 'alert', $to, $language, $params, $site_mail);
    // Update email repetition restricting variables if needed.
    if ($max_similar_emails) {
      $last_message = array(
        'message' => $message,
        'time'    => time(),
        'count'   => $message_count,
      );
      $this->config->set('emaillog_last_message', $last_message);
    }
  }
}
