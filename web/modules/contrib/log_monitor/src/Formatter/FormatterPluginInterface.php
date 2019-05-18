<?php

namespace Drupal\log_monitor\Formatter;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Defines an interface for Formatter plugin plugins.
 */
interface FormatterPluginInterface extends PluginInspectionInterface {


  /**
   * @param $logs
   *   Array of stdClass objects returned by a database query
   *
   * @return string
   *   Containing markup for the email body
   */
  public function format($logs);

}
