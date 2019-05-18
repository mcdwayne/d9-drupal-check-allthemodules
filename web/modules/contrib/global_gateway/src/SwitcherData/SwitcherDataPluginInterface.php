<?php

namespace Drupal\global_gateway\SwitcherData;

/**
 * Interface SwitcherDataPluginInterface.
 */
interface SwitcherDataPluginInterface {

  /**
   * Get plugin ID.
   */
  public function id();

  /**
   * Get plugin label.
   */
  public function getLabel();

  /**
   * Used for returning values by key.
   *
   * @var string
   *   Key of the value.
   *
   * @return string
   *   Value of the key.
   */
  public function get($key);

  /**
   * Used for returning values by key.
   *
   * @var string
   *   Key of the value.
   *
   * @var string
   *   Value of the key.
   */
  public function set($key, $value);

  /**
   * Returns a renderable array.
   *
   * @return array
   *   A renderable array.
   */
  public function getOutput($region_code);

}
