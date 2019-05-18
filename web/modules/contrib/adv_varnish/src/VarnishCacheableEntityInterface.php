<?php

/**
 * @file
 * Contains \Drupal\adv_varnish\VarnishCacheableEntityInterface.
 */

namespace Drupal\adv_varnish;

use Drupal\Component\Plugin\PluginInspectionInterface;

interface VarnishCacheableEntityInterface extends PluginInspectionInterface {

  public function generateSettingsKey();

}
