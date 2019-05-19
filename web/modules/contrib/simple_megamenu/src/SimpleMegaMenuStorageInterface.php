<?php

namespace Drupal\simple_megamenu;

use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\simple_megamenu\Entity\SimpleMegaMenuInterface;

/**
 * Defines the storage handler class for Simple mega menu entities.
 *
 * This extends the base storage class, adding required special handling for
 * Simple mega menu entities.
 *
 * @ingroup simple_megamenu
 */
interface SimpleMegaMenuStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets a list of Simple megamenu revision IDs for a specific Simple megamenu.
   *
   * @param \Drupal\simple_megamenu\Entity\SimpleMegaMenuInterface $entity
   *   The Simple mega menu entity.
   *
   * @return int[]
   *   Simple mega menu revision IDs (in ascending order).
   */
  public function revisionIds(SimpleMegaMenuInterface $entity);

  /**
   * Gets a list of revision IDs having a given user as Simple mega menu author.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity.
   *
   * @return int[]
   *   Simple mega menu revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account);

  /**
   * Counts the number of revisions in the default language.
   *
   * @param \Drupal\simple_megamenu\Entity\SimpleMegaMenuInterface $entity
   *   The Simple mega menu entity.
   *
   * @return int
   *   The number of revisions in the default language.
   */
  public function countDefaultLanguageRevisions(SimpleMegaMenuInterface $entity);

  /**
   * Unsets the language for all Simple mega menu with the given language.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language object.
   */
  public function clearRevisionsLanguage(LanguageInterface $language);

}
