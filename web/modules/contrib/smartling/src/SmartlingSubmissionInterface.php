<?php

/**
 * @file
 * Contains \Drupal\smartling\SmartlingSubmissionInterface.
 */

namespace Drupal\smartling;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use \Drupal\Core\Entity\EntityInterface;

/**
 * Provides an interface defining a smartling submission entity.
 */
interface SmartlingSubmissionInterface extends ContentEntityInterface, EntityChangedInterface {

  /**
   * Submission queued.
   */
  const QUEUED = 0;

  /**
   * Submission in progress of translation.
   */
  const TRANSLATING = 1;

  /**
   * Submission is translated and could be downloaded.
   */
  const TRANSLATED = 2;

  /**
   * The related entity changed, submission should be uploaded again.
   */
  const CHANGED = 3;

  /**
   * Last action on submission failed.
   */
  const FAILED = 4;

  /**
   * Returns the submission's status.
   *
   * @return int
   *   One of SmartlingSubmissionInterface::QUEUED
   *   or SmartlingSubmissionInterface::TRANSLATING
   *   or SmartlingSubmissionInterface::TRANSLATED
   *   or SmartlingSubmissionInterface::CHANGED
   *   or SmartlingSubmissionInterface::FAILED
   */
  public function getStatus();

  /**
   * Returns the file name for submission.
   *
   * Generated new when file_name field is empty.
   *
   * @return string
   *   The file name.
   */
  public function getFileName();

  /**
   * Generates file name for the entity.
   *
   * @return string
   *   The generated file name.
   */
  public function generateFileName();

  /**
   * Sets status of translation.
   *
   * @param int $event
   *   One of SMARTLING_STATUS_EVENT_SEND_TO_UPLOAD_QUEUE
   *   or SMARTLING_STATUS_EVENT_FAILED_UPLOAD
   *   or SMARTLING_STATUS_EVENT_UPLOAD_TO_SERVICE
   *   or SMARTLING_STATUS_EVENT_DOWNLOAD_FROM_SERVICE
   *   or SMARTLING_STATUS_EVENT_UPDATE_FIELDS
   *   or SMARTLING_STATUS_EVENT_NODE_ENTITY_UPDATE
   *
   * @return $this
   */
  public function setStatusByEvent($event);

  /**
   * Returns entity submitted for translation.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface
   */
  public function getRelatedEntity();

  /**
   * Loads or creates new entity for the given content entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The content entity.
   * @param string $target_langcode
   *   A language to translate.
   *
   * @return \Drupal\smartling\SmartlingSubmissionInterface
   *   Unsaved entity with regenerated file name.
   */
  public static function getFromDrupalEntity(EntityInterface $entity, $target_langcode);

  /**
   * Load all entities using some conditions.
   *
   * @param array $conditions
   *
   * @return \Drupal\smartling\SmartlingSubmissionInterface[]
   */
  public static function loadMultipleByConditions(array $conditions);

}
