<?php

namespace Drupal\entity_generic;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines an interface for entity storage classes.
 */
interface GenericStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets a list of entity revision IDs for a specific entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   *
   * @return int[]
   *   Entity revision IDs (in ascending order).
   */
  public function revisionIds(ContentEntityInterface $entity);

  /**
   * Gets a list of revision IDs having a given user as entity owner.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity.
   *
   * @return int[]
   *   Entity revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account);

  /**
   * Counts the number of revisions in the default language.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   *
   * @return int
   *   The number of revisions in the default language.
   */
  public function countDefaultLanguageRevisions(ContentEntityInterface $entity);

  /**
   * Updates all entities of one type to be of another type.
   *
   * @param string $old_type
   *   The current entity type of the entities.
   * @param string $new_type
   *   The new entity type of the entities.
   *
   * @return int
   *   The number of entities whose entity type field was modified.
   */
  public function updateType($old_type, $new_type);

  /**
   * Unsets the language for all entities with the given language.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language object.
   */
  public function clearRevisionsLanguage(LanguageInterface $language);

}
