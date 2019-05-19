<?php

namespace Drupal\subscription_entity;

use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\subscription_entity\Entity\SubscriptionInterface;

/**
 * Defines the storage handler class for Subscription entities.
 *
 * This extends the base storage class, adding required special handling for
 * Subscription entities.
 *
 * @ingroup subscription
 */
interface SubscriptionStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets a list of Subscription revision IDs for a specific Subscription.
   *
   * @param \Drupal\subscription_entity\Entity\SubscriptionInterface $entity
   *   The Subscription entity.
   *
   * @return int[]
   *   Subscription revision IDs (in ascending order).
   */
  public function revisionIds(SubscriptionInterface $entity);

  /**
   * Gets a list of revision IDs having a given user as Subscription author.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity.
   *
   * @return int[]
   *   Subscription revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account);

  /**
   * Counts the number of revisions in the default language.
   *
   * @param \Drupal\subscription_entity\Entity\SubscriptionInterface $entity
   *   The Subscription entity.
   *
   * @return int
   *   The number of revisions in the default language.
   */
  public function countDefaultLanguageRevisions(SubscriptionInterface $entity);

  /**
   * Unsets the language for all Subscription with the given language.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language object.
   */
  public function clearRevisionsLanguage(LanguageInterface $language);

}
