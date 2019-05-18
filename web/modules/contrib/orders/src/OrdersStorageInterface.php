<?php

namespace Drupal\orders;

use Drupal\Core\Entity\ContentEntityStorageInterface;
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
interface OrdersStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets a list of Orders revision IDs for a specific Orders.
   *
   * @param \Drupal\orders\Entity\OrdersInterface $entity
   *   The Orders entity.
   *
   * @return int[]
   *   Orders revision IDs (in ascending order).
   */
  public function revisionIds(OrdersInterface $entity);

  /**
   * Gets a list of revision IDs having a given user as Orders author.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity.
   *
   * @return int[]
   *   Orders revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account);

  /**
   * Counts the number of revisions in the default language.
   *
   * @param \Drupal\orders\Entity\OrdersInterface $entity
   *   The Orders entity.
   *
   * @return int
   *   The number of revisions in the default language.
   */
  public function countDefaultLanguageRevisions(OrdersInterface $entity);

  /**
   * Unsets the language for all Orders with the given language.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language object.
   */
  public function clearRevisionsLanguage(LanguageInterface $language);

}
