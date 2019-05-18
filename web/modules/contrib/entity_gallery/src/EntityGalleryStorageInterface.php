<?php

namespace Drupal\entity_gallery;

use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines an interface for entity gallery entity storage classes.
 */
interface EntityGalleryStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets a list of entity gallery revision IDs for a specific entity gallery.
   *
   * @param \Drupal\entity_gallery\EntityGalleryInterface
   *   The entity gallery entity.
   *
   * @return int[]
   *   Entity gallery revision IDs (in ascending order).
   */
  public function revisionIds(EntityGalleryInterface $entity_gallery);

  /**
   * Gets a list of revision IDs having a given user as entity gallery author.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity.
   *
   * @return int[]
   *   Entity gallery revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account);

  /**
   * Counts the number of revisions in the default language.
   *
   * @param \Drupal\entity_gallery\EntityGalleryInterface
   *   The entity gallery entity.
   *
   * @return int
   *   The number of revisions in the default language.
   */
  public function countDefaultLanguageRevisions(EntityGalleryInterface $entity_gallery);

  /**
   * Updates all entity galleries of one type to be of another type.
   *
   * @param string $old_type
   *   The current entity gallery type of the entity galleries.
   * @param string $new_type
   *   The new entity gallery type of the entity galleries.
   *
   * @return int
   *   The number of entity galleries whose entity gallery type field was 
   *   modified.
   */
  public function updateType($old_type, $new_type);

  /**
   * Unsets the language for all entity galleries with the given language.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language object.
   */
  public function clearRevisionsLanguage(LanguageInterface $language);
}
