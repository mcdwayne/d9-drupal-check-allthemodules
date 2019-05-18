<?php

namespace Drupal\pc;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LogMessageParserInterface;
use Drupal\Core\Logger\RfcLoggerTrait;
use Psr\Log\LoggerInterface;

/**
 * Logs events in the PHP Console.
 *
 * Native PHP Console logger is not compatible with RFC 5424.
 */
class Logger implements LoggerInterface {

  use RfcLoggerTrait;

  /**
   * The configuration factory object.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The message's placeholders parser.
   *
   * @var \Drupal\Core\Logger\LogMessageParserInterface
   */
  protected $parser;

  /**
   * Constructs a pc\Logger object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory object to use.
   * @param \Drupal\Core\Logger\LogMessageParserInterface $parser
   *   The parser to use when extracting message variables.
   */
  public function __construct(ConfigFactoryInterface $config_factory, LogMessageParserInterface $parser) {
    $this->configFactory = $config_factory;
    $this->parser = $parser;
  }

  /**
   * {@inheritdoc}
   */
  public function log($level, $message, array $context = []) {
    if ($this->configFactory->get('pc.settings')->get('debug_info.watchdog')) {
      $args = $this->parser->parseMessagePlaceholders($message, $context);
      pc(strip_tags(html_entity_decode(new FormattableMarkup($message, $args))), 'Log');
    }
  }

}
