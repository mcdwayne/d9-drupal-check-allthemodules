<?php

namespace Drupal\subscription_entity\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\subscription_entity\Event\SubscriptionStateUpdatedEvent;
use Drupal\user\Entity\User;

/**
 * Class SubscriptionEvents.
 *
 * @package Drupal\subscription_entity
 */
class SubscriptionEvents implements EventSubscriberInterface {

  protected $state;

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events['subscription.state_updated'][] = array('subscriptionStateUpdatedEvent');
    return $events;
  }

  /**
   * Update the users role depending on the subscription state.
   *
   * @param \Drupal\subscription_entity\Event\SubscriptionStateUpdatedEvent $event
   *   SubscriptionStateUpdatedEvent.
   */
  public function subscriptionStateUpdatedEvent(SubscriptionStateUpdatedEvent $event) {
    $subscription = $event->getSubscription();
    $state = $event->getSubscriptionState();
    // Assign the user to a given role.
    $user = $subscription->getSubscriptionOwner();
    $subscriptionTypeEntity = $subscription->getSubscriptionTypeEntity();

    if (is_object($user) && is_object($subscriptionTypeEntity)) {
      $role = $subscriptionTypeEntity->getRole();
      $this->updateUserRole($user, $state, $role);
    }

  }

  /**
   * Updates the user role.
   *
   * @param \Drupal\user\Entity\User $user
   *   The user entity.
   * @param int $state
   *   The state of the subscription.
   * @param string $role
   *   The role that needs to be removed or added.
   */
  public function updateUserRole(User $user, $state, $role) {
    if ($role != 'authenticated') {
      switch ($state) {
        case SUBSCRIPTION_ACTIVE:
          $user->addRole($role);
          break;

        default:
          $user->removeRole($role);
      }
      $user->save();
    }
  }

}
