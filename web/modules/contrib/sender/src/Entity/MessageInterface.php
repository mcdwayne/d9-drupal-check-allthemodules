<?php

namespace Drupal\sender\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the interface of a message entity.
 */
interface MessageInterface extends ConfigEntityInterface {

  /**
   * Gets the label.
   *
   * @return string
   *   The label.
   */
  public function getLabel();

  /**
   * Sets the label.
   *
   * @param string $label
   *   The new label.
   */
  public function setLabel($label);

  /**
   * Gets the message's group ID.
   *
   * @return string
   *   The message's group. It might be empty if no group is set.
   */
  public function getGroupId();

  /**
   * Sets the message's group.
   *
   * @param string $group_id
   *   The group ID.
   */
  public function setGroupId($group_id);

  /**
   * Gets the message's group.
   *
   * @return \Drupal\sender\Plugin\SenderMessageGroup
   *   The message's group plugin.
   */
  public function getGroup();

  /**
   * Gets the message's subject.
   *
   * @return string
   *   The message's subject.
   */
  public function getSubject();

  /**
   * Sets the message's subject.
   *
   * @param string $subject
   *   The message's subject.
   */
  public function setSubject($subject);

  /**
   * Gets the format of the message's body.
   *
   * @return string
   *   The format of the body.
   */
  public function getBodyFormat();

  /**
   * Gets the value of the message's body.
   *
   * @return string
   *   The value of the body.
   */
  public function getBodyValue();

  /**
   * Gets the message's body.
   *
   * @return array
   *   An associative array with 'value' and 'format' keys.
   */
  public function getBody();

  /**
   * Sets the message's body.
   *
   * @param array $body
   *   An associative array with 'value' and 'format' keys.
   */
  public function setBody(array $body);

  /**
   * Gets a list of token types allowed in the message's body and subject.
   *
   * @return array
   *   A list of allowed token types.
   */
  public function getTokenTypes();

  /**
   * Sets allowed token types.
   *
   * @param array $token_types
   *   A list of token types.
   */
  public function setTokenTypes(array $token_types);

  /**
   * Builds a render array for the message.
   *
   * @param \Drupal\Core\Session\AccountInterface $recipient
   *   The recipient of the message.
   * @param array $data
   *   An array of values to be used in token replacement.
   *
   * @see \Drupal\Core\Utility\Token::replace()
   *
   * @return array
   *   A render array for the message.
   */
  public function build(AccountInterface $recipient, array $data);

  /**
   * Loads messages by group.
   *
   * @param string $group_id
   *   The group ID.
   *
   * @return array
   *   An array of messages of the specified group.
   */
  public static function loadByGroup($group_id);

}
