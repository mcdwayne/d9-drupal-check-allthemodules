<?php

namespace Drupal\visualn_drawing;

use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\visualn_drawing\Entity\VisualNDrawingInterface;

/**
 * Defines the storage handler class for VisualN Drawing entities.
 *
 * This extends the base storage class, adding required special handling for
 * VisualN Drawing entities.
 *
 * @ingroup visualn_drawing
 */
interface VisualNDrawingStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets a list of VisualN Drawing revision IDs for a specific VisualN Drawing.
   *
   * @param \Drupal\visualn_drawing\Entity\VisualNDrawingInterface $entity
   *   The VisualN Drawing entity.
   *
   * @return int[]
   *   VisualN Drawing revision IDs (in ascending order).
   */
  public function revisionIds(VisualNDrawingInterface $entity);

  /**
   * Gets a list of revision IDs having a given user as VisualN Drawing author.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity.
   *
   * @return int[]
   *   VisualN Drawing revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account);

  /**
   * Counts the number of revisions in the default language.
   *
   * @param \Drupal\visualn_drawing\Entity\VisualNDrawingInterface $entity
   *   The VisualN Drawing entity.
   *
   * @return int
   *   The number of revisions in the default language.
   */
  public function countDefaultLanguageRevisions(VisualNDrawingInterface $entity);

  /**
   * Unsets the language for all VisualN Drawing with the given language.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language object.
   */
  public function clearRevisionsLanguage(LanguageInterface $language);

}
