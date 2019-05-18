<?php
/**
 * @file
 * Contains \Drupal\dummyimage\DummyImageProviderBase
 */

namespace Drupal\dummyimage;

use Drupal\Component\Plugin\PluginBase;

abstract class DummyImageProviderBase extends PluginBase implements DummyImageProviderInterface {

  public function getName() {
    return $this->pluginDefinition['name'];
  }

  public function getOptions() {
    // TODO. Implement actual options.
  }
}