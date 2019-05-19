<?php

namespace Drupal\workflow_task;

use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\workflow_task\Entity\WorkflowTaskInterface;

/**
 * Defines the storage handler class for Workflow task entities.
 *
 * This extends the base storage class, adding required special handling for
 * Workflow task entities.
 *
 * @ingroup workflow_task
 */
interface WorkflowTaskStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets a list of Workflow task revision IDs for a specific Workflow task.
   *
   * @param \Drupal\workflow_task\Entity\WorkflowTaskInterface $entity
   *   The Workflow task entity.
   *
   * @return int[]
   *   Workflow task revision IDs (in ascending order).
   */
  public function revisionIds(WorkflowTaskInterface $entity);

  /**
   * Gets a list of revision IDs having a given user as Workflow task author.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity.
   *
   * @return int[]
   *   Workflow task revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account);

  /**
   * Counts the number of revisions in the default language.
   *
   * @param \Drupal\workflow_task\Entity\WorkflowTaskInterface $entity
   *   The Workflow task entity.
   *
   * @return int
   *   The number of revisions in the default language.
   */
  public function countDefaultLanguageRevisions(WorkflowTaskInterface $entity);

  /**
   * Unsets the language for all Workflow task with the given language.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language object.
   */
  public function clearRevisionsLanguage(LanguageInterface $language);

}
