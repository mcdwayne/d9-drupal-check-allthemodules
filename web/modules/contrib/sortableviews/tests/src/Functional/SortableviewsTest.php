<?php

namespace Drupal\Tests\sortableviews\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Verifies form of sortableviews.
 *
 * @group sortableviews
 */
class SortableviewsTest extends BrowserTestBase {

  /**
   * The privileged user performing the actions.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $user;

  /**
   * Required modules.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  public static $modules = [
    'node',
    'user',
    'sortableviews',
    'sortableviews_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->user = $this->drupalCreateUser([
      'access content',
    ]);
    for ($aux = 1; $aux <= 3; $aux++) {
      $this->drupalCreateNode([
        'type' => 'test',
      ]);
    }
  }

  /**
   * Tests sortableviews key elements exist.
   *
   * @dataProvider dataProvider
   */
  public function testElements($path) {
    $this->drupalLogin($this->user);
    $this->drupalGet($path);
    $this->assertElementPresent('.sortableviews-handle');
    $this->assertElementPresent('.sortableviews-save-changes');
    $this->drupalLogout();
  }

  /**
   * Provides data for testAccess().
   */
  public function dataProvider() {
    $data = [];
    $data[] = ['ul'];
    $data[] = ['ol'];
    $data[] = ['unformatted'];
    $data[] = ['table'];
    return $data;
  }

}
