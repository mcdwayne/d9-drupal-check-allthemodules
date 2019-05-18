<?php

namespace Drupal\cacheflush_advanced\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Test cacheflush advanced functionality.
 *
 * @group cacheflush
 */
class CacheFlushAdvancedTest extends WebTestBase {

  /**
   * A user with permission to administer feeds and create content.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $User;

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['cacheflush_advanced'];

  /**
   * Sets up the test.
   */
  protected function setUp() {
    parent::setUp(self::$modules);

    $roles = [
      'cacheflush create new',
      'cacheflush view own',
      'cacheflush edit own',
      'cacheflush delete own',
    ];
    $user = $this->createUser($roles);
    $this->drupalLogin($user);
  }

  /**
   * Tests CRUD functions for cacheflush entity.
   */
  public function testUi() {
    $this->interfaceErrorrs();
    $this->interfaceCrud();
  }

  /**
   * Check if errors are generated correctly.
   */
  private function interfaceErrorrs() {
    // Check interface has all fields.
    $this->drupalGet('admin/structure/cacheflush/add');
    $this->assertRaw(t('Custom (advanced)'));
    $this->assertFieldByName('vertical_tabs_advance[cacheflush_advanced_table][0][cid]');
    $this->assertFieldByName('vertical_tabs_advance[cacheflush_advanced_table][0][table]');
    $this->assertFieldByName('advanced_remove_0');
    $this->assertFieldByName('advance_add');

    // Test NO error generate if nothing completet on the advanced form.
    $this->drupalPostForm(NULL, [], t('Save'));
    $this->assertNoRaw(t('Cache ID is required!'));
    $this->assertNoRaw(t('Service is required!'));

    // Test if CID sett and service not, is generating error.
    $this->drupalPostForm(NULL, ['vertical_tabs_advance[cacheflush_advanced_table][0][cid]' => 'Test 1'], t('Save'));
    $this->assertNoRaw(t('Cache ID is required!'));
    $this->assertRaw(t('Service is required'));

    // Test if CID not and service sett, is generating error.
    $this->drupalPostForm(NULL, [
      'vertical_tabs_advance[cacheflush_advanced_table][0][cid]' => '',
      'vertical_tabs_advance[cacheflush_advanced_table][0][table]' => 'menu',
    ], t('Save'));
    $this->assertRaw(t('Cache ID is required!'));
    $this->assertNoRaw(t('Service is required'));

    // Test NO error on advanced / save fail because no title sett.
    $this->drupalPostForm(NULL, [
      'vertical_tabs_advance[cacheflush_advanced_table][0][cid]' => 'TEST',
      'vertical_tabs_advance[cacheflush_advanced_table][0][table]' => 'menu',
    ], t('Save'));
    $this->assertNoRaw(t('Cache ID is required!'));
    $this->assertNoRaw(t('Service is required'));
    $this->assertRaw(t('Title field is required.'));
  }

  /**
   * Test add/remove on ajax form.
   */
  private function interfaceCrud() {
    $this->drupalGet('admin/structure/cacheflush/add');

    $this->drupalPostAjaxForm(NULL, [], 'advance_add');
    $this->assertFieldByName('vertical_tabs_advance[cacheflush_advanced_table][1][cid]');
    $this->assertFieldByName('vertical_tabs_advance[cacheflush_advanced_table][1][table]');
    $this->assertFieldByName('advanced_remove_1');

    $this->drupalPostAjaxForm(NULL, [], 'advance_add');
    $this->assertFieldByName('vertical_tabs_advance[cacheflush_advanced_table][2][cid]');
    $this->assertFieldByName('vertical_tabs_advance[cacheflush_advanced_table][2][table]');
    $this->assertFieldByName('advanced_remove_2');

    $this->drupalPostAjaxForm(NULL, [], 'advance_add');
    $this->assertFieldByName('vertical_tabs_advance[cacheflush_advanced_table][3][cid]');
    $this->assertFieldByName('vertical_tabs_advance[cacheflush_advanced_table][3][table]');
    $this->assertFieldByName('advanced_remove_3');

    $this->drupalPostAjaxForm(NULL, [], 'advanced_remove_1');
    $this->assertFieldByName('advanced_remove_0');
    $this->assertNoFieldByName('advanced_remove_1');
    $this->assertFieldByName('advanced_remove_2');

    $this->drupalPostAjaxForm(NULL, ['title' => 'Test 1'], 'advanced_remove_0');
    $this->assertNoFieldByName('advanced_remove_0');
    $this->assertNoFieldByName('advanced_remove_1');
    $this->assertFieldByName('advanced_remove_2');

    $this->drupalPostAjaxForm(NULL, ['title' => 'Test 1'], 'advanced_remove_2');
    $this->assertNoFieldByName('advanced_remove_0');
    $this->assertNoFieldByName('advanced_remove_1');
    $this->assertNoFieldByName('advanced_remove_2');

    $this->drupalPostAjaxForm(NULL, [], 'advance_add');
    $this->assertFieldByName('advanced_remove_4');

    $this->drupalPostForm(NULL, [
      'title' => 'Test 1',
      'vertical_tabs_advance[cacheflush_advanced_table][4][cid]' => 'TEST',
      'vertical_tabs_advance[cacheflush_advanced_table][4][table]' => 'menu',
    ], t('Save'));

    $entities = array_values(cacheflush_load_multiple_by_properties(['title' => 'Test 1']));
    $this->assertEqual($entities[0]->getTitle(), 'Test 1', 'Entity successfully created.');

    // Check if entity create on interface.
    $this->drupalGet('cacheflush/' . $entities[0]->id() . '/edit');
    $this->assertFieldByName('vertical_tabs_advance[cacheflush_advanced_table][4][cid]', 'TEST');
    $this->assertFieldByName('vertical_tabs_advance[cacheflush_advanced_table][4][table]', 'menu');
  }

}
