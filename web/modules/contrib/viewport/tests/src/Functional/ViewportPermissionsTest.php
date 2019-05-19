<?php

namespace Drupal\Tests\viewport\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the Viewport permissions exist.
 *
 * @group viewport
 */
class ViewportPermissionsTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['viewport'];

  /**
   * A user account with permission to edit viewport settings.
   *
   * @var \Drupal\user\Entity\User
   */
  private $userWithViewportPerm;

  /**
   * A user account without permission to edit viewport settings.
   *
   * @var \Drupal\user\Entity\User
   */
  private $userWithoutViewportPerm;

  /**
   * Sets up the required environment for the tests.
   */
  public function setUp() {
    parent::setUp();

    $this->userWithViewportPerm = $this->drupalCreateUser(array('administer viewport'));
    $this->userWithoutViewportPerm = $this->drupalCreateUser();
  }

  /**
   * Tests that there's a permission to administer the viewport settings.
   */
  public function testSettingsPageAccessPermission() {
    $viewportSettingsPath = 'admin/appearance/settings/viewport';

    $this->drupalLogin($this->userWithViewportPerm);
    $this->drupalGet($viewportSettingsPath);
    $this->assertEquals(200, $this->getSession()->getStatusCode());

    $this->drupalLogin($this->userWithoutViewportPerm);
    $this->drupalGet($viewportSettingsPath);
    $this->assertEquals(403, $this->getSession()->getStatusCode());
  }

}
