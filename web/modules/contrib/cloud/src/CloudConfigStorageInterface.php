<?php

namespace Drupal\cloud;

use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\cloud\Entity\CloudConfigInterface;

/**
 * Defines the storage handler class for Cloud config entities.
 *
 * This extends the base storage class, adding required special handling for
 * Cloud config entities.
 *
 * @ingroup cloud
 */
interface CloudConfigStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets a list of Cloud config revision IDs for a specific Cloud config.
   *
   * @param \Drupal\cloud\Entity\CloudConfigInterface $entity
   *   The Cloud config entity.
   *
   * @return int[]
   *   Cloud config revision IDs (in ascending order).
   */
  public function revisionIds(CloudConfigInterface $entity);

  /**
   * Gets a list of revision IDs having a given user as Cloud config author.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity.
   *
   * @return int[]
   *   Cloud config revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account);

  /**
   * Counts the number of revisions in the default language.
   *
   * @param \Drupal\cloud\Entity\CloudConfigInterface $entity
   *   The Cloud config entity.
   *
   * @return int
   *   The number of revisions in the default language.
   */
  public function countDefaultLanguageRevisions(CloudConfigInterface $entity);

  /**
   * Unsets the language for all Cloud config with the given language.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language object.
   */
  public function clearRevisionsLanguage(LanguageInterface $language);

}
