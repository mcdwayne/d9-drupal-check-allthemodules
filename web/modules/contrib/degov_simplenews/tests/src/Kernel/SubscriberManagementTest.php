<?php

namespace Drupal\Tests\degov_simplenews\Kernel;

use Drupal\simplenews\Entity\Newsletter;
use Drupal\simplenews\Entity\Subscriber;
use Drupal\simplenews\SubscriberInterface;
use Drupal\KernelTests\KernelTestBase;

/**
 * Class SubscriberManagementTest.
 *
 * @package Drupal\Tests\degov_simplenews\Kernel
 */
class SubscriberManagementTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'node',
    'system',
    'views',
    'user',
    'field',
    'simplenews',
    'degov_simplenews',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installConfig(['simplenews']);
    $this->installEntitySchema('user');
    $this->installEntitySchema('simplenews_subscriber');

    $now = time();
    $last_week = strtotime('-1 week');

    $second_newsletter = Newsletter::create([
      'id'           => 'second',
      'name'         => 'My second newsletter',
      'description'  => 'Another one!',
      'format'       => 'plain',
      'priority'     => 0,
      'receipt'      => 0,
      'from_name'    => '',
      'subject'      => '[[simplenews-newsletter:name]] [node:title]',
      'from_address' => 'replace@example.org',
    ]);
    $second_newsletter->save();

    // Create subscriber with recent signup date.
    $subscriberBeforeExpirationDate = Subscriber::create([
      'mail'     => 'test1@test.com',
      'status'   => SubscriberInterface::ACTIVE,
      'langcode' => 'en',
      'created'  => $now,
      'forename' => 'Testi',
      'surname'  => 'McTesting',
    ]);
    $subscriberBeforeExpirationDate->save();
    $subscriberBeforeExpirationDate->subscribe(
      'default',
      SIMPLENEWS_SUBSCRIPTION_STATUS_UNCONFIRMED,
      'test',
      $now
    );
    $subscriberBeforeExpirationDate->subscribe(
      'second',
      SIMPLENEWS_SUBSCRIPTION_STATUS_UNCONFIRMED,
      'test',
      $now
    );
    $subscriberBeforeExpirationDate->save();

    /*
     * Create subscriber older than check-threshold with all confirmed
     * subscriptions.
     */
    $subscriberWithAllConfirmedSubs = Subscriber::create([
      'mail'     => 'test2@test.com',
      'status'   => SubscriberInterface::ACTIVE,
      'langcode' => 'en',
      'created'  => $last_week,
      'forename' => 'Testi',
      'surname'  => 'McTesting',
    ]);
    $subscriberWithAllConfirmedSubs->save();
    $subscriberWithAllConfirmedSubs->subscribe(
      'default',
      SIMPLENEWS_SUBSCRIPTION_STATUS_SUBSCRIBED,
      'test',
      $last_week
    );
    $subscriberWithAllConfirmedSubs->subscribe(
      'second',
      SIMPLENEWS_SUBSCRIPTION_STATUS_SUBSCRIBED,
      'test',
      $last_week
    );
    $subscriberWithAllConfirmedSubs->save();

    /*
     * Create subscriber older than check-threshold with mixed-confirmation
     * subscriptions.
     */
    $subscriberWithMixedSubs = Subscriber::create([
      'mail'     => 'test3@test.com',
      'status'   => SubscriberInterface::ACTIVE,
      'langcode' => 'en',
      'created'  => $last_week,
      'forename' => 'Testi',
      'surname'  => 'McTesting',
    ]);
    $subscriberWithMixedSubs->save();
    $subscriberWithMixedSubs->subscribe(
      'default',
      SIMPLENEWS_SUBSCRIPTION_STATUS_SUBSCRIBED,
      'test',
      $last_week
    );
    $subscriberWithMixedSubs->subscribe(
      'second',
      SIMPLENEWS_SUBSCRIPTION_STATUS_UNCONFIRMED,
      'test',
      $last_week
    );
    $subscriberWithMixedSubs->save();

    /*
     * Create subscriber older than check-threshold with all unconfirmed
     * subscriptions.
     */
    $subscriberWithAllUnconfirmedSubs = Subscriber::create([
      'mail'     => 'test4@test.com',
      'status'   => SubscriberInterface::ACTIVE,
      'langcode' => 'en',
      'created'  => $last_week,
      'forename' => 'Testi',
      'surname'  => 'McTesting',
    ]);
    $subscriberWithAllUnconfirmedSubs->save();
    $subscriberWithAllUnconfirmedSubs->subscribe(
      'default',
      SIMPLENEWS_SUBSCRIPTION_STATUS_UNCONFIRMED,
      'test',
      $last_week
    );
    $subscriberWithAllUnconfirmedSubs->subscribe(
      'second',
      SIMPLENEWS_SUBSCRIPTION_STATUS_UNCONFIRMED,
      'test',
      $last_week
    );
    $subscriberWithAllUnconfirmedSubs->save();

    // Create unsubscribed subscriber.
    $unsubscribedSubscriber = Subscriber::create([
      'mail'     => 'test5@test.com',
      'status'   => SubscriberInterface::ACTIVE,
      'langcode' => 'en',
      'created'  => $last_week,
      'forename' => 'Testi',
      'surname'  => 'McTesting',
    ]);
    $unsubscribedSubscriber->save();
    $unsubscribedSubscriber->subscribe(
      'default',
      SIMPLENEWS_SUBSCRIPTION_STATUS_UNSUBSCRIBED,
      'test',
      $last_week
    );
    $unsubscribedSubscriber->subscribe(
      'second',
      SIMPLENEWS_SUBSCRIPTION_STATUS_UNSUBSCRIBED,
      'test',
      $last_week
    );
    $unsubscribedSubscriber->save();
  }

  /**
   * Tests that a subscriber added within the deletion threshold survives cron.
   */
  public function testSubscriberAddedBeforeTheExpirationTimeShouldSurvive() {
    $subscribers = $this->loadSubscribersByProperties(['mail' => 'test1@test.com']);
    $this->assertCount(1, $subscribers);
    \Drupal::service('cron')->run();
    $subscribers = $this->loadSubscribersByProperties(['mail' => 'test1@test.com']);
    $this->assertCount(1, $subscribers);
  }

  /**
   * Tests that a subscribed subscriber survives the cron run.
   */
  public function testSubscriberWithAllConfirmedSubscriptionsShouldSurvive() {
    $subscribers = $this->loadSubscribersByProperties(['mail' => 'test2@test.com']);
    $this->assertCount(1, $subscribers);
    \Drupal::service('cron')->run();
    $subscribers = $this->loadSubscribersByProperties(['mail' => 'test2@test.com']);
    $this->assertCount(1, $subscribers);
  }

  /**
   * Tests that a mixed-status subscriber survives the cron run.
   */
  public function testSubscriberWithMixedConfirmationStatusSubscriptionsShouldSurvive() {
    $subscribers = $this->loadSubscribersByProperties(['mail' => 'test3@test.com']);
    $this->assertCount(1, $subscribers);
    \Drupal::service('cron')->run();
    $subscribers = $this->loadSubscribersByProperties(['mail' => 'test3@test.com']);
    $this->assertCount(1, $subscribers);
  }

  /**
   * Tests that a wholly unconfirmed subscriber does not survive the cron run.
   */
  public function testSubscriberWithAllUnconfirmedSubscriptionsShouldPerish() {
    $subscribers = $this->loadSubscribersByProperties(['mail' => 'test4@test.com']);
    $this->assertCount(1, $subscribers);
    \Drupal::service('cron')->run();
    $subscribers = $this->loadSubscribersByProperties(['mail' => 'test4@test.com']);
    $this->assertCount(0, $subscribers);
  }

  /**
   * Tests that an unsubscribed subscriber survives the cron run.
   */
  public function testUnsubscribedSubscriberShouldSurvive() {
    $subscribers = $this->loadSubscribersByProperties(['mail' => 'test5@test.com']);
    $this->assertCount(1, $subscribers);
    \Drupal::service('cron')->run();
    $subscribers = $this->loadSubscribersByProperties(['mail' => 'test5@test.com']);
    $this->assertCount(1, $subscribers);
  }

  /**
   * Loads subscriber entities selected by an array of properties.
   *
   * @param array $properties
   *   The properties to filter the subsribers by.
   *
   * @return mixed
   *   The found subscribers if any.
   */
  private function loadSubscribersByProperties(array $properties) {
    return \Drupal::entityTypeManager()
      ->getStorage('simplenews_subscriber')
      ->loadByProperties($properties);
  }

}
