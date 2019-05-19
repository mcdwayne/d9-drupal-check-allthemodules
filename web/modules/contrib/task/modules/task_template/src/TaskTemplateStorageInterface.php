<?php

namespace Drupal\task_template;

use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\task_template\Entity\TaskTemplateInterface;

/**
 * Defines the storage handler class for Task Template entities.
 *
 * This extends the base storage class, adding required special handling for
 * Task Template entities.
 *
 * @ingroup task_template
 */
interface TaskTemplateStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets a list of Task Template revision IDs for a specific Task Template.
   *
   * @param \Drupal\task_template\Entity\TaskTemplateInterface $entity
   *   The Task Template entity.
   *
   * @return int[]
   *   Task Template revision IDs (in ascending order).
   */
  public function revisionIds(TaskTemplateInterface $entity);

  /**
   * Gets a list of revision IDs having a given user as Task Template author.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity.
   *
   * @return int[]
   *   Task Template revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account);

  /**
   * Counts the number of revisions in the default language.
   *
   * @param \Drupal\task_template\Entity\TaskTemplateInterface $entity
   *   The Task Template entity.
   *
   * @return int
   *   The number of revisions in the default language.
   */
  public function countDefaultLanguageRevisions(TaskTemplateInterface $entity);

  /**
   * Unsets the language for all Task Template with the given language.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language object.
   */
  public function clearRevisionsLanguage(LanguageInterface $language);

}
