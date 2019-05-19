<?php

namespace Drupal\subscription_entity;

use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\subscription_entity\Entity\SubscriptionTermInterface;

/**
 * Defines the storage handler class for Subscription Term entities.
 *
 * This extends the base storage class, adding required special handling for
 * Subscription Term entities.
 *
 * @ingroup subscription
 */
interface SubscriptionTermStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets a list of Subscription Term revision IDs for a specific Subscription Term.
   *
   * @param \Drupal\subscription_entity\Entity\SubscriptionTermInterface $entity
   *   The Subscription Term entity.
   *
   * @return int[]
   *   Subscription Term revision IDs (in ascending order).
   */
  public function revisionIds(SubscriptionTermInterface $entity);

  /**
   * Gets a list of revision IDs having a given user as Subscription Term author.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity.
   *
   * @return int[]
   *   Subscription Term revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account);

  /**
   * Counts the number of revisions in the default language.
   *
   * @param \Drupal\subscription_entity\Entity\SubscriptionTermInterface $entity
   *   The Subscription Term entity.
   *
   * @return int
   *   The number of revisions in the default language.
   */
  public function countDefaultLanguageRevisions(SubscriptionTermInterface $entity);

  /**
   * Unsets the language for all Subscription Term with the given language.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language object.
   */
  public function clearRevisionsLanguage(LanguageInterface $language);

}
