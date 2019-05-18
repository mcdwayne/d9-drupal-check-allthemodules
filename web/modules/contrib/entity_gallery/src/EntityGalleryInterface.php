<?php

namespace Drupal\entity_gallery;

use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface defining an entity gallery entity.
 */
interface EntityGalleryInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface, RevisionLogInterface {

  /**
   * Gets the entity gallery type.
   *
   * @return string
   *   The entity gallery type.
   */
  public function getType();

  /**
   * Gets the entity gallery title.
   *
   * @return string
   *   Title of the entity gallery.
   */
  public function getTitle();

  /**
   * Sets the entity gallery title.
   *
   * @param string $title
   *   The entity gallery title.
   *
   * @return \Drupal\entity_gallery\EntityGalleryInterface
   *   The called entity gallery entity.
   */
  public function setTitle($title);

  /**
   * Gets the entity gallery creation timestamp.
   *
   * @return int
   *   Creation timestamp of the entity gallery.
   */
  public function getCreatedTime();

  /**
   * Sets the entity gallery creation timestamp.
   *
   * @param int $timestamp
   *   The entity gallery creation timestamp.
   *
   * @return \Drupal\entity_gallery\EntityGalleryInterface
   *   The called entity gallery entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the entity gallery published status indicator.
   *
   * Unpublished entity galleries are only visible to their authors and to
   * administrators.
   *
   * @return bool
   *   TRUE if the entity gallery is published.
   */
  public function isPublished();

  /**
   * Sets the published status of an entity gallery..
   *
   * @param bool $published
   *   TRUE to set this entity gallery to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\entity_gallery\EntityGalleryInterface
   *   The called entity gallery entity.
   */
  public function setPublished($published);

  /**
   * Gets the entity galery revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the entity gallery revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return \Drupal\entity_gallery\EntityGalleryInterface
   *   The called entity gallery entity.
   */
  public function setRevisionCreationTime($timestamp);

  /**
   * Gets the entity gallery revision author.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity for the revision author.
   *
   * @deprecated in Drupal 8.2.0, will be removed before Drupal 9.0.0. Use
   *   \Drupal\Core\Entity\RevisionLogInterface::getRevisionUser() instead.
   */
  public function getRevisionAuthor();

  /**
   * Sets the entity gallery revision author.
   *
   * @param int $uid
   *   The user ID of the revision author.
   *
   * @return \Drupal\entity_gallery\EntityGalleryInterface
   *   The called entity gallery entity.
   *
   * @deprecated in Drupal 8.2.0, will be removed before Drupal 9.0.0. Use
   *   \Drupal\Core\Entity\RevisionLogInterface::setRevisionUserId() instead.
   */
  public function setRevisionAuthorId($uid);

}
