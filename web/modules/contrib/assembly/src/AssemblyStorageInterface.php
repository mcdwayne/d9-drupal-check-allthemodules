<?php

namespace Drupal\assembly;

use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\assembly\Entity\AssemblyInterface;

/**
 * Defines the storage handler class for Assembly entities.
 *
 * This extends the base storage class, adding required special handling for
 * Assembly entities.
 *
 * @ingroup assembly
 */
interface AssemblyStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets a list of Assembly revision IDs for a specific Assembly.
   *
   * @param \Drupal\assembly\Entity\AssemblyInterface $entity
   *   The Assembly entity.
   *
   * @return int[]
   *   Assembly revision IDs (in ascending order).
   */
  public function revisionIds(AssemblyInterface $entity);

  /**
   * Gets a list of revision IDs having a given user as Assembly author.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity.
   *
   * @return int[]
   *   Assembly revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account);

  /**
   * Counts the number of revisions in the default language.
   *
   * @param \Drupal\assembly\Entity\AssemblyInterface $entity
   *   The Assembly entity.
   *
   * @return int
   *   The number of revisions in the default language.
   */
  public function countDefaultLanguageRevisions(AssemblyInterface $entity);

  /**
   * Unsets the language for all Assembly with the given language.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language object.
   */
  public function clearRevisionsLanguage(LanguageInterface $language);

}
