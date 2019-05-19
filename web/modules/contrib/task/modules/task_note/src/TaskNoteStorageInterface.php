<?php

namespace Drupal\task_note;

use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\task_note\Entity\TaskNoteInterface;

/**
 * Defines the storage handler class for Task Note entities.
 *
 * This extends the base storage class, adding required special handling for
 * Task Note entities.
 *
 * @ingroup task_note
 */
interface TaskNoteStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets a list of Task Note revision IDs for a specific Task Note.
   *
   * @param \Drupal\task_note\Entity\TaskNoteInterface $entity
   *   The Task Note entity.
   *
   * @return int[]
   *   Task Note revision IDs (in ascending order).
   */
  public function revisionIds(TaskNoteInterface $entity);

  /**
   * Gets a list of revision IDs having a given user as Task Note author.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity.
   *
   * @return int[]
   *   Task Note revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account);

  /**
   * Counts the number of revisions in the default language.
   *
   * @param \Drupal\task_note\Entity\TaskNoteInterface $entity
   *   The Task Note entity.
   *
   * @return int
   *   The number of revisions in the default language.
   */
  public function countDefaultLanguageRevisions(TaskNoteInterface $entity);

  /**
   * Unsets the language for all Task Note with the given language.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language object.
   */
  public function clearRevisionsLanguage(LanguageInterface $language);

}
