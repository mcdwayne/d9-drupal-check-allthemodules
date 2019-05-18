<?php

namespace Drupal\jsonlog\Logger;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LogMessageParserInterface;
use Drupal\Core\Logger\RfcLoggerTrait;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Psr\Log\LoggerInterface;

/**
 * Redirects logging messages to jsonlog.
 */
class JsonLog implements LoggerInterface {

  use RfcLoggerTrait;

  /**
   * A configuration object containing jsonlog settings.
   *
   * @var \Drupal\Core\Config\Config
   */
  private $config;

  /**
   * The message's placeholders parser.
   *
   * @var \Drupal\Core\Logger\LogMessageParserInterface
   */
  private $parser;

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  private $moduleHandler;

  /**
   * @var RequestStack
   */
  private $requestStack;

  /**
   * @var string
   */
  private $threshold;

  /**
   * @var string
   */
  private $site_id;

  /**
   * @var string
   */
  private $canonical;

  /**
   * @var string
   */
  private $file;

  /**
   * @var string
   */
  private $dir;

  /**
   * @var integer
   */
  private $truncate;

  /**
   * @var string
   */
  private $tags_server;

  /**
   * @var string
   */
  private $tags_site;

  /**
   * JsonLog constructor.
   *
   * @param ConfigFactoryInterface $config_factory
   * @param LogMessageParserInterface $parser
   * @param ModuleHandlerInterface $moduleHandler
   * @param RequestStack $requestStack
   */
  public function __construct(ConfigFactoryInterface $config_factory, LogMessageParserInterface $parser, ModuleHandlerInterface $moduleHandler, RequestStack $requestStack) {
    $this->config = $config_factory->get('jsonlog.settings');
    $this->parser = $parser;
    $this->moduleHandler = $moduleHandler;
    $this->requestStack = $requestStack;
    $this->loadDefaultSettings();
  }

  /**
   * {@inheritdoc}
   *
   * Logs any log statement - at or above a certain severity threshold -
   * to a custom log file as JSON.
   */
  public function log($level, $message, array $context = []) {
    if (!$log_entry = $this->prepareLog($level, $message, $context)) {
      return;
    }

    // File append, using lock (write, doesn't prevent reading).
    // If failure: log filing error to web server's default log.
    if (!file_put_contents($this->file, "\n" . $log_entry->getJson(), FILE_APPEND | LOCK_EX)) {
      error_log('Drupal jsonlog, site ID[' . $this->site_id . '], failed to write to file[' . $this->file . '].');
    }

    unset($log_entry);
  }

  /**
   * Setup the log entry if necessary
   *
   * @param int $level
   * @param string $message
   * @param array $context
   *
   * @return \Drupal\jsonlog\Logger\JsonLogData | bool FALSE
   */
  public function prepareLog($level, $message, array $context = []) {
    // Severity is upside down; less is more. Do not log below configured threshold
    if ($level > $this->threshold || empty($context)) {
      return FALSE;
    }

    // Populate the message placeholders and then replace them in the message.
    $variables = $this->parser->parseMessagePlaceholders($message, $context);

    // Determine HTTP method in case we are dealing with a valid request context
    $method = empty($context['request_uri']) ? '' : $this->requestStack->getCurrentRequest()->getRealMethod();

    // Create the entry.
    $entry = new JsonLogData($this->site_id, $this->canonical);
    $entry->setTags($this->tags_server, $this->tags_site);
    $entry->setMessage($message, $this->truncate, $variables);
    $entry->setSeverity($level);
    $entry->setSubType($context['channel']);
    $entry->setMethod($method);
    $entry->setRequest_uri($context['request_uri']);
    $entry->setReferer($context['referer']);
    $entry->setAccount(isset($context['user']) ? $context['user'] : NULL);
    $entry->setClient_ip($context['ip']);
    $entry->setLink($context['link']);

    return $entry;
  }

  /**
   * Helper function to determine correct log filename
   *
   * @param string $file_time_format
   */
  public function getFileName($file_time_format) {
    return $this->dir . '/' . $this->site_id . ($file_time_format == 'none' ? '' : ('.' . date($file_time_format))) . '.json.log';
  }

  /**
   * Fetch all settings of this module.
   *
   * Always try a server environment var before Drupal configuration var.
   */
  private function loadDefaultSettings() {
    // Threshold
    if (!($this->threshold = getenv('drupal_jsonlog_severity_threshold'))) {
      $this->threshold = $this->config->get('jsonlog_severity_threshold');
    }

    // Site ID
    if (!($this->site_id = getenv('drupal_jsonlog_siteid'))) {
      if (!($this->site_id = $this->config->get('jsonlog_siteid'))) {
        $this->moduleHandler->loadInclude('jsonlog', 'inc');
        $this->site_id = jsonlog_default_site_id();
      }
    }

    // Canonical site identifier
    if (!($this->canonical = getenv('drupal_jsonlog_canonical'))) {
      $this->canonical = $this->config->get('jsonlog_canonical');
    }

    // Dir
    if (!($this->dir = getenv('drupal_jsonlog_dir'))) {
      if (!($this->dir = $this->config->get('jsonlog_dir'))) {
        $this->moduleHandler->loadInclude('jsonlog', 'inc');
        if (!($this->dir = jsonlog_default_dir())) {
          error_log('Drupal jsonlog, site ID[' . $this->dir . '], failed to establish server\'s default log dir.');
        }
      }
    }

    // File
    if (!($file_time = getenv('drupal_jsonlog_file_time'))) {
      $file_time = $this->config->get('jsonlog_file_time');
    }
    $this->file = $this->getFileName($file_time);

    // Truncation
    if (($this->truncate = getenv('drupal_jsonlog_truncate')) === FALSE) {
      $this->truncate = $this->config->get('jsonlog_truncate');
    }

    // Tags
    $this->tags_server = ($tags = getenv('drupal_jsonlog_tags')) !== FALSE ? $tags : '';
    $this->tags_site = $this->config->get('jsonlog_tags');
  }

}
