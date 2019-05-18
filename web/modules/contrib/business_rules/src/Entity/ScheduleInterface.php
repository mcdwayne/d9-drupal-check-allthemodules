<?php

namespace Drupal\business_rules\Entity;

use Drupal\business_rules\Events\BusinessRulesEvent;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Schedule entities.
 *
 * @ingroup business_rules
 */
interface ScheduleInterface extends ContentEntityInterface, RevisionLogInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the Schedule name.
   *
   * @return string
   *   Name of the Schedule.
   */
  public function getName();

  /**
   * Sets the Schedule name.
   *
   * @param string $name
   *   The Schedule name.
   *
   * @return \Drupal\business_rules\Entity\ScheduleInterface
   *   The called Schedule entity.
   */
  public function setName($name);

  /**
   * Gets the Schedule creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Schedule.
   */
  public function getCreatedTime();

  /**
   * Sets the Schedule creation timestamp.
   *
   * @param int $timestamp
   *   The Schedule creation timestamp.
   *
   * @return \Drupal\business_rules\Entity\ScheduleInterface
   *   The called Schedule entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Schedule executed status indicator.
   *
   * @return bool
   *   TRUE if the Schedule was executed.
   */
  public function isExecuted();

  /**
   * Sets the executed status of a Schedule.
   *
   * @param bool $executed
   *   TRUE to set this Schedule to executed, FALSE to set it to non-executed.
   *
   * @return \Drupal\business_rules\Entity\ScheduleInterface
   *   The called Schedule entity.
   */
  public function setExecuted($executed);

  /**
   * Gets the Schedule revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the Schedule revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return \Drupal\business_rules\Entity\ScheduleInterface
   *   The called Schedule entity.
   */
  public function setRevisionCreationTime($timestamp);

  /**
   * Gets the Schedule revision author.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity for the revision author.
   */
  public function getRevisionUser();

  /**
   * Sets the Schedule revision author.
   *
   * @param int $uid
   *   The user ID of the revision author.
   *
   * @return \Drupal\business_rules\Entity\ScheduleInterface
   *   The called Schedule entity.
   */
  public function setRevisionUserId($uid);

  /**
   * Gets the Schedule description.
   *
   * @return string
   *   The Schedule description.
   */
  public function getDescription();

  /**
   * Sets ths Schedule description.
   *
   * @param string $description
   *   The schedule descripion for information porpouses.
   *
   * @return \Drupal\business_rules\Entity\ScheduleInterface
   *   The called Schedule entity.
   */
  public function setDescription($description);

  /**
   * Gets the Schedule scheduled timestamp.
   *
   * @return int
   *   Creation timestamp that it's scheduled.
   */
  public function getScheduled();

  /**
   * Sets the Schedule scheduled timestamp.
   *
   * @param int $timestamp
   *   The Schedule scheduled timestamp.
   *
   * @return \Drupal\business_rules\Entity\ScheduleInterface
   *   The called Schedule entity.
   */
  public function setScheduled($timestamp);

  /**
   * Get the Business Rule which has triggered this schedule.
   *
   * @return \Drupal\business_rules\Entity\BusinessRule|null
   *   The Business Rule or null.
   */
  public function getTriggeredBy();

  /**
   * Set the Business Rule which has triggered this schedule.
   *
   * @param \Drupal\business_rules\Entity\BusinessRulesItemBase $businessRuleItem
   *   The Business Rule item.
   *
   * @return \Drupal\business_rules\Entity\ScheduleInterface
   *   The called Schedule entity.
   */
  public function setTriggeredBy(BusinessRulesItemBase $businessRuleItem);

  /**
   * Load an schedule based on name and triggered by item.
   *
   * @param string $name
   *   The schedule name.
   * @param string $triggeredBy
   *   The triggered by Business Rule item.
   *
   * @return \Drupal\business_rules\Entity\Schedule
   *   The schedule entity.
   */
  public static function loadByNameAndTriggeredBy($name, $triggeredBy);

  /**
   * Execute the scheduled tasks.
   *
   * @param \Drupal\business_rules\Events\BusinessRulesEvent $event
   *   The cron event.
   */
  public static function executeSchedule(BusinessRulesEvent $event);

  /**
   * Set the event.
   *
   * @param \Drupal\business_rules\Events\BusinessRulesEvent $event
   *   The event that has created the schedule.
   */
  public function setEvent(BusinessRulesEvent $event);

  /**
   * Get the event.
   *
   * @return \Drupal\business_rules\Events\BusinessRulesEvent
   *   The event that has created the schedule.
   */
  public function getEvent();

  /**
   * Set if it's to update the entity at the end of the task.
   *
   * @param bool $update
   *   True or False.
   */
  public function setUpdateEntity(bool $update);

  /**
   * Get is it's to update the entity at the end of the task.
   *
   * @return bool
   *   True or false.
   */
  public function getUpdateEntity();

}
