<?php

namespace Drupal\decoupled_quiz;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\decoupled_quiz\Entity\AnswerInterface;

/**
 * Defines the storage handler class for Answer entities.
 *
 * This extends the base storage class, adding required special handling for
 * Answer entities.
 *
 * @ingroup decoupled_quiz
 */
class AnswerStorage extends SqlContentEntityStorage implements AnswerStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function revisionIds(AnswerInterface $entity) {
    return $this->database->query(
      'SELECT vid FROM {answer_revision} WHERE id=:id ORDER BY vid',
      [':id' => $entity->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function userRevisionIds(AccountInterface $account) {
    return $this->database->query(
      'SELECT vid FROM {answer_field_revision} WHERE uid = :uid ORDER BY vid',
      [':uid' => $account->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function countDefaultLanguageRevisions(AnswerInterface $entity) {
    return $this->database->query('SELECT COUNT(*) FROM {answer_field_revision} WHERE id = :id AND default_langcode = 1', [':id' => $entity->id()])
      ->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function clearRevisionsLanguage(LanguageInterface $language) {
    return $this->database->update('answer_revision')
      ->fields(['langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED])
      ->condition('langcode', $language->getId())
      ->execute();
  }

}
