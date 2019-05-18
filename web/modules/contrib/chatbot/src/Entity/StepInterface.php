<?php

namespace Drupal\chatbot\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface for defining Step entities.
 *
 * @ingroup chatbot
 */
interface StepInterface extends ContentEntityInterface, EntityChangedInterface {

  // Add get/set methods for your configuration properties here.
  /**
   * Gets the Step title.
   *
   * @return string
   *   Title of the Step.
   */
  public function getTitle();

  /**
   * Sets the Step title.
   *
   * @param string $title
   *   The Step title.
   *
   * @return \Drupal\chatbot\Entity\StepInterface
   *   The called Step entity.
   */
  public function setTitle($title);

  /**
   * Gets the Step creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Step.
   */
  public function getCreatedTime();

  /**
   * Sets the Step creation timestamp.
   *
   * @param int $timestamp
   *   The Step creation timestamp.
   *
   * @return \Drupal\chatbot\Entity\StepInterface
   *   The called Step entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Step published status indicator.
   *
   * Unpublished Step are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Step is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Step.
   *
   * @param bool $published
   *   TRUE to set this Step to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\chatbot\Entity\StepInterface
   *   The called Step entity.
   */
  public function setPublished($published);

  /**
   * Returns a list of Message Entity.
   *
   * @return \Drupal\chatbot\Entity\MessageInterface
   *   A list of Messages
   */
  public function getMessages();

  /**
   * Sets the entity owner's user entity.
   *
   * @param $messages
   *   A list of Messages
   *
   * @return $this
   */
  public function setMessages($messages);

}
