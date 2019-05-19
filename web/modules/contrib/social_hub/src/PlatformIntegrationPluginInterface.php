<?php

namespace Drupal\social_hub;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Plugin\PluginWithFormsInterface;

/**
 * Defines the interface for platform plugins.
 */
interface PlatformIntegrationPluginInterface extends PluginWithFormsInterface, PluginFormInterface, ConfigurablePluginInterface {

  /**
   * Return the name of the platform.
   *
   * @return string
   *   The name of the platform.
   */
  public function getLabel();

  /**
   * Build the plugin output.
   *
   * @param array $context
   *   An assoc array to serve as plugin's build context.
   *   Available keys:
   *    - platform: The platform entity being built.
   *    - entity: An entity instance if the platform is being used over
   *      an entity e.g.: sharing.
   *    - user: Current user instance. If not present will be taken
   *      from current session value. In mind that 'entity' is also
   *      a user will take preference over this value.
   *
   * @return array
   *   A render array for the output.
   */
  public function build(array $context = []);

}
