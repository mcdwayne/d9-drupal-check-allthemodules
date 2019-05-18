<?php
namespace Drupal\care\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests the CARE module functionality
 *
 * @group demo
 */
class AdminFormTest extends WebTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = array(
    'care');

  /**
   * The installation profile to use with this test.
   *
   * @var string
   */
  protected $profile = 'minimal';

  /**
   * Tests the admin settings form permissions.
   */
  public function testFormPagePermissions() {
    // As anonymous user.
    $this->drupalGet('admin/config/services/care/settings');
    $this->assertResponse(403);
    $this->drupalGet('admin/config/services/care/call');
    $this->assertResponse(403);
    // As basic authenticated user.
    $user = $this->createUser();
    $this->drupalLogin($user);
    $this->drupalGet('admin/config/services/care/settings');
    $this->assertResponse(403);
    $this->drupalGet('admin/config/services/care/call');
    $this->assertResponse(403);
    // As user with required permission.
    $user = $this->createUser(array('administer care module'));
    $this->drupalLogin($user);
    $this->drupalGet('admin/config/services/care/settings');
    $this->assertResponse(200);
    $this->drupalGet('admin/config/services/care/call');
    $this->assertResponse(200);
  }
}
