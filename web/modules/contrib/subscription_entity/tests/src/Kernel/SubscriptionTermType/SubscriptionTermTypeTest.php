<?php

namespace Drupal\Tests\subscription\Kernel\SubscriptionTermType;

use Drupal\Tests\subscription\Kernel\SubscriptionKernelTestBase;

/**
 * Tests the general behavior of subscription type entities.
 *
 * @coversDefaultClass \Drupal\subscription_entity\Entity\SubscriptionTermType
 * @group subscription
 */
class SubscriptionTermTypeTest extends SubscriptionKernelTestBase {

  /**
   * Tests the getSiteRoles method.
   */
  public function testSubscriptionTermTypeCreated() {
    $this->assertInstanceOf('Drupal\subscription_entity\Entity\SubscriptionTermType', $this->subscriptionTermTypeEntity);
  }

}
