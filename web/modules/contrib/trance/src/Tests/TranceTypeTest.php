<?php

namespace Drupal\trance\Tests;

/**
 * Ensures that trance type functions work correctly.
 *
 * @group trance
 */
class TranceTypeTest extends TranceTestBase {

  /**
   * Modules to enable.
   */
  public static $modules = ['field_ui'];

  /**
   * Tests creating a content type programmatically and via a form.
   */
  public function testTranceTypeCreation() {
    $type_exists = (bool) \Drupal::entityManager()->getStorage($this->bundleEntityTypeId)->load('trance_basic');
    $this->assertTrue($type_exists, 'The trance_basic type has been found in the database.');

    // Create a content type programmatically.
    $type = $this->drupalCreateTranceType(['type' => 'test']);

    $type_exists = (bool) \Drupal::entityManager()->getStorage($this->bundleEntityTypeId)->load($type->id());
    $this->assertTrue($type_exists, 'The new trance type has been created in the database.');

    // Login a test user.
    $web_user = $this->drupalCreateUser([
      $this->getPermission('view'),
      $this->getPermission('update'),
      $this->getPermission('add'),
    ]);
    $this->drupalLogin($web_user);

    $this->drupalGet('admin/content/' . $this->entityTypeId . '/add/' . $type->id());
    $this->assertResponse(200, 'The new trance type can be accessed at admin/content/' . $this->entityTypeId . '/add/' . $type->id());

    // Create a trance type via the user interface.
    $web_user = $this->drupalCreateUser([
      $this->getPermission('view'),
      $this->getPermission('update'),
      $this->getPermission('add'),
      $this->getPermission('administer'),
      $this->getPermission('administer types'),
    ]);
    $this->drupalLogin($web_user);

    $this->drupalGet('admin/content/' . $this->entityTypeId . '/add');
    $this->assertCacheContext('user.permissions');

    $elements_before = $this->cssSelect('ul.trance-type-list li');

    $edit = [
      'label' => 'foo',
      'id' => 'foo',
    ];
    $this->drupalPostForm('admin/structure/' . $this->bundleEntityTypeId . '/add', $edit, t('Save'));
    $type_exists = (bool) \Drupal::entityManager()->getStorage($this->bundleEntityTypeId)->load('foo');
    $this->assertTrue($type_exists, 'The new type has been created in the database.');

    $this->drupalGet('admin/content/' . $this->entityTypeId . '/add');
    $elements_after = $this->cssSelect('ul.trance-type-list li');
    if ($elements_before == 0) {
      $this->assertEqual(count($elements_after), 1, 'New type added to list.');
    }
    else {
      $this->assertEqual(count($elements_after), count($elements_before) + 1, 'New type added to list.');
    }
  }

  /**
   * Tests editing a trance type using the UI.
   */
  public function testTranceTypeEditing() {
    $web_user = $this->drupalCreateUser([
      $this->getPermission('add'),
      $this->getPermission('administer types'),
    ]);
    $this->drupalLogin($web_user);

    $this->drupalCreateTranceType(['type' => 'trance_test']);

    // Verify that name and @todo fields are displayed.
    $this->drupalGet('admin/content/' . $this->entityTypeId . '/add/trance_test');
    $this->assertRaw('Admin name', 'Admin name field was found.');

  }

}
