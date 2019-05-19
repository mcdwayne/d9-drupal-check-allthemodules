<?php

namespace Drupal\Tests\subscription\Kernel\Subscription;

use Drupal\Tests\subscription\Kernel\SubscriptionKernelTestBase;

/**
 * Tests the general behavior of subscription type entities.
 *
 * @coversDefaultClass \Drupal\subscription_entity\Entity\Subscription
 * @group subscription
 */
class SubscriptionTest extends SubscriptionKernelTestBase {

  /**
   * The subscription type storage.
   *
   * @var subscriptionTypeStorage
   */
  protected $subscriptionTypeStorage;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
  }

  /**
   * Test to make sure we can create term objects.
   */
  public function testSubscriptionTermCreated() {
    $subscription = $this->createSubscription();
    $subscriptionTerm = $this->createSubscriptionTerm($subscription);
    $this->assertInstanceOf('Drupal\subscription_entity\Entity\subscription_term', $subscriptionTerm);
  }

  /**
   * Check to make sure we can create a subscription.
   */
  public function testSubscriptionCreated() {
    $subscription = $this->createSubscription();
    $this->assertInstanceOf('Drupal\subscription_entity\Entity\Subscription', $subscription);
  }

  /**
   * Checks to see if we can assign a term to a subscription.
   */
  public function testIsTermAssignedToSubscription() {
    $subscription = $this->createSubscription();
    $subscriptionTerm = $this->createSubscriptionTerm($subscription);
    $subscriptionTerm->setSubscriptionEntityId($subscription->id());
    $subscriptionTerm->save();
    $in = ($subscriptionTerm->getSubscriptionEntityId() == $subscription->id()) ? TRUE : FALSE;
    $this->assertTrue($in);
  }

  /**
   * Checks to make sure that when a subscription is active that the user has a role.
   */
  public function testUserHasSubscriptionRole() {
    $subscription = $this->createSubscription();
    $this->createSubscriptionTerm($subscription, date('Y-m-d H:i:s', strtotime('-1 day')));
    $userEntity = $subscription->get('subscription_owner_uid')->entity;
    $roles = $userEntity->getRoles();
    $subscriptionRole = $this->getSubscriptionRole();
    $this->assertTrue(in_array(strtolower($subscriptionRole->label()), $roles));
  }

  /**
   * When a subscription is active test to see if it expires.
   */
  public function testUserSubscriptionExpiry() {

    $subscription = $this->createSubscription();
    $subscriptionTerm = $this->createSubscriptionTerm($subscription, date('Y-m-d H:i:s', strtotime('-1 day')));
    $subscription = $this->entityTypeManager->getStorage('subscription')->load($subscription->id());
    $userEntity = $subscription->get('subscription_owner_uid')->entity;
    $roles = $userEntity->getRoles();
    // Check to make sure the subscription is active.
    $this->assertTrue(SUBSCRIPTION_ACTIVE == $subscriptionTerm->isActiveTerm());
    $this->assertTrue(SUBSCRIPTION_ACTIVE == $subscription->isActive());
    $subscriptionRole = $this->getSubscriptionRole();
    $this->assertTrue(in_array(strtolower($subscriptionRole->label()), $roles));

    // Push back the dates so that the subscription expires.
    $subscriptionTerm->set('start_date', date('Y-m-d H:i:s', strtotime('-2 year')));
    $subscriptionTerm->set('end_date', date('Y-m-d H:i:s', strtotime('-1 year')));
    $subscriptionTerm->save();

    // Cant simulate queue cron worker running so we will deactivate manually.
    $subscriptionTerm->deActivateTerm();

    $subscription = $this->entityTypeManager->getStorage('subscription')->load($subscription->id());

    $this->assertTrue(SUBSCRIPTION_EXPIRED == $subscriptionTerm->get('term_status')->value);
    $this->assertTrue(SUBSCRIPTION_EXPIRED == $subscription->get('subscription_status')->value);

    // Check to make sure the role has been removed.
    $roles = $userEntity->getRoles();
    $subscriptionRole = $this->getSubscriptionRole();
    $this->assertFalse(in_array(strtolower($subscriptionRole->label()), $roles));

  }

}
