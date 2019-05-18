<?php

namespace Drupal\bills;

use Drupal\Core\Entity\ContentEntityStorageInterface;
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
interface BillsStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets a list of Bills revision IDs for a specific Bills.
   *
   * @param \Drupal\bills\Entity\BillsInterface $entity
   *   The Bills entity.
   *
   * @return int[]
   *   Bills revision IDs (in ascending order).
   */
  public function revisionIds(BillsInterface $entity);

  /**
   * Gets a list of revision IDs having a given user as Bills author.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity.
   *
   * @return int[]
   *   Bills revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account);

  /**
   * Counts the number of revisions in the default language.
   *
   * @param \Drupal\bills\Entity\BillsInterface $entity
   *   The Bills entity.
   *
   * @return int
   *   The number of revisions in the default language.
   */
  public function countDefaultLanguageRevisions(BillsInterface $entity);

  /**
   * Unsets the language for all Bills with the given language.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language object.
   */
  public function clearRevisionsLanguage(LanguageInterface $language);

}
