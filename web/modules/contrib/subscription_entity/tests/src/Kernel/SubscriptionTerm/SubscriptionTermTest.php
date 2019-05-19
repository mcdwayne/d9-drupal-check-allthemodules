<?php

namespace Drupal\Tests\subscription\Kernel\SubscriptionTerm;

use Drupal\Tests\subscription\Kernel\SubscriptionKernelTestBase;

/**
 * Tests the general behavior of subscription type entities.
 *
 * @coversDefaultClass \Drupal\subscription_entity\Entity\subscription_term
 * @group subscription
 */
class SubscriptionTermTest extends SubscriptionKernelTestBase {

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
   * @covers ::isActiveTerm
   */
  public function testIsActiveTerm() {

    $subscription = $this->createSubscription();
    // Check to see if the term is active using the current time.
    $subscriptionTerm = $this->createSubscriptionTerm($subscription, date('Y-m-d H:i:s', strtotime('-1 day')));
    $active = $subscriptionTerm->isActiveTerm();
    $this->assertTrue($active);
    // Check to see if the term is active using a time that is in the future.
    $subscriptionTerm->set('start_date', date('Y-m-d', strtotime('+1 year')));
    $subscriptionTerm->save();
    $active = $subscriptionTerm->isActiveTerm();
    $this->assertFalse($active);
  }

  /**
   * Check that when we create a term that is active, that the subscription is active.
   */
  public function testSubscriptionIsActive() {
    $subscription = $this->createSubscription();
    // Check to see if the term is active using the current time.
    $this->createSubscriptionTerm($subscription, date('Y-m-d H:i:s', strtotime('-1 day')));
    // Reload the subscription.
    $subscription = $this->entityTypeManager->getStorage('subscription')->load($subscription->id());
    $this->assertTrue($subscription->isActive());
  }

  /**
   * Check to make sure the end date is set correct.
   */
  public function testDefaultTermEndDate() {

    $subscription = $this->createSubscription();
    // Check to see if the term is active using the current time.
    $subscriptionTerm = $this->createSubscriptionTerm($subscription, date('Y-m-d H:i:s', strtotime('-1 day')));
    $startDate = new \DateTime($subscriptionTerm->get('start_date')->value);
    $endDate = new \DateTime($subscriptionTerm->get('end_date')->value);
    $diff = $startDate->diff($endDate);
    $values = array_unique(array_values(get_object_vars($diff)));
    $oneYear = (count($values) == 3 && $values[0] == 1 && $values[1] == 0) ? TRUE : FALSE;
    $this->assertTrue($oneYear);

  }

}
