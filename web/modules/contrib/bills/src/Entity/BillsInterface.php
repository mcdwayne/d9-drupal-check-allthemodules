<?php

namespace Drupal\bills\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Bills entities.
 *
 * @ingroup bills
 */
interface BillsInterface extends ContentEntityInterface, RevisionLogInterface, EntityChangedInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Bills name.
   *
   * @return string
   *   Name of the Bills.
   */
  public function getName();

  /**
   * Sets the Bills name.
   *
   * @param string $name
   *   The Bills name.
   *
   * @return \Drupal\bills\Entity\BillsInterface
   *   The called Bills entity.
   */
  public function setName($name);

  /**
   * Gets the Bills creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Bills.
   */
  public function getCreatedTime();

  /**
   * Sets the Bills creation timestamp.
   *
   * @param int $timestamp
   *   The Bills creation timestamp.
   *
   * @return \Drupal\bills\Entity\BillsInterface
   *   The called Bills entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Bills published status indicator.
   *
   * Unpublished Bills are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Bills is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Bills.
   *
   * @param bool $published
   *   TRUE to set this Bills to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\bills\Entity\BillsInterface
   *   The called Bills entity.
   */
  public function setPublished($published);

  /**
   * Gets the Bills revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the Bills revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return \Drupal\bills\Entity\BillsInterface
   *   The called Bills entity.
   */
  public function setRevisionCreationTime($timestamp);

  /**
   * Gets the Bills revision author.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity for the revision author.
   */
  public function getRevisionUser();

  /**
   * Sets the Bills revision author.
   *
   * @param int $uid
   *   The user ID of the revision author.
   *
   * @return \Drupal\bills\Entity\BillsInterface
   *   The called Bills entity.
   */
  public function setRevisionUserId($uid);

}
