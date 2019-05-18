<?php

/**
 * @file
 * Contains \Drupal\log\LogStorageInterface.
 */

namespace Drupal\log;

use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines an interface for log entity storage classes.
 */
interface LogStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets a list of log revision IDs for a specific log.
   *
   * @param \Drupal\log\LogInterface
   *   The log entity.
   *
   * @return int[]
   *   Log revision IDs (in ascending order).
   */
  public function revisionIds(LogInterface $log);

  /**
   * Gets a list of revision IDs having a given user as log author.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity.
   *
   * @return int[]
   *   Log revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account);

  /**
   * Counts the number of revisions in the default language.
   *
   * @param \Drupal\log\LogInterface
   *   The log entity.
   *
   * @return int
   *   The number of revisions in the default language.
   */
  public function countDefaultLanguageRevisions(LogInterface $log);

  /**
   * Updates all logs of one type to be of another type.
   *
   * @param string $old_type
   *   The current log type of the logs.
   * @param string $new_type
   *   The new log type of the logs.
   *
   * @return int
   *   The number of logs whose log type field was modified.
   */
  public function updateType($old_type, $new_type);

  /**
   * Unsets the language for all logs with the given language.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *  The language object.
   */
  public function clearRevisionsLanguage(LanguageInterface $language);
}
