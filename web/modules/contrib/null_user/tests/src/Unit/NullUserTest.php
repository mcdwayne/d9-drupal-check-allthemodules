<?php

namespace Drupal\Tests\null_user\Unit;

use Drupal\Core\Session\AccountInterface;
use Drupal\null_user\NullUser;
use Drupal\Tests\UnitTestCase;

/**
 * @group null_user
 */
class NullUserTest extends UnitTestCase {

  /**
   * Test the NullUser class.
   *
   * Ensure that it implements the correct interface and returns the correct
   * values.
   */
  public function testNullUser() {
    $user = new NullUser();

    // Ensure the AccountInterface is implemented.
    $this->assertInstanceOf(AccountInterface::class, $user);

    $this->assertNull($user->id());
    $this->assertEmpty($user->getAccountName());
    $this->assertNull($user->getEmail());
    $this->assertEmpty($user->getRoles());
    $this->assertFalse($user->hasPermission('access content'));
    $this->assertFalse($user->isAuthenticated());
    $this->assertTrue($user->isAnonymous());
  }

}
