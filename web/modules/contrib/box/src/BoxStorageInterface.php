<?php

namespace Drupal\box;

use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\box\Entity\BoxInterface;

/**
 * Defines the storage handler class for Box entities.
 *
 * This extends the base storage class, adding required special handling for
 * Box entities.
 *
 * @ingroup box
 */
interface BoxStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets a list of Box revision IDs for a specific Box.
   *
   * @param \Drupal\box\Entity\BoxInterface $entity
   *   The Box entity.
   *
   * @return int[]
   *   Box revision IDs (in ascending order).
   */
  public function revisionIds(BoxInterface $entity);

  /**
   * Gets a list of revision IDs having a given user as Box author.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity.
   *
   * @return int[]
   *   Box revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account);

  /**
   * Counts the number of revisions in the default language.
   *
   * @param \Drupal\box\Entity\BoxInterface $entity
   *   The Box entity.
   *
   * @return int
   *   The number of revisions in the default language.
   */
  public function countDefaultLanguageRevisions(BoxInterface $entity);

  /**
   * Unsets the language for all Box with the given language.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language object.
   */
  public function clearRevisionsLanguage(LanguageInterface $language);

}
