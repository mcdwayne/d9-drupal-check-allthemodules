<?php

namespace Drupal\fragments;

use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\fragments\Entity\FragmentInterface;

/**
 * Defines the storage handler class for fragment entities.
 *
 * This extends the base storage class, adding required special handling for
 * fragment entities.
 *
 * @ingroup fragments
 */
interface FragmentStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets a list of fragment revision IDs for a specific fragment.
   *
   * @param \Drupal\fragments\Entity\FragmentInterface $entity
   *   The fragment entity.
   *
   * @return int[]
   *   Fragment revision IDs (in ascending order).
   */
  public function revisionIds(FragmentInterface $entity);

  /**
   * Gets a list of revision IDs having a given user as fragment author.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity.
   *
   * @return int[]
   *   Fragment revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account);

  /**
   * Counts the number of revisions in the default language.
   *
   * @param \Drupal\fragments\Entity\FragmentInterface $entity
   *   The fragment entity.
   *
   * @return int
   *   The number of revisions in the default language.
   */
  public function countDefaultLanguageRevisions(FragmentInterface $entity);

  /**
   * Unsets the language for all fragments with the given language.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language object.
   */
  public function clearRevisionsLanguage(LanguageInterface $language);

}
