<?php

namespace Drupal\mason\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining Mason entity.
 */
interface MasonInterface extends ConfigEntityInterface {

  /**
   * Returns the Mason options by group, or property.
   *
   * @param string $group
   *   The name of setting group: filler.
   * @param string $property
   *   The name of specific property: ratio, columns.
   *
   * @return mixed|array|null
   *   Available options by $group, $property, all, or NULL.
   */
  public function getOptions($group = NULL, $property = NULL);

  /**
   * Returns the value of a mason setting.
   *
   * @param string $option_name
   *   The option name.
   *
   * @return mixed
   *   The option value.
   */
  public function getOption($option_name);

  /**
   * Returns the Mason json.
   *
   * @return string
   *   The output of the Mason json.
   */
  public function getJson();

}
