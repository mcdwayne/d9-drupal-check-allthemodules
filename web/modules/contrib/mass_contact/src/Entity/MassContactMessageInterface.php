<?php

namespace Drupal\mass_contact\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Defines a mass contact message interface.
 */
interface MassContactMessageInterface extends EntityOwnerInterface, ContentEntityInterface {

  /**
   * Gets the message body.
   *
   * @return string
   *   The message body.
   */
  public function getBody();

  /**
   * Get the message categories.
   *
   * @return \Drupal\mass_contact\Entity\MassContactCategoryInterface[]
   *   The message categories.
   */
  public function getCategories();

  /**
   * Get the message body format.
   *
   * @return string
   *   The message body format.
   */
  public function getFormat();

  /**
   * Get the message subject.
   *
   * @return string
   *   The message subject.
   */
  public function getSubject();

  /**
   * Gets the sent time.
   *
   * @return int
   *   The time the message was sent.
   */
  public function getSentTime();

}
