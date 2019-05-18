<?php

declare(strict_types = 1);

namespace Drupal\config_owner;

use Drupal\Component\Plugin\PluginManagerInterface;

/**
 * Defines an interface for owned_config managers.
 */
interface OwnedConfigManagerInterface extends PluginManagerInterface {

  /**
   * Returns all the owned config values that should not be alterable.
   *
   * @return array
   *   The config values.
   */
  public function getOwnedConfigValues();

  /**
   * Determines whether a given configuration is owned by any module.
   *
   * @param string $name
   *   The config name.
   *
   * @return bool
   *   Whether it's owned by any module.
   */
  public function configIsOwned(string $name);

}
