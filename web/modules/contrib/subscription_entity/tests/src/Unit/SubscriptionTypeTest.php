<?php

namespace Drupal\Tests\subscription\Unit;

use Drupal\Tests\UnitTestCase;

/**
 * Tests generation of a subscription type.
 *
 * @coversDefaultClass \Drupal\subscription_entity\Entity\subscriptionType
 * @group subscription
 */
class SubscriptionTypeTest extends UnitTestCase {

  /**
   * @covers ::getSiteRoles
   */
  public function testGetSiteRoles() {
    $subscriptionType = $this->getMock('Drupal\subscription_entity\Entity\subscriptionTypeInterface');
    $subscriptionType->expects($this->never())->method('getSiteRoles')->willReturn(array());
  }

}
