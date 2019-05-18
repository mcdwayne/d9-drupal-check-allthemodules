<?php

namespace Drupal\gridstack\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides a base interface defining GridStack entity.
 */
interface GridStackBaseInterface extends ConfigEntityInterface {

  /**
   * Returns the GridStack options by group, or property.
   *
   * @param string $group
   *   The name of setting group: breakpoints, grids, settings.
   * @param string $property
   *   The name of specific property: resizable, draggable, etc.
   *
   * @return mixed|array|null
   *   Available options by $group, $property, all, or NULL.
   */
  public function getOptions($group = NULL, $property = NULL);

  /**
   * Returns the value of a gridstack option group.
   *
   * @param string $group
   *   The group name: settings, icon, use_framework, breakpoints.
   *
   * @return mixed
   *   The option value merged with defaults.
   */
  public function getOption($group);

  /**
   * Sets the value of a gridstack option.
   *
   * @param string $name
   *   The option name: settings, icon, use_framework, breakpoints.
   * @param string $value
   *   The option value.
   *
   * @return $this
   *   The class is being called.
   */
  public function setOption($name, $value);

  /**
   * Returns the GridStack json suitable for HTML data-attribute.
   *
   * @param string $group
   *   The option group can be settings or grids.
   *
   * @return string
   *   The output of the GridStack json.
   */
  public function getJson($group = 'settings');

}
