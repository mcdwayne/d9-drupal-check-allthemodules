<?php

namespace Drupal\Tests\diba_carousel\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Test basic diba_carousel block functionality.
 *
 * @group diba_carousel
 */
class DibaCarouselBlockTests extends BrowserTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['filter', 'diba_carousel'];

  /**
   * An administrative user to configure the test environment.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create and login an administrative user.
    $this->adminUser = $this->drupalCreateUser([
      'administer site configuration',
      'access administration pages',
      'administer blocks',
    ]);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests diba carousel block.
   */
  public function testDibaCarouselBlock() {
    $default_theme = $this->config('system.theme')->get('default');

    // Tests block UI.
    $this->drupalGet('admin/structure/block/add/diba_carousel/' . $default_theme);
    $this->assertResponse(200, 'Block config UI diba_carousel works.');
  }

}
