<?php

namespace Drupal\orders;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\orders\Entity\OrdersInterface;

/**
 * Defines the storage handler class for Orders entities.
 *
 * This extends the base storage class, adding required special handling for
 * Orders entities.
 *
 * @ingroup orders
 */
class OrdersStorage extends SqlContentEntityStorage implements OrdersStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function revisionIds(OrdersInterface $entity) {
    return $this->database->query(
      'SELECT vid FROM {orders_revision} WHERE id=:id ORDER BY vid',
      [':id' => $entity->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function userRevisionIds(AccountInterface $account) {
    return $this->database->query(
      'SELECT vid FROM {orders_field_revision} WHERE uid = :uid ORDER BY vid',
      [':uid' => $account->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function countDefaultLanguageRevisions(OrdersInterface $entity) {
    return $this->database->query('SELECT COUNT(*) FROM {orders_field_revision} WHERE id = :id AND default_langcode = 1', [':id' => $entity->id()])
      ->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function clearRevisionsLanguage(LanguageInterface $language) {
    return $this->database->update('orders_revision')
      ->fields(['langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED])
      ->condition('langcode', $language->getId())
      ->execute();
  }

}
