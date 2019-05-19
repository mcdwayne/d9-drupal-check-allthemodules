<?php

namespace Drupal\subscription_entity;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\user\UserInterface;
use Drupal\subscription_entity\Entity\SubscriptionInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;

/**
 * Easy load methods for subscription components.
 *
 * @package Drupal\subscription_entity
 */
class SubscriptionLoader implements SubscriptionLoaderInterface {

  protected $entityTypeManager;

  protected $entityTypeBundleInfo;

  /**
   * SubscriptionLoader constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entityTypeBundleInfo
   *   Entity type bundle info interface.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, EntityTypeBundleInfoInterface $entityTypeBundleInfo) {
    $this->entityTypeManager = $entityTypeManager;
    $this->entityTypeBundleInfo = $entityTypeBundleInfo;
  }

  /**
   * Checks to see if a user is already assigned to a membership type.
   *
   * @param \Drupal\subscription_entity\Entity\SubscriptionInterface $subscription
   *   The subscription.
   * @param \Drupal\user\UserInterface $user
   *   The user object.
   *
   * @return bool
   *   TRUE if the user is already assigned, FALSE otherwise.
   */
  public function isUserAlreadyAssigned(SubscriptionInterface $subscription, UserInterface $user) {
    $result = [];
    if (is_object($user)) {
      $result = $this->entityTypeManager->getStorage($subscription->getEntityTypeId())
        ->loadByProperties([
          'type' => $subscription->bundle(),
          'subscription_owner_uid' => $user->id(),
        ]);
    }
    return !empty($result);
  }

  /**
   * Load the subscription object.
   *
   * @param int $id
   *   The id of the subscription.
   *
   * @return \Drupal\core\Entity\EntityInterface|null
   *   The subscription term or null.
   */
  public function loadSubscriptionById($id) {
    return $this->entityTypeManager->getStorage('subscription')->load($id);
  }

  /**
   * Load the subscription term object.
   *
   * @param int $id
   *   The id of the subscription term.
   *
   * @return \Drupal\core\Entity\EntityInterface|null
   *   The subscription term or null.
   */
  public function loadSubscriptionTermById($id) {
    return $this->entityTypeManager->getStorage('subscription_term')->load($id);
  }

  /**
   * Gets an array of bundle types.
   *
   * @return array
   *   An array of subscription types.
   */
  public function getSubscriptionTypes() {
    $types = $this->entityTypeBundleInfo->getBundleInfo('subscription');
    return array_keys($types);
  }

  /**
   * Renew a subscription.
   *
   * @param \Drupal\subscription_entity\Entity\SubscriptionInterface $subscription
   *   The subscription.
   */
  public function renewSubscription(SubscriptionInterface $subscription) {
    $subscription->renew();
  }

}
