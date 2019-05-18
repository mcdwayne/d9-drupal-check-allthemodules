<?php

namespace Drupal\resources;

use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\resources\Entity\ResourcesInterface;

/**
 * Defines the storage handler class for Resources entities.
 *
 * This extends the base storage class, adding required special handling for
 * Resources entities.
 *
 * @ingroup resources
 */
interface ResourcesStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets a list of Resources revision IDs for a specific Resources.
   *
   * @param \Drupal\resources\Entity\ResourcesInterface $entity
   *   The Resources entity.
   *
   * @return int[]
   *   Resources revision IDs (in ascending order).
   */
  public function revisionIds(ResourcesInterface $entity);

  /**
   * Gets a list of revision IDs having a given user as Resources author.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity.
   *
   * @return int[]
   *   Resources revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account);

  /**
   * Counts the number of revisions in the default language.
   *
   * @param \Drupal\resources\Entity\ResourcesInterface $entity
   *   The Resources entity.
   *
   * @return int
   *   The number of revisions in the default language.
   */
  public function countDefaultLanguageRevisions(ResourcesInterface $entity);

  /**
   * Unsets the language for all Resources with the given language.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language object.
   */
  public function clearRevisionsLanguage(LanguageInterface $language);

}
