<?php

namespace Drupal\better_exposed_filters\Plugin;

use Drupal\Component\Plugin\PluginBase;

/**
 * Base class for Better exposed filters pager widget plugins.
 */
abstract class BetterExposedFiltersPagerWidgetBase extends PluginBase implements BetterExposedFiltersPagerWidgetInterface {

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->pluginDefinition['label'];
  }

}
