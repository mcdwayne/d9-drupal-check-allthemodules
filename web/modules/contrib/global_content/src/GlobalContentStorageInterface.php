<?php

namespace Drupal\global_content;

use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\global_content\Entity\GlobalContentInterface;

/**
 * Defines the storage handler class for Global Content entities.
 *
 * This extends the base storage class, adding required special handling for
 * Global Content entities.
 *
 * @ingroup global_content
 */
interface GlobalContentStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets a list of Global Content revision IDs for a specific Global Content.
   *
   * @param \Drupal\global_content\Entity\GlobalContentInterface $entity
   *   The Global Content entity.
   *
   * @return int[]
   *   Global Content revision IDs (in ascending order).
   */
  public function revisionIds(GlobalContentInterface $entity);

  /**
   * Gets a list of revision IDs having a given user as Global Content author.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity.
   *
   * @return int[]
   *   Global Content revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account);

  /**
   * Counts the number of revisions in the default language.
   *
   * @param \Drupal\global_content\Entity\GlobalContentInterface $entity
   *   The Global Content entity.
   *
   * @return int
   *   The number of revisions in the default language.
   */
  public function countDefaultLanguageRevisions(GlobalContentInterface $entity);

  /**
   * Unsets the language for all Global Content with the given language.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language object.
   */
  public function clearRevisionsLanguage(LanguageInterface $language);

}
