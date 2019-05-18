<?php

namespace Drupal\Tests\role_paywall\Functional;

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\role_paywall\Functional\RolePaywallTestBase;

/**
 * Test for the Role paywall content access.
 *
 * @group role_paywall
 */
class RolePaywallContentTest extends RolePaywallTestBase {

  public function setUp() {
    parent::setUp();

    $this->createTestNodes();
  }


  /**
   * Checks without any paywall setting we can properly access to the content.
   */
  public function testContentWithoutPaywall() {
    $this->drupalGet($this->testNodePublic->toUrl()->toString());
    $this->assertSession()->pageTextContains($this->testNodeTitle);
    $this->assertSession()->pageTextContains($this->testNodePremiumText);
  }

  /**
   * Checks with setting content as premium that anonymous cannot access.
   */
  public function testContentWithtPaywallNotAccess() {
    $this->setConfig();
    $this->drupalGet($this->testNodePremium->toUrl()->toString());
    $this->assertSession()->pageTextContains($this->testNodeTitle);
    $this->assertSession()->pageTextNotContains($this->testNodePremiumText);
  }

  /**
   * Checks with setting content as premium that role with access have access.
   */
  public function testContentWithtPaywallHaveAccess() {
    $this->setConfig();
    $this->drupalLogin($this->adminUser);
    $this->drupalGet($this->testNodePremium->toUrl()->toString());
    $this->assertSession()->pageTextContains($this->testNodeTitle);
    $this->assertSession()->pageTextContains($this->testNodePremiumText);
  }

}
