<?php

namespace Drupal\trance;

use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines an interface for trance entity storage classes.
 */
interface TranceStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets a list of trance revision IDs for a specific trance.
   *
   * @param \Drupal\trance\TranceInterface $trance
   *   The trance entity.
   *
   * @return int[]
   *   Trance revision IDs (in ascending order).
   */
  public function revisionIds(TranceInterface $trance);

  /**
   * Gets a list of revision IDs having a given user as trance author.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity.
   *
   * @return int[]
   *   Trance revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account);

  /**
   * Counts the number of revisions in the default language.
   *
   * @param \Drupal\trance\TranceInterface $trance
   *   The trance entity.
   *
   * @return int
   *   The number of revisions in the default language.
   */
  public function countDefaultLanguageRevisions(TranceInterface $trance);

  /**
   * Updates all trances of one type to be of another type.
   *
   * @param string $old_type
   *   The current trance type of the trances.
   * @param string $new_type
   *   The new trance type of the trances.
   *
   * @return int
   *   The number of trances whose trance type field was modified.
   */
  public function updateType($old_type, $new_type);

  /**
   * Unsets the language for all trances with the given language.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language object.
   */
  public function clearRevisionsLanguage(LanguageInterface $language);

}
