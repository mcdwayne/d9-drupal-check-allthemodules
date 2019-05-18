<?php

/**
 * @file
 * Contains \Drupal\beta2beta\Tests\Update\Beta7UpdatePath.
 */

namespace Drupal\beta2beta\Tests\Update;

use Drupal\beta2beta\Tests\Update\TestTraits\FrontPage;
use Drupal\beta2beta\Tests\Update\TestTraits\NewNode;
use Drupal\user\Entity\Role;

/**
 * Tests the beta 7 update path.
 *
 * @group beta2beta
 */
class Beta7UpdatePath extends Beta2BetaUpdateTestBase {

  use FrontPage;
  use NewNode;

  /**
   * Turn off strict config schema checking.
   *
   * This has to be turned off since there are multiple update hooks that update
   * views. Since only the final view save will be compliant with the current
   * schema, an exception would be thrown on the first view to be saved if this
   * were left on.
   */
  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  protected static $startingBeta = 7;

  /**
   * Test that the admin role flag gets set.
   */
  public function testUpdate8804() {
    // Make sure the admin role is configured as expected.
    $user_settings = \Drupal::configFactory()->getEditable('user.settings');
    $this->assertEqual($user_settings->get('admin_role'), 'administrator', 'The admin_role is set to administrator before updates.');
    $this->runUpdates();
    $user_settings = \Drupal::configFactory()->getEditable('user.settings');
    $this->assertNull($user_settings->get('admin_role'), 'The admin_role setting has been removed.');
    $admin_role = Role::load('administrator');
    $this->assertTrue($admin_role->isAdmin(), 'The admin role flag is properly set on the administrator role.');
    $authenticated_user_role = Role::load('authenticated');
    $this->assertFalse($authenticated_user_role->isAdmin(), 'Authenticated user role remains a non-admin role.');
  }

}
