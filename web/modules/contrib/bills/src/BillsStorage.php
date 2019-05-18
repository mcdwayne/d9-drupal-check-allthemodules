<?php

namespace Drupal\bills;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\bills\Entity\BillsInterface;

/**
 * Defines the storage handler class for Bills entities.
 *
 * This extends the base storage class, adding required special handling for
 * Bills entities.
 *
 * @ingroup bills
 */
class BillsStorage extends SqlContentEntityStorage implements BillsStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function revisionIds(BillsInterface $entity) {
    return $this->database->query(
      'SELECT vid FROM {bills_revision} WHERE id=:id ORDER BY vid',
      [':id' => $entity->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function userRevisionIds(AccountInterface $account) {
    return $this->database->query(
      'SELECT vid FROM {bills_field_revision} WHERE uid = :uid ORDER BY vid',
      [':uid' => $account->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function countDefaultLanguageRevisions(BillsInterface $entity) {
    return $this->database->query('SELECT COUNT(*) FROM {bills_field_revision} WHERE id = :id AND default_langcode = 1', [':id' => $entity->id()])
      ->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function clearRevisionsLanguage(LanguageInterface $language) {
    return $this->database->update('bills_revision')
      ->fields(['langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED])
      ->condition('langcode', $language->getId())
      ->execute();
  }

}
