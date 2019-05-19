<?php

namespace Drupal\sponsor;

use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\sponsor\Entity\SponsorInterface;

/**
 * Defines the storage handler class for Sponsor entities.
 *
 * This extends the base storage class, adding required special handling for
 * Sponsor entities.
 *
 * @ingroup sponsors
 */
interface SponsorStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets a list of Sponsor revision IDs for a specific Sponsor.
   *
   * @param \Drupal\sponsor\Entity\SponsorInterface $entity
   *   The Sponsor entity.
   *
   * @return int[]
   *   Sponsor revision IDs (in ascending order).
   */
  public function revisionIds(SponsorInterface $entity);

  /**
   * Gets a list of revision IDs having a given user as Sponsor author.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity.
   *
   * @return int[]
   *   Sponsor revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account);

  /**
   * Counts the number of revisions in the default language.
   *
   * @param \Drupal\sponsor\Entity\SponsorInterface $entity
   *   The Sponsor entity.
   *
   * @return int
   *   The number of revisions in the default language.
   */
  public function countDefaultLanguageRevisions(SponsorInterface $entity);

  /**
   * Unsets the language for all Sponsor with the given language.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language object.
   */
  public function clearRevisionsLanguage(LanguageInterface $language);

}
