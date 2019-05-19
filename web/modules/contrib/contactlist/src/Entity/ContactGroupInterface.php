<?php

namespace Drupal\contactlist\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

interface ContactGroupInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the name of the contact group.
   *
   * @return string
   */
  public function getName();

  /**
   * Sets the name of the contact group.
   *
   * @param string $value
   *   The new name of the contact group.
   *
   * @return $this
   */
  public function setName($value);

  /**
   * Gets the description of the contact group.
   *
   * @return string
   */
  public function getDescription();

  /**
   * Sets the description of the contact group.
   *
   * @param string $value
   *   The description.
   *
   * @return $this
   */
  public function setDescription($value);

  /**
   * Gets the weight of the contact group.
   *
   * @return int
   */
  public function getWeight();

  /**
   * Sets the weight of this contact group.
   *
   * @param int $value
   *   The value of the new weight.
   *
   * @return $this
   */
  public function setWeight($value);

  /**
   * Gets all the contacts that belong to this contact group.
   *
   * @return \Drupal\contactlist\Entity\ContactListEntryInterface[]
   *   A list of the contact entries in this contact group.
   */
  public function getContacts();

}
