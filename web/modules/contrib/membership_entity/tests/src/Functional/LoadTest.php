<?php
declare(strict_types=1);

namespace Drupal\Tests\membership_entity\Functional;

use Drupal\Core\Url;

/**
 * Simple test to ensure that main pages load with module enabled.
 *
 * @group MembershipEntity
 */
class LoadTest extends MembershipTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests that the membership admin dashboard loads with a 200 response.
   */
  public function testLoadDashboard() {
    $this->drupalGet(Url::fromRoute('membership_entity.admin'));
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Tests that the membership type page loads with a 200 response.
   */
  public function testLoadMembershipTypes() {
    $this->drupalGet(Url::fromRoute('entity.membership_entity_type.collection'));
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Tests that the membership list page loads with a 200 response.
   */
  public function testLoadMembershipList() {
    $this->drupalGet(Url::fromRoute('entity.membership_entity.collection'));
    $this->assertSession()->statusCodeEquals(200);
  }

}
