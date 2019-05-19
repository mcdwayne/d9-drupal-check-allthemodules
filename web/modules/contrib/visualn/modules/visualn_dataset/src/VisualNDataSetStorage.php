<?php

namespace Drupal\visualn_dataset;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\visualn_dataset\Entity\VisualNDataSetInterface;

/**
 * Defines the storage handler class for VisualN Data Set entities.
 *
 * This extends the base storage class, adding required special handling for
 * VisualN Data Set entities.
 *
 * @ingroup visualn_dataset
 */
class VisualNDataSetStorage extends SqlContentEntityStorage implements VisualNDataSetStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function revisionIds(VisualNDataSetInterface $entity) {
    return $this->database->query(
      'SELECT vid FROM {visualn_dataset_revision} WHERE id=:id ORDER BY vid',
      [':id' => $entity->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function userRevisionIds(AccountInterface $account) {
    return $this->database->query(
      'SELECT vid FROM {visualn_dataset_field_revision} WHERE uid = :uid ORDER BY vid',
      [':uid' => $account->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function countDefaultLanguageRevisions(VisualNDataSetInterface $entity) {
    return $this->database->query('SELECT COUNT(*) FROM {visualn_dataset_field_revision} WHERE id = :id AND default_langcode = 1', [':id' => $entity->id()])
      ->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function clearRevisionsLanguage(LanguageInterface $language) {
    return $this->database->update('visualn_dataset_revision')
      ->fields(['langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED])
      ->condition('langcode', $language->getId())
      ->execute();
  }

}
