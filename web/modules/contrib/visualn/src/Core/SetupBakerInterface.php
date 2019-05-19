<?php

namespace Drupal\visualn\Core;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Defines an interface for VisualN Setup Baker plugins.
 */
interface SetupBakerInterface extends PluginInspectionInterface, ConfigurablePluginInterface, PluginFormInterface {

  /**
   * Get ready setup for drawer to use.
   *
   * Setups are used by drawers in a way they prefer too, no strict requirements are imposed.
   * Though generally setups are used to hide raw configuration (e.g. JSON) from subdrawers
   * and styles administrating users and to make them reusable across multiple subdrawers and styles.
   * Also it makes it handy when making subdrawers implementing modifiers - one setup can be used
   * as a base for multiple subdrawers with different from one another settings sets.
   *
   * Setups are not something special for drawers - it is just a way of bringing out a config for a drawer configuration
   * field into an external VisualN Setup entity. Generally it is more a drawer architecture pattern.
   *
   * @return array $drawer_ready_setup
   */
  public function bakeSetup();

}
