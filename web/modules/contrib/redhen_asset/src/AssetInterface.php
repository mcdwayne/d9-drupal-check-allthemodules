<?php

/**
 * @file
 * Contains \Drupal\redhen_asset\AssetInterface.
 */

namespace Drupal\redhen_asset;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Provides an interface for defining Asset entities.
 *
 * @ingroup redhen_asset
 */
interface AssetInterface extends ContentEntityInterface, EntityChangedInterface {
  /**
   * Gets the Asset type.
   *
   * @return string
   *   The Asset type.
   */
  public function getType();

  /**
   * Sets the Asset name.
   *
   * @param string $name
   *   The Asset name.
   *
   * @return \Drupal\redhen_asset\AssetInterface
   *   The called Asset entity.
   */
  public function setName($name);

  /**
   * Gets the Asset creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Asset.
   */
  public function getCreatedTime();

  /**
   * Sets the Asset creation timestamp.
   *
   * @param int $timestamp
   *   The Asset creation timestamp.
   *
   * @return \Drupal\redhen_asset\AssetInterface
   *   The called Asset entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns a label for the asset.
   */
  public function label();

  /**
   * Returns the Asset active status indicator.
   *
   * @return bool
   *   TRUE if the Asset is active.
   */
  public function isActive();

  /**
   * Sets the active status of a Asset.
   *
   * @param bool $active
   *   TRUE to set this Asset to active, FALSE to set it to inactive.
   *
   * @return \Drupal\redhen_asset\AssetInterface
   *   The called Asset entity.
   */
  public function setActive($active);

}
