<?php
/**
 * @file
 * Contains \Drupal\collect\Processor\ProcessorManagerInterface.
 */

namespace Drupal\collect\Processor;

use Drupal\collect\Model\ModelPluginInterface;
use Drupal\Component\Plugin\PluginManagerInterface;

/**
 * Interface for the Collect post-processor plugin manager.
 */
interface ProcessorManagerInterface extends PluginManagerInterface {

  /**
   * Returns a pre-configured instance of a plugin.
   *
   * @param string $plugin_id
   *   The ID of the plugin being instantiated.
   * @param array $configuration
   *   An array of configuration relevant to the plugin instance.
   * @param \Drupal\collect\Model\ModelPluginInterface $model_plugin
   *   The model plugin that the processor plugin is configured for. This
   *   parameter is declared in code as optional, in order to satisfy the parent
   *   interface, but it is indeed required.
   *
   * @return \Drupal\collect\Processor\ProcessorInterface
   *   A fully configured plugin instance.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   *   If the instance cannot be created, such as if the ID is invalid.
   */
  public function createInstance($plugin_id, array $configuration = array(), ModelPluginInterface $model_plugin = NULL);

}
