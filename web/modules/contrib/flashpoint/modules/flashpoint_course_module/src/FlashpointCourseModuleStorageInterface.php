<?php

namespace Drupal\flashpoint_course_module;

use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\flashpoint_course_module\Entity\FlashpointCourseModuleInterface;

/**
 * Defines the storage handler class for Course module entities.
 *
 * This extends the base storage class, adding required special handling for
 * Course module entities.
 *
 * @ingroup flashpoint
 */
interface FlashpointCourseModuleStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets a list of Course module revision IDs for a specific Course module.
   *
   * @param \Drupal\flashpoint_course_module\Entity\FlashpointCourseModuleInterface $entity
   *   The Course module entity.
   *
   * @return int[]
   *   Course module revision IDs (in ascending order).
   */
  public function revisionIds(FlashpointCourseModuleInterface $entity);

  /**
   * Gets a list of revision IDs having a given user as Course module author.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity.
   *
   * @return int[]
   *   Course module revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account);

  /**
   * Counts the number of revisions in the default language.
   *
   * @param \Drupal\flashpoint_course_module\Entity\FlashpointCourseModuleInterface $entity
   *   The Course module entity.
   *
   * @return int
   *   The number of revisions in the default language.
   */
  public function countDefaultLanguageRevisions(FlashpointCourseModuleInterface $entity);

  /**
   * Unsets the language for all Course module with the given language.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language object.
   */
  public function clearRevisionsLanguage(LanguageInterface $language);

}
