<?php

namespace Drupal\mail_entity_queue\Plugin;

use Drupal\Component\Plugin\PluginManagerInterface;

/**
 * Provides an interface defining a mail entity queue processor plugin manager.
 */
interface MailEntityQueueProcessorPluginManagerInterface extends PluginManagerInterface {

  /**
   * Create pre-configured instance of plugins.
   *
   * @param array $id
   *   Either the plugin ID or the base plugin ID of the plugins being
   *   instantiated. Also accepts an array of plugin IDs and an empty array to
   *   load all plugins.
   * @param array $configuration
   *   An array of configuration relevant to the plugin instances. Keyed by the
   *   plugin ID.
   *
   * @return \Drupal\mail_entity_queue\Plugin\MailEntityQueueProcessorInterface[]
   *   Fully configured plugin instances.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   *   If an instance cannot be created, such as if the ID is invalid.
   */
  public function createInstances($id = [], array $configuration = []);

}
