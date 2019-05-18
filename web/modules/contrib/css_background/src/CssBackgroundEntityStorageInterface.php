<?php

namespace Drupal\css_background;

use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\css_background\Entity\CssBackgroundEntityInterface;

/**
 * Defines the storage handler class for CSS background entities.
 *
 * This extends the base storage class, adding required special handling for
 * CSS background entities.
 *
 * @ingroup css_background
 */
interface CssBackgroundEntityStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets a list of CSS background revision IDs for a specific CSS background.
   *
   * @param \Drupal\css_background\Entity\CssBackgroundEntityInterface $entity
   *   The CSS background entity.
   *
   * @return int[]
   *   CSS background revision IDs (in ascending order).
   */
  public function revisionIds(CssBackgroundEntityInterface $entity);

  /**
   * Gets a list of revision IDs having a given user as CSS background author.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity.
   *
   * @return int[]
   *   CSS background revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account);

  /**
   * Counts the number of revisions in the default language.
   *
   * @param \Drupal\css_background\Entity\CssBackgroundEntityInterface $entity
   *   The CSS background entity.
   *
   * @return int
   *   The number of revisions in the default language.
   */
  public function countDefaultLanguageRevisions(CssBackgroundEntityInterface $entity);

  /**
   * Unsets the language for all CSS background with the given language.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language object.
   */
  public function clearRevisionsLanguage(LanguageInterface $language);

}
