<?php

namespace Drupal\vault\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for Vault Authentication plugins.
 */
interface VaultAuthInterface extends PluginInspectionInterface {

  /**
   * Returns the vault authentication strategy object.
   *
   * @return \Vault\AuthenticationStrategies\AuthenticationStrategy
   *   The vault authentication strategy provided by this plugin.
   */
  public function getAuthenticationStrategy();

}
