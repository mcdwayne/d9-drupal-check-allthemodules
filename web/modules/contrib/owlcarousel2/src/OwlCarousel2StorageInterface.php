<?php

namespace Drupal\owlcarousel2;

use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\owlcarousel2\Entity\OwlCarousel2Interface;

/**
 * Defines the storage handler class for OwlCarousel2 entities.
 *
 * This extends the base storage class, adding required special handling for
 * OwlCarousel2 entities.
 *
 * @ingroup owlcarousel2
 */
interface OwlCarousel2StorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets a list of OwlCarousel2 revision IDs for a specific OwlCarousel2.
   *
   * @param \Drupal\owlcarousel2\Entity\OwlCarousel2Interface $entity
   *   The OwlCarousel2 entity.
   *
   * @return int[]
   *   OwlCarousel2 revision IDs (in ascending order).
   */
  public function revisionIds(OwlCarousel2Interface $entity);

  /**
   * Gets a list of revision IDs having a given user as OwlCarousel2 author.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity.
   *
   * @return int[]
   *   OwlCarousel2 revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account);

  /**
   * Counts the number of revisions in the default language.
   *
   * @param \Drupal\owlcarousel2\Entity\OwlCarousel2Interface $entity
   *   The OwlCarousel2 entity.
   *
   * @return int
   *   The number of revisions in the default language.
   */
  public function countDefaultLanguageRevisions(OwlCarousel2Interface $entity);

  /**
   * Unsets the language for all OwlCarousel2 with the given language.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language object.
   */
  public function clearRevisionsLanguage(LanguageInterface $language);

}
