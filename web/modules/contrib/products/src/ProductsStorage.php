<?php

namespace Drupal\products;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\products\Entity\ProductsInterface;

/**
 * Defines the storage handler class for Products entities.
 *
 * This extends the base storage class, adding required special handling for
 * Products entities.
 *
 * @ingroup products
 */
class ProductsStorage extends SqlContentEntityStorage implements ProductsStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function revisionIds(ProductsInterface $entity) {
    return $this->database->query(
      'SELECT vid FROM {products_revision} WHERE id=:id ORDER BY vid',
      [':id' => $entity->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function userRevisionIds(AccountInterface $account) {
    return $this->database->query(
      'SELECT vid FROM {products_field_revision} WHERE uid = :uid ORDER BY vid',
      [':uid' => $account->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function countDefaultLanguageRevisions(ProductsInterface $entity) {
    return $this->database->query('SELECT COUNT(*) FROM {products_field_revision} WHERE id = :id AND default_langcode = 1', [':id' => $entity->id()])
      ->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function clearRevisionsLanguage(LanguageInterface $language) {
    return $this->database->update('products_revision')
      ->fields(['langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED])
      ->condition('langcode', $language->getId())
      ->execute();
  }

}
