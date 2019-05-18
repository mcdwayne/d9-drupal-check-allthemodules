<?php

namespace Drupal\homebox\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Homebox entities.
 */
interface HomeboxInterface extends ConfigEntityInterface {

  /**
   * Gets the Homebox page path.
   *
   * @return string
   *   Path of the Homebox.
   */
  public function getPath();

  /**
   * Gets the list of user role IDs.
   *
   * @return array
   *   List of user role IDs.
   */
  public function getRoles();

  /**
   * Gets the list of visibility options.
   *
   * @return array
   *   The list of visibility options.
   */
  public function getOptions();

  /**
   * Gets the layout ID.
   *
   * @return string
   *   Layout ID.
   */
  public function getRegions();

  /**
   * Gets the enabled blocks.
   *
   * @return mixed
   *   Enabled blocks.
   */
  public function getBlocks();

  /**
   * Sets the path of the Homebox page.
   *
   * @param string $name
   *   The path alias of the Homebox.
   *
   * @return $this
   */
  public function setPath($name);

  /**
   * Sets the list of user role IDs.
   *
   * @param array $roles
   *   The list of user role IDs.
   *
   * @return $this
   */
  public function setRoles(array $roles);

  /**
   * Sets the list of layouted blocks.
   *
   * @param array $blocks
   *   Gets the layouted blocks.
   *
   * @return $this
   */
  public function setBlocks(array $blocks);

}
