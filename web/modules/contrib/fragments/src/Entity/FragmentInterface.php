<?php

namespace Drupal\fragments\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining fragment entities.
 *
 * @ingroup fragments
 */
interface FragmentInterface extends ContentEntityInterface, RevisionLogInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the fragment title.
   *
   * @return string
   *   Title of the fragment.
   */
  public function getTitle();

  /**
   * Sets the fragment title.
   *
   * @param string $title
   *   The fragment title.
   *
   * @return \Drupal\fragments\Entity\FragmentInterface
   *   The called fragment entity.
   */
  public function setTitle($title);

  /**
   * Gets the fragment creation timestamp.
   *
   * @return int
   *   Creation timestamp of the fragment.
   */
  public function getCreatedTime();

  /**
   * Sets the fragment creation timestamp.
   *
   * @param int $timestamp
   *   The fragment creation timestamp.
   *
   * @return \Drupal\fragments\Entity\FragmentInterface
   *   The called fragment entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the fragment published status indicator.
   *
   * Unpublished fragment are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the fragment is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a fragment.
   *
   * @param bool $published
   *   TRUE to set this fragment to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\fragments\Entity\FragmentInterface
   *   The called fragment entity.
   */
  public function setPublished($published);

  /**
   * Gets the fragment revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the fragment revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return \Drupal\fragments\Entity\FragmentInterface
   *   The called fragment entity.
   */
  public function setRevisionCreationTime($timestamp);

  /**
   * Gets the fragment revision author.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity for the revision author.
   */
  public function getRevisionUser();

  /**
   * Sets the fragment revision author.
   *
   * @param int $uid
   *   The user ID of the revision author.
   *
   * @return \Drupal\fragments\Entity\FragmentInterface
   *   The called fragment entity.
   */
  public function setRevisionUserId($uid);

}
