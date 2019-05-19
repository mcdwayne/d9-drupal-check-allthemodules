<?php

namespace Drupal\task_note;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
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
class TaskNoteStorage extends SqlContentEntityStorage implements TaskNoteStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function revisionIds(TaskNoteInterface $entity) {
    return $this->database->query(
      'SELECT vid FROM {task_note_revision} WHERE id=:id ORDER BY vid',
      [':id' => $entity->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function userRevisionIds(AccountInterface $account) {
    return $this->database->query(
      'SELECT vid FROM {task_note_field_revision} WHERE uid = :uid ORDER BY vid',
      [':uid' => $account->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function countDefaultLanguageRevisions(TaskNoteInterface $entity) {
    return $this->database->query('SELECT COUNT(*) FROM {task_note_field_revision} WHERE id = :id AND default_langcode = 1', [':id' => $entity->id()])
      ->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function clearRevisionsLanguage(LanguageInterface $language) {
    return $this->database->update('task_note_revision')
      ->fields(['langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED])
      ->condition('langcode', $language->getId())
      ->execute();
  }

}
