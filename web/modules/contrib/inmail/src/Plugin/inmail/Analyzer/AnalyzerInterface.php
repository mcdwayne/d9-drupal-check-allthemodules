<?php

namespace Drupal\inmail\Plugin\inmail\Analyzer;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\inmail\MIME\MimeMessageInterface;
use Drupal\inmail\ProcessorResultInterface;

/**
 * Performs some analysis on a message.
 *
 * @ingroup analyzer
 */
interface AnalyzerInterface extends ConfigurablePluginInterface, PluginFormInterface, PluginInspectionInterface {

  /**
   * Analyze the given message.
   *
   * @param \Drupal\inmail\MIME\MimeMessageInterface $message
   *   A mail message to be analyzed.
   * @param \Drupal\inmail\ProcessorResultInterface $processor_result
   *   The processor result object for logging and reporting results. Contains
   *   the message deliverer.
   */
  public function analyze(MimeMessageInterface $message, ProcessorResultInterface $processor_result);

}
