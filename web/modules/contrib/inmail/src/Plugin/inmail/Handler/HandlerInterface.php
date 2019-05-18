<?php

namespace Drupal\inmail\Plugin\inmail\Handler;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\inmail\MIME\MimeMessageInterface;
use Drupal\inmail\ProcessorResultInterface;

/**
 * Provides a callback to execute for an analyzed message.
 *
 * @ingroup handler
 */
interface HandlerInterface extends ConfigurablePluginInterface, PluginFormInterface, PluginInspectionInterface {

  /**
   * Returns helpful explanation for using and configuring the handler.
   *
   * @return array
   *   A build array structure with a description of the handler.
   */
  public function help();

  /**
   * Executes callbacks for an analyzed message.
   *
   * @param \Drupal\inmail\MIME\MimeMessageInterface $message
   *   The incoming mail message.
   * @param \Drupal\inmail\ProcessorResultInterface $processor_result
   *   The result and log container for the message, containing the message
   *   deliverer and possibly analyzer results.
   */
  public function invoke(MimeMessageInterface $message, ProcessorResultInterface $processor_result);

}
