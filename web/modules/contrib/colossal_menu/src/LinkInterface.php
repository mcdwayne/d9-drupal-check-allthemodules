<?php

/**
 * @file
 * Contains \Drupal\colossal_menu\LinkInterface.
 */

namespace Drupal\colossal_menu;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Menu\MenuLinkInterface;

/**
 * Provides an interface for defining Link entities.
 *
 * @ingroup colossal_menu
 */
interface LinkInterface extends MenuLinkInterface, ContentEntityInterface, EntityChangedInterface {

  /**
   * Sets the parent.
   *
   * @param int $parent
   *   The id of the parent.
   *
   * @return \Drupal\colossal_menu\LinkInterface
   *   Return this.
   */
  public function setParent($parent);

  /**
   * Sets the weight.
   *
   * @param int $weight
   *   The weight.
   *
   * @return \Drupal\colossal_menu\LinkInterface
   *   Return this.
   */
  public function setWeight($weight);

  /**
   * Sets the enabled status.
   *
   * @param bool $enabled
   *   The enabled status.
   *
   * @return \Drupal\colossal_menu\LinkInterface
   *   Return this.
   */
  public function setEnabled($enabled);

  /**
   * Gets the machine name.
   *
   * @return string
   *   Machine name.
   */
  public function getMachineName();

  /**
   * Determines if link is external.
   *
   * @return bool
   *   Whether the current link is external or not.
   */
  public function isExternal();

  /**
   * Determines if the title should be shown.
   *
   * @return bool
   *   Whether the title should be shown or not.
   */
  public function showTitle();

}
