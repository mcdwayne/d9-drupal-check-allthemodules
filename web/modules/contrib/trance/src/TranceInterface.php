<?php

namespace Drupal\trance;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides an interface for defining trance entities.
 *
 * @ingroup trance
 */
interface TranceInterface extends EntityInterface, ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the trance type.
   *
   * @return string
   *   The trance type.
   */
  public function getType();

  /**
   * Gets the trance name.
   *
   * @return string
   *   Name of the trance.
   */
  public function getName();

  /**
   * Sets the trance name.
   *
   * @param string $name
   *   The trance name.
   *
   * @return \Drupal\trance\TranceInterface
   *   The called trance entity.
   */
  public function setName($name);

  /**
   * Gets the trance creation timestamp.
   *
   * @return int
   *   Creation timestamp of the trance.
   */
  public function getCreatedTime();

  /**
   * Sets the trance creation timestamp.
   *
   * @param int $timestamp
   *   The trance creation timestamp.
   *
   * @return \Drupal\trance\TranceInterface
   *   The called trance entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the trance published status indicator.
   *
   * Unpublished trance are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the trance is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a trance.
   *
   * @param bool $published
   *   TRUE to set this trance to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\trance\TranceInterface
   *   The called trance entity.
   */
  public function setPublished($published);

  /**
   * Gets the entity revision author.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity for the revision author.
   */
  public function getRevisionAuthor();

  /**
   * Sets the block revision log message.
   *
   * @param string $revision_log
   *   The revision log message.
   *
   * @return \Drupal\cms_component\CmsComponentInterface
   *   The class instance that this method is called on.
   */
  public function setRevisionLog($revision_log);

}
