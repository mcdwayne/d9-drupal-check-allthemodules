<?php

namespace Drupal\entity_collector;

use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\entity_collector\Entity\EntityCollectionInterface;

/**
 * Defines the storage handler class for Entity collection entities.
 *
 * This extends the base storage class, adding required special handling for
 * Entity collection entities.
 *
 * @ingroup entity_collector
 */
interface EntityCollectionStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets a list of Entity collection revision IDs for a specific Entity collection.
   *
   * @param \Drupal\entity_collector\Entity\EntityCollectionInterface $entity
   *   The Entity collection entity.
   *
   * @return int[]
   *   Entity collection revision IDs (in ascending order).
   */
  public function revisionIds(EntityCollectionInterface $entity);

  /**
   * Gets a list of revision IDs having a given user as Entity collection author.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity.
   *
   * @return int[]
   *   Entity collection revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account);

  /**
   * Counts the number of revisions in the default language.
   *
   * @param \Drupal\entity_collector\Entity\EntityCollectionInterface $entity
   *   The Entity collection entity.
   *
   * @return int
   *   The number of revisions in the default language.
   */
  public function countDefaultLanguageRevisions(EntityCollectionInterface $entity);

  /**
   * Unsets the language for all Entity collection with the given language.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language object.
   */
  public function clearRevisionsLanguage(LanguageInterface $language);

}
