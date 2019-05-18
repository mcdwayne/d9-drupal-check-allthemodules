<?php

namespace Drupal\reference_map\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Defines an interface for the Reference Map Config entity.
 */
interface ReferenceMapConfigInterface extends ConfigEntityInterface {

  /**
   * Sets the map property.
   *
   * @param mixed $map
   *   Either an array that fits the specifications of a map or a yaml string
   *   that also fits the specifications of a map.
   *
   * @return bool
   *   Returns True if the map has been successfully set, False otherwise.
   */
  public function setMap($map);

  /**
   * Set an entry into the settings array.
   *
   * @param string $key
   *   The setting key.
   * @param mixed $value
   *   The setting value.
   */
  public function setSetting($key, $value);

  /**
   * Gets the specified setting from the config entity.
   *
   * @param string $key
   *   The setting to get.
   *
   * @return mixed
   *   The value of the setting.
   */
  public function getSetting($key);

  /**
   * Magic getter for reference map config entities.
   *
   * @param string $property
   *   The property to get.
   *
   * @return mixed
   *   The value of the property.
   */
  public function __get($property);

}
