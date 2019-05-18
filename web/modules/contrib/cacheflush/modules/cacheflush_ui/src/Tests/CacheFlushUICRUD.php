<?php

namespace Drupal\cacheflush_ui\Tests;

use Drupal\cacheflush\Controller\CacheflushApi;
use Drupal\simpletest\WebTestBase;

/**
 * Test the UI CRUD.
 *
 * @group cacheflush
 */
class CacheFlushUICRUD extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['cacheflush_ui'];

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
    parent::setUp(self::$modules);
    $user = $this->drupalCreateUser(self::$roles);
    $this->drupalLogin($user);
  }

  /**
   * Run CRUD test functions.
   */
  public function testCrud() {
    $this->addInterfaceIntegrity();
    $this->fieldRequiring();
    $this->crudTest();
  }

  /**
   * Test if add interface has all buttons/links/etc.
   */
  public function addInterfaceIntegrity() {
    $this->drupalGet('admin/structure/cacheflush/add');
    $this->assertResponse(200);

    $this->assertFieldByName('title');
    $this->assertFieldByName('op', t('Save'));

    $tabs = \Drupal::moduleHandler()->invokeAll('cacheflush_ui_tabs');
    if ($tabs) {
      foreach ($tabs as $key => $value) {
        $this->assertText($value['name']);
      }
    }

    $checkboxes = CacheflushApi::create(\Drupal::getContainer())
      ->getOptionList();
    if ($checkboxes) {
      foreach ($checkboxes as $key => $value) {
        $this->assertFieldByName($value['category'] . "[$key]");
      }
    }
  }

  /**
   * Check if form requring is working on title field.
   */
  public function fieldRequiring() {
    $this->drupalPostForm('admin/structure/cacheflush/add', [], t('Save'));
    $this->assertResponse(200);
    $this->assertRaw('error');
  }

  /**
   * Test CRUD.
   */
  public function crudTest() {
    // Test Entity create.
    $data = [
      'title' => 'NewEntityTitle',
      'vertical_tabs_core[bootstrap]' => 1,
      'vertical_tabs_core[config]' => 1,
    ];
    $this->drupalPostForm('admin/structure/cacheflush/add', $data, t('Save'));

    $entities = array_values(cacheflush_load_multiple_by_properties(['title' => 'NewEntityTitle']));
    $this->assertEqual($entities[0]->getTitle(), 'NewEntityTitle', 'Entity successfully created.');

    // Test Entity update.
    $data2 = [
      'title' => 'UpdatedEntityTitle',
      'vertical_tabs_core[default]' => 1,
      'vertical_tabs_core[config]' => FALSE,
    ];
    $this->drupalGet('cacheflush/' . $entities[0]->id() . '/edit');
    $this->assertFieldByName('vertical_tabs_core[bootstrap]', 1, 'Entity 1: vertical_tabs_core[bootstrap] value appears correctly in the form.');
    $this->assertFieldByName('vertical_tabs_core[config]', 1, 'Entity 1: vertical_tabs_core[config] value appears correctly in the form.');
    $this->assertFieldByName('vertical_tabs_core[default]', 0, 'Entity 1: vertical_tabs_core[default] value appears correctly in the form.');

    $this->drupalPostForm('cacheflush/' . $entities[0]->id() . '/edit', $data2, t('Save'));

    \Drupal::entityTypeManager()
      ->getStorage('cacheflush')
      ->resetCache([$entities[0]->id()]);
    $entities = array_values(cacheflush_load_multiple_by_properties(['title' => 'UpdatedEntityTitle']));
    $this->assertEqual($entities[0]->getTitle(), 'UpdatedEntityTitle', 'Entity successfully updated.');

    $this->drupalGet('cacheflush/' . $entities[0]->id() . '/edit');
    $this->assertFieldByName('vertical_tabs_core[bootstrap]', 1, 'Entity 1: vertical_tabs_core[bootstrap] value appears correctly in the form.');
    $this->assertFieldByName('vertical_tabs_core[config]', 0, 'Entity 1: vertical_tabs_core[config] value appears correctly in the form.');
    $this->assertFieldByName('vertical_tabs_core[default]', 1, 'Entity 1: vertical_tabs_core[default] value appears correctly in the form.');

    // Test delete page and delete.
    $this->drupalGet('cacheflush/' . $entities[0]->id() . '/delete');
    $this->assertLink(t('Cancel'));
    $this->assertFieldByName('op', t('Delete'));
    $this->drupalPostForm(NULL, [], t('Delete'));
    $this->drupalGet('cacheflush/' . $entities[0]->id());
    $this->assertResponse(404);
  }

}
