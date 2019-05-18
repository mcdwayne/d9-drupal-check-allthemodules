<?php

namespace Drupal\private_messages\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\Entity\User;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\UserInterface;

/**
 * Provides an interface for defining Dialog entities.
 *
 * @ingroup private_messages
 */
interface DialogInterface extends  ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Dialog name.
   *
   * @return string
   *   Name of the Dialog.
   */
  public function getName();

  /**
   * Sets the Dialog name.
   *
   * @param string $name
   *   The Dialog name.
   *
   * @return \Drupal\private_messages\Entity\DialogInterface
   *   The called Dialog entity.
   */
  public function setName($name);

  /**
   * Gets the Dialog creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Dialog.
   */
  public function getCreatedTime();

  /**
   * Sets the Dialog creation timestamp.
   *
   * @param int $timestamp
   *   The Dialog creation timestamp.
   *
   * @return \Drupal\private_messages\Entity\DialogInterface
   *   The called Dialog entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Dialog published status indicator.
   *
   * Unpublished Dialog are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Dialog is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Dialog.
   *
   * @param bool $published
   *   TRUE to set this Dialog to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\private_messages\Entity\DialogInterface
   *   The called Dialog entity.
   */
  public function setPublished($published);

  /**
   * Get owner.
   *
   * @return UserInterface
   *  User creator of a dialog.
   */
  public function getOwner();

  /**
   * Gets recipient entity id.
   *
   * @return integer
   */
  public function getRecipientId() : int;

  /**
   * Gets recipient user Entity.
   *
   * @return UserInterface
   */
  public function getRecipient() : UserInterface;


  /**
   * Gets participant entity. The "other guy" for current user.
   *
   * @return \Drupal\user\UserInterface
   */
  public function getParticipant() : UserInterface;

  /**
   * Gets participant entity id.
   *
   * This always provide "other guy" id.
   *
   * @return int
   */
  public function getParticipantId() : int;

  /**
   * Fires Message count attributes change.
   *
   * @return mixed
   */
  public function onMessageCreated();

  /**
   * Gets current user new messages count.
   *
   * @return int
   */
  public function getNewMessagesCount() : int;
}
