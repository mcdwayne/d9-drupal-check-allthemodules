<?php

namespace Drupal\decoupled_quiz;

use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\decoupled_quiz\Entity\QuizInterface;

/**
 * Defines the storage handler class for Quiz entities.
 *
 * This extends the base storage class, adding required special handling for
 * Quiz entities.
 *
 * @ingroup decoupled_quiz
 */
interface QuizStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets a list of Quiz revision IDs for a specific Quiz.
   *
   * @param \Drupal\decoupled_quiz\Entity\QuizInterface $entity
   *   The Quiz entity.
   *
   * @return int[]
   *   Quiz revision IDs (in ascending order).
   */
  public function revisionIds(QuizInterface $entity);

  /**
   * Gets a list of revision IDs having a given user as Quiz author.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity.
   *
   * @return int[]
   *   Quiz revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account);

  /**
   * Counts the number of revisions in the default language.
   *
   * @param \Drupal\decoupled_quiz\Entity\QuizInterface $entity
   *   The Quiz entity.
   *
   * @return int
   *   The number of revisions in the default language.
   */
  public function countDefaultLanguageRevisions(QuizInterface $entity);

  /**
   * Unsets the language for all Quiz with the given language.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language object.
   */
  public function clearRevisionsLanguage(LanguageInterface $language);

}
