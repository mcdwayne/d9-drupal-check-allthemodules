<?php

namespace Drupal\workflow_task;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
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
class WorkflowTaskStorage extends SqlContentEntityStorage implements WorkflowTaskStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function revisionIds(WorkflowTaskInterface $entity) {
    return $this->database->query(
      'SELECT vid FROM {workflow_task_revision} WHERE id=:id ORDER BY vid',
      [':id' => $entity->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function userRevisionIds(AccountInterface $account) {
    return $this->database->query(
      'SELECT vid FROM {workflow_task_field_revision} WHERE uid = :uid ORDER BY vid',
      [':uid' => $account->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function countDefaultLanguageRevisions(WorkflowTaskInterface $entity) {
    return $this->database->query('SELECT COUNT(*) FROM {workflow_task_field_revision} WHERE id = :id AND default_langcode = 1', [':id' => $entity->id()])
      ->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function clearRevisionsLanguage(LanguageInterface $language) {
    return $this->database->update('workflow_task_revision')
      ->fields(['langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED])
      ->condition('langcode', $language->getId())
      ->execute();
  }

}
