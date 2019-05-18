<?php

namespace Drupal\better_exposed_filters\Plugin;

use Drupal\Component\Plugin\PluginBase;

/**
 * Base class for Better exposed filters sort widget plugins.
 */
abstract class BetterExposedFiltersSortWidgetBase extends PluginBase implements BetterExposedFiltersSortWidgetInterface {

  /**
   * {@inheritdoc}
   */
  public function label() {
    // Return the label defined in the annotation.
    return (string) $this->pluginDefinition['label'];
  }

}
