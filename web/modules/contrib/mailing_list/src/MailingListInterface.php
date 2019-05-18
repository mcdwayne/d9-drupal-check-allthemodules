<?php

namespace Drupal\mailing_list;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a mailing list entity.
 */
interface MailingListInterface extends ConfigEntityInterface {

  /**
   * Gets the help information.
   *
   * @return string
   *   The help information of this mailing list.
   */
  public function getHelp();

  /**
   * Gets the description.
   *
   * @return string
   *   The description of this mailing list.
   */
  public function getDescription();

  /**
   * Gets the maximum number of subscriptions per user.
   *
   * @return int
   *   The limit value.
   */
  public function getLimitByUser();

  /**
   * Sets the maximum number of subscriptions per user.
   *
   * @param int $limit
   *   The limit value.
   */
  public function setLimitByUser($limit);

  /**
   * Gets the inactive subscriptions lifetime for this mailing list.
   *
   * @return int
   *   Max inactive subscriptions lifetime in seconds.
   */
  public function getInactiveLifetime();

  /**
   * Sets the inactive subscriptions lifetime for this mailing list.
   *
   * @param int $time
   *   Max inactive subscriptions lifetime in seconds.
   */
  public function setInactiveLifetime($time);

  /**
   * Gets the maximum number of subscriptions per email address.
   *
   * @return int
   *   The limit value.
   */
  public function getLimitByEmail();

  /**
   * Sets the maximum number of subscriptions per email address.
   *
   * @param int $limit
   *   The limit value.
   */
  public function setLimitByEmail($limit);

  /**
   * Check if subscription cross access is allowed for this mailing list.
   *
   * @return bool
   *   TRUE when user cross access is allowd, FALSE otherwise.
   */
  public function isCrossAccessAllowed();

  /**
   * Gets the subscription confirmation message.
   *
   * @return string
   *   The confirmation message.
   */
  public function getOnSubscriptionMessage();

  /**
   * Sets the subscription confirmation message.
   *
   * @param string $message
   *   The new confirmation message.
   */
  public function setOnSubscriptionMessage($message);

  /**
   * Gets the cancellation message.
   *
   * @return string
   *   The cancellation message.
   */
  public function getOnCancellationMessage();

  /**
   * Sets the subscription cancellation message.
   *
   * @param string $message
   *   The new cancellation message.
   */
  public function setOnCancellationMessage($message);

  /**
   * Gets the subscription form destination config option.
   *
   * @return string
   *   The form destination.
   */
  public function getFormDestination();

  /**
   * Sets the subscription form destination config option.
   *
   * @param string $destination
   *   The form destination.
   */
  public function setFormDestination($destination);

}
