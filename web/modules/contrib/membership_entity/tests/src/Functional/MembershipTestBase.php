<?php
declare(strict_types=1);

namespace Drupal\Tests\membership_entity\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Sets up membership testing environment.
 */
abstract class MembershipTestBase extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['membership_entity'];

  /**
   * A user with permission to administer memberships.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->adminUser = $this->drupalCreateUser(['administer site configuration', 'administer membership entities']);
  }

}
