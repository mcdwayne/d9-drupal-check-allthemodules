<?php

namespace Drupal\homebox;

use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\homebox\Entity\HomeboxLayoutInterface;

/**
 * Defines the storage handler class for Homebox Layout entities.
 *
 * This extends the base storage class, adding required special handling for
 * Homebox Layout entities.
 *
 * @ingroup homebox
 */
interface HomeboxLayoutStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets a list of Homebox Layout revision IDs for a specific Homebox Layout.
   *
   * @param \Drupal\homebox\Entity\HomeboxLayoutInterface $entity
   *   The Homebox Layout entity.
   *
   * @return int[]
   *   Homebox Layout revision IDs (in ascending order).
   */
  public function revisionIds(HomeboxLayoutInterface $entity);

  /**
   * Gets a list of revision IDs having a given user as Homebox Layout author.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity.
   *
   * @return int[]
   *   Homebox Layout revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account);

  /**
   * Counts the number of revisions in the default language.
   *
   * @param \Drupal\homebox\Entity\HomeboxLayoutInterface $entity
   *   The Homebox Layout entity.
   *
   * @return int
   *   The number of revisions in the default language.
   */
  public function countDefaultLanguageRevisions(HomeboxLayoutInterface $entity);

  /**
   * Unsets the language for all Homebox Layout with the given language.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language object.
   */
  public function clearRevisionsLanguage(LanguageInterface $language);

}
