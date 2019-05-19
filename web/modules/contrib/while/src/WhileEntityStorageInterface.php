<?php

namespace Drupal\white_label_entity;

use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\white_label_entity\Entity\WhileEntityInterface;

/**
 * Defines the storage handler class for while entities.
 *
 * This extends the base storage class, adding required special handling for
 * while entities.
 *
 * @ingroup while
 */
interface WhileEntityStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets a list of While entity revision IDs for a specific While entity.
   *
   * @param \Drupal\white_label_entity\Entity\WhileEntityInterface $entity
   *   The While entity entity.
   *
   * @return int[]
   *   While entity revision IDs (in ascending order).
   */
  public function revisionIds(WhileEntityInterface $entity);

  /**
   * Gets a list of revision IDs having a given user as While entity author.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity.
   *
   * @return int[]
   *   While entity revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account);

  /**
   * Counts the number of revisions in the default language.
   *
   * @param \Drupal\white_label_entity\Entity\WhileEntityInterface $entity
   *   The While entity entity.
   *
   * @return int
   *   The number of revisions in the default language.
   */
  public function countDefaultLanguageRevisions(WhileEntityInterface $entity);

  /**
   * Unsets the language for all While entity with the given language.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language object.
   */
  public function clearRevisionsLanguage(LanguageInterface $language);

}
