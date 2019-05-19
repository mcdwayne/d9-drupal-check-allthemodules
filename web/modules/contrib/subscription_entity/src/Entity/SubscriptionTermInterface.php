<?php

namespace Drupal\subscription_entity\Entity;

use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Subscription Term entities.
 *
 * @ingroup subscription
 */
interface SubscriptionTermInterface extends RevisionableInterface, RevisionLogInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the Subscription Term type.
   *
   * @return string
   *   The Subscription Term type.
   */
  public function getType();

  /**
   * Gets the Subscription Term creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Subscription Term.
   */
  public function getCreatedTime();

  /**
   * Sets the Subscription Term creation timestamp.
   *
   * @param int $timestamp
   *   The Subscription Term creation timestamp.
   *
   * @return \Drupal\subscription_entity\Entity\SubscriptionTermInterface
   *   The called Subscription Term entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Subscription Term published status indicator.
   *
   * Unpublished Subscription Term are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Subscription Term is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Subscription Term.
   *
   * @param bool $published
   *   TRUE to set this Subscription Term to published
   *   FALSE to set it to unpublished.
   *
   * @return \Drupal\subscription_entity\Entity\SubscriptionTermInterface
   *   The called Subscription Term entity.
   */
  public function setPublished($published);

  /**
   * Gets the Subscription Term revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the Subscription Term revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return \Drupal\subscription_entity\Entity\SubscriptionTermInterface
   *   The called Subscription Term entity.
   */
  public function setRevisionCreationTime($timestamp);

  /**
   * Gets the Subscription Term revision author.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity for the revision author.
   */
  public function getRevisionUser();

  /**
   * Sets the Subscription Term revision author.
   *
   * @param int $uid
   *   The user ID of the revision author.
   *
   * @return \Drupal\subscription_entity\Entity\SubscriptionTermInterface
   *   The called Subscription Term entity.
   */
  public function setRevisionUserId($uid);

  /**
   * Sets the $subscriptionEntityId property.
   *
   * @param int $subscriptionEntityId
   *   The unique identifier.
   *
   * @return $this
   */
  public function setSubscriptionEntityId($subscriptionEntityId);

  /**
   * Load the corresponding subscription associated to the term.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   Returns the subscription based on the term object.
   */
  public function loadSubscriptionByTerm();

  /**
   * Gets the related subscription entity id.
   */
  public function getSubscriptionEntityId();

  /**
   * Set the start date for a term.
   *
   * @param string $startDate
   *   A date string.
   */
  public function setStartDate($startDate);

  /**
   * Gets the start date for a term.
   *
   * @return mixed
   *   The start date as a string.
   */
  public function getStartDate();

  /**
   * Set the end date for a term.
   *
   * @param string $endDate
   *   A date string.
   */
  public function setEndDate($endDate);

  /**
   * Gets the end date for a term.
   *
   * @return mixed
   *   The end date as a string.
   */
  public function getEndDate();

  /**
   * Method checks to see if we have an active term.
   *
   * @return bool
   *   Whether or not the term is active.
   */
  public function isActiveTerm();

  /**
   * Activates the term.
   */
  public function activateTerm();

  /**
   * Deactivates the term.
   */
  public function deActivateTerm();

}
