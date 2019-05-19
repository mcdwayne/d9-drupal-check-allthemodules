<?php

namespace Drupal\virtual_entities\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for Virtual entity storage client plugins.
 */
interface VirtualEntityStorageClientPluginInterface extends PluginInspectionInterface {

  /**
   * Get the client plugin name.
   *
   * @return string
   *   The client plugin name.
   */
  public function getPluginLabel();

  /**
   * Query entity.
   *
   * @param array $parameters
   *   Key-value pairs to query.
   *
   * @return mixed
   *   The virtual entities.
   */
  public function query(array $parameters);

  /**
   * Load the entity.
   *
   * @param mixed $id
   *   The entity ID.
   *
   * @return mixed
   *   The entity null if nothing is found.
   */
  public function load($id);

}
