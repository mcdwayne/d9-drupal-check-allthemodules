<?php

namespace Drupal\entity_generic;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;

/**
 * Defines the storage handler class for entities.
 *
 * This extends the base storage class, adding required special handling for entities.
 */
class GenericStorage extends SqlContentEntityStorage implements GenericStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function revisionIds(ContentEntityInterface $entity) {
    return $this->database->query(
      'SELECT vid FROM {' . $this->getRevisionTable() . '} WHERE ' . $this->getEntityType()->getKey('id') . '=:id ORDER BY vid',
      [':id' => $entity->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function userRevisionIds(AccountInterface $account) {
    return $this->database->query(
      'SELECT vid FROM {' . $this->getRevisionDataTable() . '} WHERE ' . $this->getEntityType()->getKey('uid') . ' = :uid ORDER BY vid',
      [':uid' => $account->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function countDefaultLanguageRevisions(ContentEntityInterface $entity) {
    return $this->database->query('SELECT COUNT(*) FROM {' . $this->getRevisionDataTable() . '} WHERE ' . $this->getEntityType()->getKey('id') . ' = :id AND default_langcode = 1', [':id' => $entity->id()])->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function updateType($old_type, $new_type) {
    return $this->database->update($this->getBaseTable())
      ->fields(['type' => $new_type])
      ->condition('type', $old_type)
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function clearRevisionsLanguage(LanguageInterface $language) {
    return $this->database->update($this->getRevisionTable())
      ->fields(['langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED])
      ->condition('langcode', $language->getId())
      ->execute();
  }

}
