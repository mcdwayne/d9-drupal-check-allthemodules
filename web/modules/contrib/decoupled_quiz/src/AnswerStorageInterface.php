<?php

namespace Drupal\decoupled_quiz;

use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\decoupled_quiz\Entity\AnswerInterface;

/**
 * Defines the storage handler class for Answer entities.
 *
 * This extends the base storage class, adding required special handling for
 * Answer entities.
 *
 * @ingroup decoupled_quiz
 */
interface AnswerStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets a list of Answer revision IDs for a specific Answer.
   *
   * @param \Drupal\decoupled_quiz\Entity\AnswerInterface $entity
   *   The Answer entity.
   *
   * @return int[]
   *   Answer revision IDs (in ascending order).
   */
  public function revisionIds(AnswerInterface $entity);

  /**
   * Gets a list of revision IDs having a given user as Answer author.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity.
   *
   * @return int[]
   *   Answer revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account);

  /**
   * Counts the number of revisions in the default language.
   *
   * @param \Drupal\decoupled_quiz\Entity\AnswerInterface $entity
   *   The Answer entity.
   *
   * @return int
   *   The number of revisions in the default language.
   */
  public function countDefaultLanguageRevisions(AnswerInterface $entity);

  /**
   * Unsets the language for all Answer with the given language.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language object.
   */
  public function clearRevisionsLanguage(LanguageInterface $language);

}
