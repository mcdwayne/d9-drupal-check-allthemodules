<?php

namespace Drupal\visualn_dataset;

use Drupal\Core\Entity\ContentEntityStorageInterface;
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
interface VisualNDataSetStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets a list of VisualN Data Set revision IDs for a specific VisualN Data Set.
   *
   * @param \Drupal\visualn_dataset\Entity\VisualNDataSetInterface $entity
   *   The VisualN Data Set entity.
   *
   * @return int[]
   *   VisualN Data Set revision IDs (in ascending order).
   */
  public function revisionIds(VisualNDataSetInterface $entity);

  /**
   * Gets a list of revision IDs having a given user as VisualN Data Set author.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity.
   *
   * @return int[]
   *   VisualN Data Set revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account);

  /**
   * Counts the number of revisions in the default language.
   *
   * @param \Drupal\visualn_dataset\Entity\VisualNDataSetInterface $entity
   *   The VisualN Data Set entity.
   *
   * @return int
   *   The number of revisions in the default language.
   */
  public function countDefaultLanguageRevisions(VisualNDataSetInterface $entity);

  /**
   * Unsets the language for all VisualN Data Set with the given language.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language object.
   */
  public function clearRevisionsLanguage(LanguageInterface $language);

}
