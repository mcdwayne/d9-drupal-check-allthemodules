<?php
declare(strict_types=1);

namespace Drupal\Tests\membership_entity\Functional;

/**
 * Test creating and managing membership types.
 *
 * @group MembershipEntity
 */
class MembershipTypeTest extends MembershipTestBase {
  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Test creating a membership type.
   */
  public function testCreateMembershipType() {

  }
}
