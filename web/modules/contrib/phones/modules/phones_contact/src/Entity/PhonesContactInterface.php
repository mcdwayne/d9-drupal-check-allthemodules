<?php

namespace Drupal\phones_contact\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Phones contact entities.
 *
 * @ingroup phones_contact
 */
interface PhonesContactInterface extends ContentEntityInterface, RevisionLogInterface, EntityChangedInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Phones contact name.
   *
   * @return string
   *   Name of the Phones contact.
   */
  public function getName();

  /**
   * Sets the Phones contact name.
   *
   * @param string $name
   *   The Phones contact name.
   *
   * @return \Drupal\phones_contact\Entity\PhonesContactInterface
   *   The called Phones contact entity.
   */
  public function setName($name);

  /**
   * Gets the Phones contact creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Phones contact.
   */
  public function getCreatedTime();

  /**
   * Sets the Phones contact creation timestamp.
   *
   * @param int $timestamp
   *   The Phones contact creation timestamp.
   *
   * @return \Drupal\phones_contact\Entity\PhonesContactInterface
   *   The called Phones contact entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Phones contact published status indicator.
   *
   * Unpublished Phones contact are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Phones contact is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Phones contact.
   *
   * @param bool $published
   *   TRUE to set this Phones contact to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\phones_contact\Entity\PhonesContactInterface
   *   The called Phones contact entity.
   */
  public function setPublished($published);

  /**
   * Gets the Phones contact revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the Phones contact revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return \Drupal\phones_contact\Entity\PhonesContactInterface
   *   The called Phones contact entity.
   */
  public function setRevisionCreationTime($timestamp);

  /**
   * Gets the Phones contact revision author.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity for the revision author.
   */
  public function getRevisionUser();

  /**
   * Sets the Phones contact revision author.
   *
   * @param int $uid
   *   The user ID of the revision author.
   *
   * @return \Drupal\phones_contact\Entity\PhonesContactInterface
   *   The called Phones contact entity.
   */
  public function setRevisionUserId($uid);

}
