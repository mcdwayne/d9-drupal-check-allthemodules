<?php

namespace Drupal\simple_amp;

use Drupal\Component\Plugin\PluginBase;

class AmpComponentBase extends PluginBase implements AmpComponentInterface {

  /**
   * Get plugin name.
   */
  public function getName() {
    return $this->pluginDefinition['name'];
  }

  /**
   * Get plugin description.
   */
  public function getDescription() {
    return $this->pluginDefinition['description'];
  }

  /**
   * Get plugin regexp.
   */
  public function getRegexp() {
    return $this->pluginDefinition['regexp'];
  }

  /**
   * AMP component Javascript.
   */
  public function getElement() {
    // Return javascript tag.
  }

}
