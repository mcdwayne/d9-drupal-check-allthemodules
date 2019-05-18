<?php

namespace Drupal\patreon_entity;

use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\patreon_entity\Entity\PatreonEntityInterface;

/**
 * Defines the storage handler class for Patreon entity entities.
 *
 * This extends the base storage class, adding required special handling for
 * Patreon entity entities.
 *
 * @ingroup patreon_entity
 */
interface PatreonEntityStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets a list of Patreon entity revision IDs for a specific Patreon entity.
   *
   * @param \Drupal\patreon_entity\Entity\PatreonEntityInterface $entity
   *   The Patreon entity entity.
   *
   * @return int[]
   *   Patreon entity revision IDs (in ascending order).
   */
  public function revisionIds(PatreonEntityInterface $entity);

  /**
   * Gets a list of revision IDs having a given user as Patreon entity author.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity.
   *
   * @return int[]
   *   Patreon entity revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account);

  /**
   * Counts the number of revisions in the default language.
   *
   * @param \Drupal\patreon_entity\Entity\PatreonEntityInterface $entity
   *   The Patreon entity entity.
   *
   * @return int
   *   The number of revisions in the default language.
   */
  public function countDefaultLanguageRevisions(PatreonEntityInterface $entity);

  /**
   * Unsets the language for all Patreon entity with the given language.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language object.
   */
  public function clearRevisionsLanguage(LanguageInterface $language);

}
