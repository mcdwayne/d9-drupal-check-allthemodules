<?php

namespace Drupal\pagedesigner;

use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\pagedesigner\Entity\ElementInterface;

/**
 * Defines the storage handler class for Pagedesigner Element entities.
 *
 * This extends the base storage class, adding required special handling for
 * Pagedesigner Element entities.
 *
 * @ingroup pagedesigner
 */
interface ElementStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets a list of Pagedesigner Element revision IDs for a specific Pagedesigner Element.
   *
   * @param \Drupal\pagedesigner\Entity\ElementInterface $entity
   *   The Pagedesigner Element entity.
   *
   * @return int[]
   *   Pagedesigner Element revision IDs (in ascending order).
   */
  public function revisionIds(ElementInterface $entity);

  /**
   * Gets a list of revision IDs having a given user as Pagedesigner Element author.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity.
   *
   * @return int[]
   *   Pagedesigner Element revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account);

  /**
   * Counts the number of revisions in the default language.
   *
   * @param \Drupal\pagedesigner\Entity\ElementInterface $entity
   *   The Pagedesigner Element entity.
   *
   * @return int
   *   The number of revisions in the default language.
   */
  public function countDefaultLanguageRevisions(ElementInterface $entity);

  /**
   * Unsets the language for all Pagedesigner Element with the given language.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language object.
   */
  public function clearRevisionsLanguage(LanguageInterface $language);

}
