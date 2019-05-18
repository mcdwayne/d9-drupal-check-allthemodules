<?php

namespace Drupal\blogapi;

use Drupal\Component\Plugin\PluginBase;

/**
 * Class ProviderBase.
 *
 * @package Drupal\blogapi
 *
 * The base class that should be used for creating BlogAPI providers.
 */
class ProviderBase extends PluginBase implements BlogapiProviderInterface {

  /**
   * Returns all implemented methods.
   *
   * @return array
   *   An array of implemented methods.
   */
  public static function getMethods() {
    return [];
  }

}
