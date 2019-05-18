<?php

namespace Drupal\Tests\cached_computed_field\Kernel;

use Drupal\cached_computed_field\ExpiredItem;
use Drupal\cached_computed_field_test\EventSubscriber\RefreshExpiredFieldsTestSubscriber;
use Drupal\Core\Entity\EntityInterface;

/**
 * Tests the base class of the event subscriber.
 *
 * @coversDefaultClass \Drupal\cached_computed_field\EventSubscriber\RefreshExpiredFieldsSubscriberBase
 * @group cached_computed_field
 */
class RefreshExpiredFieldsSubscriberBaseTest extends KernelTestBase {

  /**
   * The event subscriber under test.
   *
   * @var \Drupal\cached_computed_field_test\EventSubscriber\RefreshExpiredFieldsTestSubscriber
   */
  protected $subscriber;

  /**
   * The time the test started.
   *
   * @var int
   */
  protected $currentTime;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Instantiate the event subscriber being tested with a mocked time service
    // that is under our control.
    $this->currentTime = time();
    $this->subscriber = new RefreshExpiredFieldsTestSubscriber($this->container->get('entity_type.manager'), new MockTimeService($this->currentTime));
    $this->container->set('cached_computed_field_test.test_event_subscriber', $this->subscriber);
  }

  /**
   * @covers ::fieldNeedsRefresh
   * @dataProvider fieldNeedsRefreshProvider
   */
  public function testFieldNeedsRefresh(bool $expected_result, int $relative_expire_time, int $previous_value = NULL) {
    $entity = $this->createEntity($this->currentTime + $relative_expire_time, $previous_value);
    $expired_item = $this->getExpiredItem($entity);
    $this->assertEquals($expected_result, $this->subscriber->fieldNeedsRefresh($expired_item));
  }

  /**
   * Data provider for ::testFieldNeedsRefresh().
   *
   * @return array
   *   An array of test cases. Each test case is an array with the following
   *   elements:
   *   - A boolean representing the expected test result.
   *   - An integer representing the time the test entity is expiring, relative
   *     to the current time, in seconds.
   *   - An optional integer representing the value which has been set on the
   *     item during a previous cache update.
   */
  public function fieldNeedsRefreshProvider() {
    return [
      // A new (unpopulated) entity with a expire time in the future.
      [
        // Unpopulated entities are always ready to be refreshed.
        TRUE,
        // Expires in 1 day.
        24*60*60,
        // No data yet.
        NULL,
      ],
      // A new (unpopulated) entity with a expire time in the past.
      [
        // Unpopulated entities are always ready to be refreshed.
        TRUE,
        // Expired 2 hours ago.
        -2*60*60,
        // No data yet.
        NULL,
      ],
      // A previously populated entity with a expire time in the future.
      [
        // Entity is not expired, should not be refreshed.
        FALSE,
        // Expires in 1 day.
        24*60*60,
        // Previous value.
        12,
      ],
      // A previously populated entity that has expired.
      [
        // Item has expired, should be refreshed.
        TRUE,
        // Expired 2 hours ago.
        -2*60*60,
        // Previous value.
        -9999,
      ],
      // An unexpired previously populated entity that has the existing value 0.
      [
        // Entity is not expired, should not be refreshed.
        FALSE,
        // Expires in 12 hours.
        12*60*60,
        // Previous value.
        0,
      ],
      // An expired previously populated entity that has the existing value 0.
      [
        // Item has expired, should be refreshed.
        TRUE,
        // Expired 5 hours ago.
        -5*60*60,
        // Previous value.
        0,
      ],
    ];
  }

  /**
   * Creates an ExpiredItem object for the given entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity for which to create the expired item.
   *
   * @return \Drupal\cached_computed_field\ExpiredItem
   *   The expired item.
   */
  protected function getExpiredItem(EntityInterface $entity) {
    return new ExpiredItem($entity->getEntityTypeId(), $entity->id(), self::TEST_FIELD);
  }

  /**
   * Creates a test entity with the given values.
   *
   * @param int $expire_time
   *   The UNIX timestamp when the data on the entity expires.
   * @param int|NULL $value
   *   Optional value to set on the cached computed field.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface
   *   The test entity.
   */
  protected function createEntity(int $expire_time, int $value = NULL) {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $this->entityStorage->create([
      'title' => $this->randomMachineName(),
      'type' => 'entity_test',
      'expire' => $expire_time,
    ]);
    $entity->set(self::TEST_FIELD, [
      'value' => $value,
      'expire' => $expire_time,
    ]);
    $entity->save();
    return $entity;
  }

}
