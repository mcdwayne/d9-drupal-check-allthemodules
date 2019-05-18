<?php

namespace Drupal\ips;

use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\ips\Entity\IpsInterface;

/**
 * Defines the storage handler class for Ips entities.
 *
 * This extends the base storage class, adding required special handling for
 * Ips entities.
 *
 * @ingroup ips
 */
interface IpsStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets a list of Ips revision IDs for a specific Ips.
   *
   * @param \Drupal\ips\Entity\IpsInterface $entity
   *   The Ips entity.
   *
   * @return int[]
   *   Ips revision IDs (in ascending order).
   */
  public function revisionIds(IpsInterface $entity);

  /**
   * Gets a list of revision IDs having a given user as Ips author.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity.
   *
   * @return int[]
   *   Ips revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account);

  /**
   * Counts the number of revisions in the default language.
   *
   * @param \Drupal\ips\Entity\IpsInterface $entity
   *   The Ips entity.
   *
   * @return int
   *   The number of revisions in the default language.
   */
  public function countDefaultLanguageRevisions(IpsInterface $entity);

  /**
   * Unsets the language for all Ips with the given language.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language object.
   */
  public function clearRevisionsLanguage(LanguageInterface $language);

}
