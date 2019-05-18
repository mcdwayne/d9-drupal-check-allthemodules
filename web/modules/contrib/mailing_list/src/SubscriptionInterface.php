<?php

namespace Drupal\mailing_list;

use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface defining a subscription entity.
 */
interface SubscriptionInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Denotes that the subscription is active.
   */
  const ACTIVE = 1;

  /**
   * Denotes that the subscription is inactive.
   */
  const INACTIVE = 0;

  /**
   * Gets the mailing list ID to which this subscription belongs.
   *
   * @return string
   *   The mailing list ID.
   */
  public function getListId();

  /**
   * Gets the mailing list to which this subscription belongs.
   *
   * @return \Drupal\mailing_list\MailingListInterface
   *   The mailing list.
   */
  public function getList();

  /**
   * Gets the subscription title.
   *
   * @return string
   *   Title of the subscription.
   */
  public function getTitle();

  /**
   * Sets the subscription title.
   *
   * @param string $title
   *   The subscription title.
   *
   * @return \Drupal\mytest\SubscriptionInterface
   *   The called subscription entity.
   */
  public function setTitle($title);

  /**
   * Gets the subscription creation timestamp.
   *
   * @return int
   *   Creation timestamp of the subscription.
   */
  public function getCreatedTime();

  /**
   * Sets the subscription creation timestamp.
   *
   * @param int $timestamp
   *   The subscription creation timestamp.
   *
   * @return \Drupal\mytest\SubscriptionInterface
   *   The called subscription entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the subscription status.
   *
   * @return bool
   *   TRUE if the subscription is active, FALSE otherwise.
   */
  public function isActive();

  /**
   * Sets the subscription status.
   *
   * @param bool $status
   *   TRUE to set this subscription as active, FALSE to set it as inactive.
   *
   * @return \Drupal\mailing_list\SubscriptionInterface
   *   The called subscription entity.
   */
  public function setStatus($status);

  /**
   * Get this subscription email address.
   *
   * @param bool $obfuscate
   *   Obfuscate the email address by replacing some characters with '*'.
   *   Defaults to FALSE (do not obfuscate).
   *
   * @return string
   *   The subscription email address.
   */
  public function getEmail($obfuscate = FALSE);

  /**
   * Set the email address of this subscription.
   *
   * @param string $email
   *   The new email address.
   */
  public function setEmail($email);

  /**
   * Calculates an access hash.
   *
   * @return string
   *   The hash.
   */
  public function getAccessHash();

}
