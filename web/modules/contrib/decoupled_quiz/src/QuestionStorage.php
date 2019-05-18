<?php

namespace Drupal\decoupled_quiz;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\decoupled_quiz\Entity\QuestionInterface;

/**
 * Defines the storage handler class for Question entities.
 *
 * This extends the base storage class, adding required special handling for
 * Question entities.
 *
 * @ingroup decoupled_quiz
 */
class QuestionStorage extends SqlContentEntityStorage implements QuestionStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function revisionIds(QuestionInterface $entity) {
    return $this->database->query(
      'SELECT vid FROM {question_revision} WHERE id=:id ORDER BY vid',
      [':id' => $entity->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function userRevisionIds(AccountInterface $account) {
    return $this->database->query(
      'SELECT vid FROM {question_field_revision} WHERE uid = :uid ORDER BY vid',
      [':uid' => $account->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function countDefaultLanguageRevisions(QuestionInterface $entity) {
    return $this->database->query('SELECT COUNT(*) FROM {question_field_revision} WHERE id = :id AND default_langcode = 1', [':id' => $entity->id()])
      ->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function clearRevisionsLanguage(LanguageInterface $language) {
    return $this->database->update('question_revision')
      ->fields(['langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED])
      ->condition('langcode', $language->getId())
      ->execute();
  }

}
