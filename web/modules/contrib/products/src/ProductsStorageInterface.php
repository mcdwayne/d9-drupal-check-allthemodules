<?php

namespace Drupal\products;

use Drupal\Core\Entity\ContentEntityStorageInterface;
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
interface ProductsStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets a list of Products revision IDs for a specific Products.
   *
   * @param \Drupal\products\Entity\ProductsInterface $entity
   *   The Products entity.
   *
   * @return int[]
   *   Products revision IDs (in ascending order).
   */
  public function revisionIds(ProductsInterface $entity);

  /**
   * Gets a list of revision IDs having a given user as Products author.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity.
   *
   * @return int[]
   *   Products revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account);

  /**
   * Counts the number of revisions in the default language.
   *
   * @param \Drupal\products\Entity\ProductsInterface $entity
   *   The Products entity.
   *
   * @return int
   *   The number of revisions in the default language.
   */
  public function countDefaultLanguageRevisions(ProductsInterface $entity);

  /**
   * Unsets the language for all Products with the given language.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language object.
   */
  public function clearRevisionsLanguage(LanguageInterface $language);

}
