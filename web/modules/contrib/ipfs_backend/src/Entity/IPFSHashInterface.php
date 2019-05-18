<?php

namespace Drupal\ipfs_backend\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining IPFSHash entities.
 *
 * @ingroup ipfs_backend
 */
interface IPFSHashInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the IPFSHash name.
   *
   * @return string
   *   Name of the IPFSHash.
   */
  public function getName();

  /**
   * Sets the IPFSHash name.
   *
   * @param string $name
   *   The IPFSHash name.
   *
   * @return \Drupal\ipfs_backend\Entity\IPFSHashInterface
   *   The called IPFSHash entity.
   */
  public function setName($name);

  /**
   * Gets the IPFSHash creation timestamp.
   *
   * @return int
   *   Creation timestamp of the IPFSHash.
   */
  public function getCreatedTime();

  /**
   * Sets the IPFSHash creation timestamp.
   *
   * @param int $timestamp
   *   The IPFSHash creation timestamp.
   *
   * @return \Drupal\ipfs_backend\Entity\IPFSHashInterface
   *   The called IPFSHash entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the IPFSHash published status indicator.
   *
   * Unpublished IPFSHash are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the IPFSHash is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a IPFSHash.
   *
   * @param bool $published
   *   TRUE to set this IPFSHash to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\ipfs_backend\Entity\IPFSHashInterface
   *   The called IPFSHash entity.
   */
  public function setPublished($published);

}
