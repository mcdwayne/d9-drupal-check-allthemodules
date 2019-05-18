<?php

namespace Drupal\permanent_entities;

use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\permanent_entities\Entity\PermanentEntityInterface;

/**
 * Defines the storage handler class for Permanent Entity entities.
 *
 * This extends the base storage class, adding required special handling for
 * Permanent Entity entities.
 *
 * @ingroup permanent_entities
 */
interface PermanentEntityStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets a list of Permanent Entity revision IDs for a specific Permanent Entity.
   *
   * @param \Drupal\permanent_entities\Entity\PermanentEntityInterface $entity
   *   The Permanent Entity entity.
   *
   * @return int[]
   *   Permanent Entity revision IDs (in ascending order).
   */
  public function revisionIds(PermanentEntityInterface $entity);

  /**
   * Gets a list of revision IDs having a given user as Permanent Entity author.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity.
   *
   * @return int[]
   *   Permanent Entity revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account);

  /**
   * Counts the number of revisions in the default language.
   *
   * @param \Drupal\permanent_entities\Entity\PermanentEntityInterface $entity
   *   The Permanent Entity entity.
   *
   * @return int
   *   The number of revisions in the default language.
   */
  public function countDefaultLanguageRevisions(PermanentEntityInterface $entity);

  /**
   * Unsets the language for all Permanent Entity with the given language.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language object.
   */
  public function clearRevisionsLanguage(LanguageInterface $language);

}
