<?php

namespace Drupal\timelinejs;

use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\timelinejs\Entity\TimelineInterface;

/**
 * Defines the storage handler class for Timeline entities.
 *
 * This extends the base storage class, adding required special handling for
 * Timeline entities.
 *
 * @ingroup timelinejs
 */
interface TimelineStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets a list of Timeline revision IDs for a specific Timeline.
   *
   * @param \Drupal\timelinejs\Entity\TimelineInterface $entity
   *   The Timeline entity.
   *
   * @return int[]
   *   Timeline revision IDs (in ascending order).
   */
  public function revisionIds(TimelineInterface $entity);

  /**
   * Gets a list of revision IDs having a given user as Timeline author.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity.
   *
   * @return int[]
   *   Timeline revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account);

  /**
   * Counts the number of revisions in the default language.
   *
   * @param \Drupal\timelinejs\Entity\TimelineInterface $entity
   *   The Timeline entity.
   *
   * @return int
   *   The number of revisions in the default language.
   */
  public function countDefaultLanguageRevisions(TimelineInterface $entity);

  /**
   * Unsets the language for all Timeline with the given language.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language object.
   */
  public function clearRevisionsLanguage(LanguageInterface $language);

}
