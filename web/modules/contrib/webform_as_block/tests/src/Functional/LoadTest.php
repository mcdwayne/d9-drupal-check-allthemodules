<?php

namespace Drupal\Tests\webform_as_block\Functional;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Simple test to ensure that configuration page loads with module enabled.
 *
 * @group webform_as_block
 */
class LoadTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['webform', 'webform_as_block'];

  /**
   * A user with permission to administer site configuration.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
  }

  public function testWebformBlockTab() {
    $account = $this->drupalCreateUser(['administer webform']);
    $this->drupalLogin($account);

    $this->drupalGet('/admin/structure/webform/blocks');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseContains('Available webforms');
  }
}
