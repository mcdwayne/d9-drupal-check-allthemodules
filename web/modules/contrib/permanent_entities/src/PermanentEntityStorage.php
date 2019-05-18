<?php

namespace Drupal\permanent_entities;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\permanent_entities\Entity\PermanentEntityInterface;

/**
 * Defines the storage handler class for Permanent Entity entities.
 *
 * This extends the base storage class, adding required special handling for
 * Permanent Entity entities.
 *
 * @ingroup permanent_entities
 */
class PermanentEntityStorage extends SqlContentEntityStorage implements PermanentEntityStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function revisionIds(PermanentEntityInterface $entity) {
    return $this->database->query(
      'SELECT vid FROM {permanent_entity_revision} WHERE id=:id ORDER BY vid',
      [':id' => $entity->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function userRevisionIds(AccountInterface $account) {
    return $this->database->query(
      'SELECT vid FROM {permanent_entity_field_revision} WHERE uid = :uid ORDER BY vid',
      [':uid' => $account->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function countDefaultLanguageRevisions(PermanentEntityInterface $entity) {
    return $this->database->query('SELECT COUNT(*) FROM {permanent_entity_field_revision} WHERE id = :id AND default_langcode = 1', [':id' => $entity->id()])
      ->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function clearRevisionsLanguage(LanguageInterface $language) {
    return $this->database->update('permanent_entity_revision')
      ->fields(['langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED])
      ->condition('langcode', $language->getId())
      ->execute();
  }

}
