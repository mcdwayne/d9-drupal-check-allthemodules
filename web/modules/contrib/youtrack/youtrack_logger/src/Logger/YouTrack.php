<?php

/**
 * @file
 * Contains \Drupal\youtrack_logger\Logger\YouTrack.
 */

namespace Drupal\youtrack_logger\Logger;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LogMessageParserInterface;
use Drupal\Core\Logger\RfcLoggerTrait;
use Drupal\youtrack\API\IssueManager;
use Psr\Log\LoggerInterface;

/**
 * Creates YouTrack issues out of logged messages.
 */
class YouTrack implements LoggerInterface {
  use RfcLoggerTrait;

  /**
   * A configuration object containing syslog settings.
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
   * @param \Drupal\Core\Config\ConfigFactoryInterface    $config_factory
   *   The configuration factory object.
   * @param \Drupal\Core\Logger\LogMessageParserInterface $parser
   *   The parser to use when extracting message variables.
   * @param \Drupal\youtrack\API\IssueManager            $issues_manager
   *   YouTrack Issues Manager
   */
  public function __construct(ConfigFactoryInterface $config_factory, LogMessageParserInterface $parser, IssueManager $issues_manager) {
    $this->config = $config_factory->getEditable('youtrack_logger.settings');
    $this->parser = $parser;
    $this->issuesManager = $issues_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function log($level, $message, array $context = array()) {
    // Skip self-logging messages.
    if ($context['channel'] == 'youtrack_logger') {
      return;
    }

    // Check for log message severity to filter unnecessary ones.
    $severities = $this->config->get('severities', array());

    if (!is_array($severities) || !in_array($level, $severities)) {
      return;
    }

    global $base_url;

    // Populate the message placeholders and then replace them in the message.
    $message_placeholders = $this->parser->parseMessagePlaceholders($message, $context);
    $message = empty($message_placeholders) ? $message : strtr($message, $message_placeholders);

    $mapping = array(
      '!base_url' => $base_url,
      '!timestamp' => $context['timestamp'],
      '!type' => $context['channel'],
      '!ip' => $context['ip'],
      '!request_uri' => $context['request_uri'],
      '!referer' => $context['referer'],
      '!uid' => $context['uid'],
      '!link' => strip_tags($context['link']),
      '!message' => strip_tags($message),
    );
    $project = $this->config->get('project');
    $commands = str_replace(array("\r", "\n"), ' ', $this->config->get('commands'));
    $summary = strtr($this->config->get('summary_format'), $mapping);
    $description = strtr($this->config->get('description_format'), $mapping);

    // Create an issue.
    try {
      $this->issuesManager->createIssue($project, $summary, $description, $commands);
    }
    catch (\YouTrack\Exception $e) {
      watchdog_exception('youtrack_logger', $e);
    }
  }

}
