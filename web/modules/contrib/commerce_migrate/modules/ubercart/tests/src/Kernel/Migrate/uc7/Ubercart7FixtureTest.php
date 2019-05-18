<?php

namespace Drupal\Tests\commerce_migrate_ubercart\Kernel\Migrate\uc7;

use Drupal\user\Entity\User;

/**
 * Test that ensures fixture can be installed.
 *
 * @group commerce_migrate
 * @group commerce_migrate_uc7;
 */
class Ubercart7FixtureTest extends Ubercart7TestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->executeMigrations([
      'd7_user_role',
      'd7_user',
    ]);
  }

  /**
   * If the fixture installed, this will pass.
   */
  public function testItWorked() {
    $user = User::load(2);
    $this->assertEquals('tomparis', $user->getAccountName());
  }

}
