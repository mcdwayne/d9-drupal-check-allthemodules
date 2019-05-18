<?php

namespace Drupal\entity_collector;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\entity_collector\Entity\EntityCollectionInterface;

/**
 * Defines the storage handler class for Entity collection entities.
 *
 * This extends the base storage class, adding required special handling for
 * Entity collection entities.
 *
 * @ingroup entity_collector
 */
class EntityCollectionStorage extends SqlContentEntityStorage implements EntityCollectionStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function revisionIds(EntityCollectionInterface $entity) {
    return $this->database->query(
      'SELECT vid FROM {entity_collection_revision} WHERE id=:id ORDER BY vid',
      [':id' => $entity->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function userRevisionIds(AccountInterface $account) {
    return $this->database->query(
      'SELECT vid FROM {entity_collection_field_revision} WHERE uid = :uid ORDER BY vid',
      [':uid' => $account->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function countDefaultLanguageRevisions(EntityCollectionInterface $entity) {
    return $this->database->query('SELECT COUNT(*) FROM {entity_collection_field_revision} WHERE id = :id AND default_langcode = 1', [':id' => $entity->id()])
      ->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function clearRevisionsLanguage(LanguageInterface $language) {
    return $this->database->update('entity_collection_revision')
      ->fields(['langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED])
      ->condition('langcode', $language->getId())
      ->execute();
  }

}
