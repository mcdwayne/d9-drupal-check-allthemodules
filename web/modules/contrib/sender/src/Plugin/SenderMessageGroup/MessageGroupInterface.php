<?php

namespace Drupal\sender\Plugin\SenderMessageGroup;

/**
 * Defines the interface of message groups.
 */
interface MessageGroupInterface {

 /**
  * Gets the message group ID.
  *
  * @return string
  *   The message group ID.
  */
  public function getId();

  /**
   * Gets the translated label.
   *
   * @return string
   *   The translated label.
   */
  public function getLabel();

  /**
   * Returns a list of token types allowed in the message's body and subject.
   *
   * @return array
   *   A list of allowed token types.
   */
  public function getTokenTypes();

}
