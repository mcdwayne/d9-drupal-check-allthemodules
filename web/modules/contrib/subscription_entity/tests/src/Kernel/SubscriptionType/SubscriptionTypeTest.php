<?php

namespace Drupal\Tests\subscription\Kernel\SubscriptionType;

use Drupal\Tests\subscription\Kernel\SubscriptionKernelTestBase;

/**
 * Tests the general behavior of subscription type entities.
 *
 * @coversDefaultClass \Drupal\subscription_entity\Entity\subscriptionType
 * @group subscription
 */
class SubscriptionTypeTest extends SubscriptionKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
  }

  /**
   * Tests the getSiteRoles method.
   *
   * @covers ::getSiteRoles
   */
  public function testGetSiteRoles() {
    $roles = $this->subscriptionTypeEntity->getSiteRoles();
    $this->assertArrayHasKey('authenticated_user', $roles);
    $this->assertArrayHasKey('premium', $roles);
  }

  /**
   * Tests if a subscription type is created.
   */
  public function testSubscriptionTypeCreated() {
    $this->assertInstanceOf('Drupal\subscription_entity\Entity\subscriptionType', $this->subscriptionTypeEntity);
  }

}
