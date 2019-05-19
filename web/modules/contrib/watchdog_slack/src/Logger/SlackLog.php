<?php

/**
 * @file
 * Contains \Drupal\watchdog_slack\Logger\SlackLog.
 */

namespace Drupal\watchdog_slack\Logger;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Logger\LogMessageParserInterface;
use Drupal\Core\Logger\RfcLoggerTrait;
use Psr\Log\LoggerInterface;

/**
 * Logs events in the watchdog database table.
 */
class SlackLog implements LoggerInterface {
  use RfcLoggerTrait;
  use DependencySerializationTrait;

  /**
   * The message's placeholders parser.
   *
   * @var \Drupal\Core\Logger\LogMessageParserInterface
   */
  protected $parser;

  /**
   * Constructs a SlackLog object.
   *
   * @param \Drupal\Core\Logger\LogMessageParserInterface $parser
   *   The parser to use when extracting message variables.
   */
  public function __construct(LogMessageParserInterface $parser) {
    $this->parser = $parser;
  }

  /**
   * {@inheritdoc}
   */
  public function log($level, $message, array $context = array()) {

    /*
     * Channel "watchdog_slack" means that some error happened here, so we
     * should skip the process as it would become recursive
     */
    if ($context['channel'] != 'watchdog_slack') {

      $config = \Drupal::config('watchdog_slack.settings');

      $channel = $config->get('channel');
      if (!isset($channel) || empty($channel)) {
        \Drupal::logger('watchdog_slack')
          ->error(t("Watchdog to Slack settings is not complete. Please configure the module.")
          );
        return FALSE;
      }

      $username = $config->get('username') ? $config->get('username') : \Drupal::translation()
        ->translate('Drupal Watchdog');

      $severity_levels_to_log = $config->get('severity_levels_to_log');

      if (!isset($severity_levels_to_log)
        || in_array($level, $severity_levels_to_log)
      ) {
        global $base_url;

        $message_placeholders = $this->parser->parseMessagePlaceholders($message, $context);

        $watchdog_message = (string) \Drupal::translation()->translate(
          $message, $message_placeholders
        );
        $watchdog_message = strip_tags($watchdog_message);
        $slack_message = 'Channel: ' . $context['channel'] . "\n";
        $slack_message .= 'Error: ' . $watchdog_message . "\n";
        $slack_message .= 'Uid: ' . $context['uid'] . "\n";
        $slack_message .= 'Location: ' . $context['request_uri'] . "\n";
        $slack_message .= 'Referer: ' . $context['referer'] . "\n";
        $slack_message .= 'Hostname: ' . Unicode::substr($context['ip'], 0, 128) . "\n";

        $result = \Drupal::service('slack.slack_service')
          ->sendMessage($slack_message, $channel, $username);

        if (!$result) {
          \Drupal::logger('watchdog_slack')
            ->error(t("Watchdog wasn't sent to Slack. Please, check Slack module configuration.")
            );
        }
      }
    }
  }
}