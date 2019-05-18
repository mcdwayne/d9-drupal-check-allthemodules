<?php

namespace Drupal\client_connection\Entity;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;

/**
 * Provides an interface for defining Client Connection Configuration entities.
 */
interface ClientConnectionConfigInterface extends ConfigEntityInterface, EntityWithPluginCollectionInterface, PluginInspectionInterface {

  /**
   * Returns channel classifications.
   *
   * @return string[]
   *   An array of channel IDs.
   */
  public function getChannels();

  /**
   * Add a channel to the channel list.
   *
   * @param string $channel
   *   The channel key to add to the channel array.
   *
   * @return $this
   */
  public function addChannel($channel);

  /**
   * Remove a channel to the channel list.
   *
   * @param string $channel
   *   The channel key to remove from the channel array.
   *
   * @return $this
   */
  public function removeChannel($channel);

  /**
   * Sets and initiates the client connection plugin.
   *
   * @param string $plugin_id
   *   The plugin id.
   *
   * @return $this
   */
  public function setPluginId($plugin_id);

  /**
   * Returns the plugin instance.
   *
   * @return \Drupal\client_connection\Plugin\ClientConnection\ClientConnectionInterface
   *   The Client Connection plugin.
   */
  public function getPlugin();

  /**
   * Sets the client instance ID.
   *
   * @param string $instance_id
   *   The instance id.
   *
   * @return $this
   */
  public function setInstanceId($instance_id);

  /**
   * Returns the instance ID.
   *
   * @return string
   *   The Client Connection Config entity instance ID.
   */
  public function getInstanceId();

}
