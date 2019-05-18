<?php

/**
 * @file
 * Contains \Drupal\adv_varnish\UserBlockInterface.
 */

namespace Drupal\adv_varnish;

use Drupal\Component\Plugin\PluginInspectionInterface;

interface UserBlockInterface extends PluginInspectionInterface {

  public static function content();

}
