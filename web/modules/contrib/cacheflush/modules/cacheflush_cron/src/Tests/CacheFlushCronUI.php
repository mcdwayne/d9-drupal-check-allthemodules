<?php

namespace Drupal\cacheflush_cron\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Test cacheflush Cron UI.
 *
 * @group cacheflush
 */
class CacheFlushCronUI extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['cacheflush_ui', 'cacheflush_cron'];

  /**
   * User roles.
   *
   * @var array
   */
  public static $roles = [
    'cacheflush create new',
    'cacheflush administer',
    'cacheflush view own',
    'cacheflush edit own',
    'cacheflush delete own',
  ];

  /**
   * Sets up the test.
   */
  public function setUp() {
    parent::setUp();
    $user = $this->drupalCreateUser(self::$roles);
    $this->drupalLogin($user);
  }

  /**
   * Run test functions.
   */
  public function testUi() {
    $this->addForm();
    $this->editForm();
  }

  /**
   * Add form test.
   */
  public function addForm() {
    $this->drupalGet('admin/structure/cacheflush/add');
    $this->assertResponse(200);
    $this->assertFieldByName('cron');
    $this->assertRaw(t('Enable cron job for this preset.'));
    $this->assertNoLink(t('EDIT'));
  }

  /**
   * Edit form test.
   */
  public function editForm() {
    $this->drupalGet('admin/structure/cacheflush/add');
    $this->assertResponse(200);
    $this->assertRaw(t('Enable cron job for this preset.'));

    // Test Entity create.
    $data = [
      'title' => 'NewEntityTitle',
      'cron' => 1,
    ];
    $this->drupalPostForm('admin/structure/cacheflush/add', $data, t('Save'));
    $entities = array_values(cacheflush_load_multiple_by_properties(['title' => 'NewEntityTitle']));

    $this->assertEqual($entities[0]->getTitle(), 'NewEntityTitle', 'Entity successfully created.');

    $this->drupalGet('cacheflush/' . $entities[0]->id() . '/edit');
    $this->assertResponse(200);
    $this->assertFieldByName('cron', 1, 'Cron checkbox is checked.');
    $this->assertLink(t('Edit'));

    $data['cron'] = 0;
    $this->drupalPostForm('cacheflush/' . $entities[0]->id() . '/edit', $data, t('Save'));
    $entities = array_values(cacheflush_load_multiple_by_properties(['title' => 'NewEntityTitle']));

    $this->drupalGet('cacheflush/' . $entities[0]->id() . '/edit');
    $this->assertResponse(200);
    $this->assertFieldByName('cron', 0, 'Cron checkbox is NOT checked.');

    // Test Entity create.
    $data = [
      'title' => 'NewEntityTitle2',
    ];

    // Test that the Edit link should not be in UI if no CronJob created yet.
    $this->drupalPostForm('admin/structure/cacheflush/add', $data, t('Save'));
    $entities = array_values(cacheflush_load_multiple_by_properties(['title' => 'NewEntityTitle2']));
    $this->assertEqual($entities[0]->getTitle(), 'NewEntityTitle2', 'Entity successfully created.');
    $this->drupalGet('cacheflush/' . $entities[0]->id() . '/edit');
    $this->assertResponse(200);
    $this->assertFieldByName('cron', 0, 'Cron checkbox is NOT checked.');
    $this->assertNoLink('Edit');
  }

}
