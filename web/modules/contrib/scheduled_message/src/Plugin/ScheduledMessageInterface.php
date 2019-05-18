<?php

namespace Drupal\scheduled_message\Plugin;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Defines an interface for Scheduled message plugins.
 */
interface ScheduledMessageInterface extends PluginInspectionInterface, PluginFormInterface, ConfigurablePluginInterface {

  /**
   * Plugin instance summary.
   *
   * Returns a render array summarizing the configuration of the scheduled
   * message.
   *
   * @return array
   *   A render array.
   */
  public function getSummary();

  /**
   * Get the UUID for this plugin instance.
   *
   * @return string
   *   A UUID.
   */
  public function getUuid();

  /**
   * Returns a label for this scheduled message.
   *
   * @return string
   *   The Scheduled Message label.
   */
  public function label();

}
