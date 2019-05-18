<?php

namespace Drupal\phones_contact\Entity;

use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\phones_contact\Entity\PhonesContactInterface;

/**
 * Defines the storage handler class for Phones contact entities.
 *
 * This extends the base storage class, adding required special handling for
 * Phones contact entities.
 *
 * @ingroup phones_contact
 */
interface PhonesContactStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets a list of Phones contact revision IDs for a specific Phones contact.
   *
   * @param \Drupal\phones_contact\Entity\PhonesContactInterface $entity
   *   The Phones contact entity.
   *
   * @return int[]
   *   Phones contact revision IDs (in ascending order).
   */
  public function revisionIds(PhonesContactInterface $entity);

  /**
   * Gets a list of revision IDs having a given user as Phones contact author.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity.
   *
   * @return int[]
   *   Phones contact revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account);

  /**
   * Counts the number of revisions in the default language.
   *
   * @param \Drupal\phones_contact\Entity\PhonesContactInterface $entity
   *   The Phones contact entity.
   *
   * @return int
   *   The number of revisions in the default language.
   */
  public function countDefaultLanguageRevisions(PhonesContactInterface $entity);

  /**
   * Unsets the language for all Phones contact with the given language.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language object.
   */
  public function clearRevisionsLanguage(LanguageInterface $language);

}
