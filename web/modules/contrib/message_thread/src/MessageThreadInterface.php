<?php

namespace Drupal\message_thread;

use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface defining a Message entity.
 */
interface MessageThreadInterface extends ContentEntityInterface, EntityOwnerInterface {

  /**
   * Set the message thread.
   *
   * @param MessageThreadInterface $thread
   *   Message thread.
   *
   * @return \Drupal\message\MessageInterface
   *   Returns the message object.
   */
  public function setThread(MessageThreadInterface $thread);

  /**
   * Get the message thread.
   *
   * @return \Drupal\message\MessageThreadInterface
   *   Returns the message object.
   */
  public function getThread();

  /**
   * Retrieve the time stamp of the message.
   *
   * @return int
   *   The Unix timestamp.
   */
  public function getCreatedTime();

  /**
   * Setting the timestamp.
   *
   * @param int $timestamp
   *   The Unix timestamp.
   *
   * @return \Drupal\message\MessageInterface
   *   Returns the message object.
   */
  public function setCreatedTime($timestamp);

  /**
   * Return the UUID.
   *
   * @return string
   *   Return the UUID.
   */
  public function getUuid();

  /**
   * Retrieve the message arguments.
   *
   * @return array
   *   The arguments of the message.
   */
  public function getArguments();

  /**
   * Set the arguments of the message.
   *
   * @param array $values
   *   Array of arguments.
   *
   * @code
   *   $values = [
   *     '@name_without_callback' => 'John doe',
   *     '@name_with_callback' => [
   *       'callback' => 'User::load',
   *       'arguments' => [1],
   *     ],
   *   ];
   * @endcode
   *
   * @return \Drupal\message\MessageInterface
   *   Returns the message object.
   */
  public function setArguments(array $values);

  /**
   * Set the language that should be used.
   *
   * @param string $language
   *   The language to load from the message thread when fetching the text.
   */
  public function setLanguage($language);

  /**
   * Delete multiple message.
   *
   * @param array $ids
   *   The messages IDs to delete.
   */
  public static function deleteMultiple(array $ids);

  /**
   * Run a EFQ over messages from a given thread.
   *
   * @param string $thread
   *   The message thread.
   *
   * @return array
   *   Array of message IDs.
   */
  public static function queryByThread($thread);

  /**
   * Convert message contents to a string.
   *
   * @return string
   *   The message contents.
   */
  public function __toString();

}
