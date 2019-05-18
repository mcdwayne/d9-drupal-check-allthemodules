<?php

namespace Drupal\business_rules;

use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\business_rules\Entity\ScheduleInterface;

/**
 * Defines the storage handler class for Schedule entities.
 *
 * This extends the base storage class, adding required special handling for
 * Schedule entities.
 *
 * @ingroup business_rules
 */
interface ScheduleStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets a list of Schedule revision IDs for a specific Schedule.
   *
   * @param \Drupal\business_rules\Entity\ScheduleInterface $entity
   *   The Schedule entity.
   *
   * @return int[]
   *   Schedule revision IDs (in ascending order).
   */
  public function revisionIds(ScheduleInterface $entity);

  /**
   * Gets a list of revision IDs having a given user as Schedule author.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity.
   *
   * @return int[]
   *   Schedule revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account);

  /**
   * Counts the number of revisions in the default language.
   *
   * @param \Drupal\business_rules\Entity\ScheduleInterface $entity
   *   The Schedule entity.
   *
   * @return int
   *   The number of revisions in the default language.
   */
  public function countDefaultLanguageRevisions(ScheduleInterface $entity);

  /**
   * Unsets the language for all Schedule with the given language.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language object.
   */
  public function clearRevisionsLanguage(LanguageInterface $language);

}
