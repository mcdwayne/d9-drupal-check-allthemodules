<?php

namespace Drupal\log_monitor\Plugin\log_monitor\Scheduler;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\log_monitor\Scheduler\SchedulerPluginInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Base class for Scheduler plugin plugins.
 */
abstract class SchedulerPluginBase extends PluginBase implements SchedulerPluginInterface {
  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return ['plugin_id' => $this->getPluginId()];
  }
}
