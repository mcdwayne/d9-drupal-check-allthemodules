<?php

namespace Drupal\better_exposed_filters\Plugin;

use Drupal\Component\Plugin\PluginBase;

/**
 * Base class for Better exposed filters widget plugins.
 */
abstract class BetterExposedFiltersFilterWidgetBase extends PluginBase implements BetterExposedFiltersFilterWidgetInterface {

  /**
   * {@inheritdoc}
   */
  public function label() {
    // Return the label defined in the annotation.
    return $this->pluginDefinition['label'];
  }

}
