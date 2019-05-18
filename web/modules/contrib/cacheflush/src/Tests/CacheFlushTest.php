<?php

namespace Drupal\cacheflush\Tests;

use Drupal\cacheflush\Controller\CacheflushApi;
use Drupal\simpletest\WebTestBase;

/**
 * Test cacheflush API.
 *
 * @group cacheflush
 */
class CacheFlushTest extends WebTestBase {

  /**
   * User of test.
   *
   * @var \Drupal\Core\Session\AccountInterface|bool
   */
  protected $testUser;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['cacheflush'];

  /**
   * Sets up the test.
   */
  protected function setUp() {
    parent::setUp(self::$modules);
    $this->testUser = $this->drupalCreateUser(['cacheflush clear cache']);
  }

  /**
   * Run test functions.
   */
  public function testMenu() {
    $this->menuAccessAnonymusUser();
    $this->clearPresetMenu();
  }

  /**
   * Check menus access.
   */
  public function menuAccessAnonymusUser() {
    // Check access of the menus - access denied expected - Anonymus user.
    $this->drupalGet('admin/cacheflush');
    $this->assertResponse(403);

    $this->drupalGet('admin/cacheflush/clear/all');
    $this->assertResponse(403);

    // No entity created yet, the route will try to load entity - 404 will be
    // returned by Entity Manager.
    $this->drupalGet('admin/cacheflush/clear/1');
    $this->assertResponse(404);
  }

  /**
   * Check clear cache.
   */
  public function clearPresetMenu() {

    $this->createTestEntitys();

    $enabled = array_values(cacheflush_load_multiple_by_properties([
      'title' => 'Enabled',
      'status' => 1,
    ]));
    $this->assertEqual($enabled[0]->getTitle(), 'Enabled', 'Created and loaded entity: enabled.');
    $disabled = array_values(cacheflush_load_multiple_by_properties([
      'title' => 'Disabled',
      'status' => 0,
    ]));
    $this->assertEqual($disabled[0]->getTitle(), 'Disabled', 'Created and loaded entity: disabled.');

    $this->drupalLogin($this->testUser);

    // Check access of the menus - access TRUE expected.
    $this->drupalGet('admin/cacheflush');
    $this->assertResponse(200);

    $this->drupalGet('admin/cacheflush/clear/all');
    $this->assertResponse(200);

    $this->drupalGet('admin/cacheflush/clear/' . $enabled[0]->id());
    $this->assertResponse(200);

    // Check if the disabled entity will be refused.
    $this->drupalGet('admin/cacheflush/clear/' . $disabled[0]->id());
    $this->assertResponse(403);

    $this->drupalLogout();
  }

  /**
   * Create cacheflush test entities.
   */
  public function createTestEntitys() {
    $data = [];
    foreach (CacheflushApi::create(\Drupal::getContainer())
      ->getOptionList() as $key => $value) {
      $data[$key]['functions'] = $value['functions'];
    }
    $data = serialize($data);

    $entity = cacheflush_create([
      'title' => 'Enabled',
      'status' => 1,
      'data' => $data,
    ]);
    $entity->save();
    $entity = cacheflush_create([
      'title' => 'Disabled',
      'status' => 0,
      'data' => $data,
    ]);
    $entity->save();
  }

}
