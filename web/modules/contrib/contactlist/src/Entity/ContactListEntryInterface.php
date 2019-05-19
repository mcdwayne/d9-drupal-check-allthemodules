<?php

namespace Drupal\contactlist\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Defines an interface for contact list entries.
 */
interface ContactListEntryInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {
  /**
   * Gets the display name of the contact.
   *
   * @return string
   */
  public function getContactName();

  /**
   * Sets the display name of the contact.
   *
   * @param string $value
   *
   * @return $this
   */
  public function setContactName($value);

  /**
   * Gets the email address of the contact.
   *
   * @return string
   */
  public function getEmail();

  /**
   * Sets the email address of the contact.
   *
   * @param string $value
   *
   * @return $this
   */
  public function setEmail($value);

  /**
   * Gets the phone number of the contact.
   *
   * @return string
   */
  public function getPhoneNumber();

  /**
   * Sets the phone number of the contact.
   *
   * @param string $value
   *
   * @return $this
   */
  public function setPhoneNumber($value);

  /**
   * Gets the time the contact was created.
   *
   * @return string
   */
  public function getCreatedTime();

  /**
   * Sets the time the contact was created.
   *
   * @param string $value
   *
   * @return $this
   */
  public function setCreatedTime($value);

  /**
   * Gets the contact groups the contact entry belongs to.
   *
   * @return \Drupal\contactlist\Entity\ContactGroupInterface[]
   *   A list of contact groups entity objects this contact entry belongs to.
   */
  public function getGroups();

  /**
   * Sets the contact groups the contact entry belongs to.
   *
   * The contact entry WILL BE removed from the previous groups it belonged to.
   *
   * @param \Drupal\contactlist\Entity\ContactGroupInterface[]|array $value
   *   An array of strings or contact group entity objects.
   *
   * @return $this
   */
  public function setGroups(array $value);

  /**
   * Adds the contact entry to the specified contact groups.
   *
   * The contact entry WILL NOT BE removed from the previous groups it belonged
   * to.
   *
   * @param \Drupal\contactlist\Entity\ContactGroupInterface[]|array $value
   *   An array of strings or contact group entity objects.
   *
   * @return $this
   */
  public function addGroups(array $value);

  /**
   * Removes the contact entry from the specified contact groups.
   *
   * @param \Drupal\contactlist\Entity\ContactGroupInterface[]|array $value
   *   An array of strings or contact group entity objects.
   *
   * @return $this
   */
  public function removeGroups(array $value);

}
