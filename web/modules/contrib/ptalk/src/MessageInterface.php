<?php

namespace Drupal\ptalk;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a ptalk_message entity.
 */
interface MessageInterface extends ContentEntityInterface, EntityOwnerInterface {

  /**
   * Returns the subject of the message.
   *
   * @return string
   *   The subject of the message.
   */
  public function getSubject();

  /**
   * Sets the subject of the message.
   *
   * @param string $subject
   *   The subject of the message.
   *
   * @return $this
   *   The class instance that this method is called on.
   */
  public function setSubject($subject);

  /**
   * Returns the owner of the message.
   *
   * @return object
   *   The owner of the ptalk_message entity.
   */
  public function getOwner();

  /**
   * Returns the owner ID of the message.
   *
   * @return string
   *   The id of the owner.
   */
  public function getOwnerId();

  /**
   * Returns the time that the message was created.
   *
   * @return int
   *   The timestamp of when the message was created.
   */
  public function getCreatedTime();

  /**
   * Returns the time that the message was changed.
   *
   * @return int
   *   The timestamp of when the message was changed.
   */
  public function getChangedTime();

  /**
   * Returns the ptalk_thread entity to which the message is belongs.
   *
   * @return \Drupal\ptalk\ThreadInterface
   *   The ptalk_thread entity.
   */
  public function getThread();

  /**
   * Returns the ID of the ptalk_thread entity to which the message is belongs.
   *
   * @return int
   *   The ID of the ptalk_thread entity.
   */
  public function getThreadId();

}
