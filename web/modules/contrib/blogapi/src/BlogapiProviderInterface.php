<?php

namespace Drupal\blogapi;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Interface BlogapiProviderInterface.
 *
 * @package Drupal\blogapi
 *
 * Interface used for creating BlogAPI providers.
 */
interface BlogapiProviderInterface extends PluginInspectionInterface {

  /**
   * Returns all implemented methods.
   *
   * @return array
   *   An array of implemented methods.
   */
  public static function getMethods();

}
