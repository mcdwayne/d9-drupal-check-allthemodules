<?php

namespace Drupal\decoupled_quiz;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\decoupled_quiz\Entity\ResultInterface;

/**
 * Defines the storage handler class for Result entities.
 *
 * This extends the base storage class, adding required special handling for
 * Result entities.
 *
 * @ingroup decoupled_quiz
 */
class ResultStorage extends SqlContentEntityStorage implements ResultStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function revisionIds(ResultInterface $entity) {
    return $this->database->query(
      'SELECT vid FROM {result_revision} WHERE id=:id ORDER BY vid',
      [':id' => $entity->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function userRevisionIds(AccountInterface $account) {
    return $this->database->query(
      'SELECT vid FROM {result_field_revision} WHERE uid = :uid ORDER BY vid',
      [':uid' => $account->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function countDefaultLanguageRevisions(ResultInterface $entity) {
    return $this->database->query('SELECT COUNT(*) FROM {result_field_revision} WHERE id = :id AND default_langcode = 1', [':id' => $entity->id()])
      ->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function clearRevisionsLanguage(LanguageInterface $language) {
    return $this->database->update('result_revision')
      ->fields(['langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED])
      ->condition('langcode', $language->getId())
      ->execute();
  }

}
