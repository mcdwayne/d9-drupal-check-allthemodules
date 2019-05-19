<?php

namespace Drupal\white_label_entity;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\white_label_entity\Entity\WhileEntityInterface;

/**
 * Defines the storage handler class for while entities.
 *
 * This extends the base storage class, adding required special handling for
 * while entities.
 *
 * @ingroup while
 */
class WhileEntityStorage extends SqlContentEntityStorage implements WhileEntityStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function revisionIds(WhileEntityInterface $entity) {
    return $this->database->query(
      'SELECT vid FROM {while_entity_revision} WHERE id=:id ORDER BY vid',
      [':id' => $entity->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function userRevisionIds(AccountInterface $account) {
    return $this->database->query(
      'SELECT vid FROM {while_entity_field_revision} WHERE uid = :uid ORDER BY vid',
      [':uid' => $account->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function countDefaultLanguageRevisions(WhileEntityInterface $entity) {
    return $this->database->query('SELECT COUNT(*) FROM {while_entity_field_revision} WHERE id = :id AND default_langcode = 1', [':id' => $entity->id()])
      ->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function clearRevisionsLanguage(LanguageInterface $language) {
    return $this->database->update('while_entity_revision')
      ->fields(['langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED])
      ->condition('langcode', $language->getId())
      ->execute();
  }

}
