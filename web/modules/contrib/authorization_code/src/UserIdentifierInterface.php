<?php

namespace Drupal\authorization_code;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * The user identifier plugin interface.
 */
interface UserIdentifierInterface extends PluginInspectionInterface, PluginFormInterface, ConfigurablePluginInterface {

  /**
   * Loads the user.
   *
   * @param mixed $identifier
   *   The identifier to use to load the user.
   *
   * @return \Drupal\user\UserInterface|null
   *   The user or null, if no user was found with the identifier.
   */
  public function loadUser($identifier);

}
