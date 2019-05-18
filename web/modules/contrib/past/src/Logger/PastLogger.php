<?php

namespace Drupal\past\Logger;

use Psr\Log\AbstractLogger;
use Drupal\Core\Logger\LogMessageParserInterface;

/**
 * A logger service for Past that creates events upon standard log messages.
 */
class PastLogger extends AbstractLogger {

  /**
   * Set to TRUE when creating a log event, and to FALSE when done.
   *
   * No log event is created if this is already TRUE, in order to avoid infinite
   * recursion should the creation of a log event cause another log event.
   *
   * @var bool
   */
  protected static $isCreatingEvent = FALSE;

  /**
   * The message's placeholders parser.
   *
   * @var LogMessageParserInterface
   */
  protected $parser;

  /**
   * Constructs a Past Logger object.
   *
   * @param LogMessageParserInterface $parser
   *   The parser to use when extracting message variables.
   */
  public function __construct(LogMessageParserInterface $parser) {
    $this->parser = $parser;
  }

  /**
   * {@inheritdoc}
   */
  public function log($level, $message, array $context = []) {
    if (!function_exists('past_event_create')) {
      return;
    }

    // Prevent logging recursion.
    if (self::$isCreatingEvent) {
      return;
    }
    self::$isCreatingEvent = TRUE;

    unset($context['backtrace']);
    $variables = $this->parser->parseMessagePlaceholders($message, $context);

    // @todo Inject config
    if (\Drupal::config('past.settings')->get('log_watchdog')
      && empty($variables['past_already_logged'])) {
      if (is_array($variables)) {
        // Format watchdog message with array variables as placeholders.
        $message = format_string($message, $variables);
      }
      // The past message is expected to be in plain text, remove HTML tags and
      // decode entities.
      $event = past_event_create('watchdog', $context['channel'], html_entity_decode(strip_tags($message), ENT_QUOTES));

      $event->setSeverity($level);
      $event->setTimestamp($context['timestamp']);

      if ($context['channel'] == 'php') {
        // We handle an error report.
        $argument = _past_error_array_to_event($variables);
        if (!empty($argument)) {
          $event->addArgument('php', $argument);
        }
      }

      if ($backtrace = _past_get_formatted_backtrace($level)) {
        $event->addArgument('backtrace', $backtrace);
      }

      if (!empty($context['link'])) {
        $event->addArgument('link', $context['link']);
      }
      if (!empty($context['uid'])) {
        $event->setUid($context['uid']);
      }
      if (!empty($context['request_uri'])) {
        $event->setLocation($context['request_uri']);
      }
      if (!empty($context['referer'])) {
        $event->setReferer($context['referer']);
      }

      $watchdog_args = [];
      if (!empty($context['ip'])) {
        $watchdog_args['hostname'] = substr($context['ip'], 0, 128);
      }
      if ($watchdog_args) {
        $event->addArgument('watchdog_args', $watchdog_args);
      }

      $event->save();
    }
    self::$isCreatingEvent = FALSE;
  }
}
