<?php

namespace Drupal\micro_site;

use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\micro_site\Entity\SiteInterface;

/**
 * Defines the storage handler class for Site entities.
 *
 * This extends the base storage class, adding required special handling for
 * Site entities.
 *
 * @ingroup micro_site
 */
interface SiteStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets a list of Site revision IDs for a specific Site.
   *
   * @param \Drupal\micro_site\Entity\SiteInterface $entity
   *   The Site entity.
   *
   * @return int[]
   *   Site revision IDs (in ascending order).
   */
  public function revisionIds(SiteInterface $entity);

  /**
   * Gets a list of revision IDs having a given user as Site author.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity.
   *
   * @return int[]
   *   Site revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account);

  /**
   * Counts the number of revisions in the default language.
   *
   * @param \Drupal\micro_site\Entity\SiteInterface $entity
   *   The Site entity.
   *
   * @return int
   *   The number of revisions in the default language.
   */
  public function countDefaultLanguageRevisions(SiteInterface $entity);

  /**
   * Unsets the language for all Site with the given language.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language object.
   */
  public function clearRevisionsLanguage(LanguageInterface $language);

}
