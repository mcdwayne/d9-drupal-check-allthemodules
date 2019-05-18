<?php

namespace Drupal\client_connection\Entity\Storage;

use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;

/**
 * Defines an interface for the client connection config storage class.
 */
interface ClientConnectionConfigStorageInterface extends ConfigEntityStorageInterface {

  /**
   * Finds a Client Connection Configuration entity ID.
   *
   * @param string $plugin_id
   *   The plugin ID.
   * @param string $instance_id
   *   The instance ID to retrieve. This allows for multiple instances of
   *   configuration in the same channel.
   * @param string|string[] $channel_id
   *   The channel ID or an array of channel IDs. Channels allow to separate
   *   different areas of configuration, like returning user-specific vs
   *   site-wide configuration.
   *
   * @return null|string
   *   The client connection entity ID if found. Null otherwise.
   */
  public function findId($plugin_id, $instance_id = 'default', $channel_id = 'site');

}
