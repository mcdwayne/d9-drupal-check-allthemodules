<?php

namespace Drupal\Tests\breadcrumb_extra_field\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests for breadcrumb extra field.
 *
 * @group breadcrumb_extra_field
 */
class BreadcrumbExtraFieldTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'field',
    'field_ui',
    'node',
    'breadcrumb_extra_field',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $type = $this->container->get('entity_type.manager')->getStorage('node_type')
      ->create([
        'type' => 'article',
        'name' => 'Article',
      ]);
    $type->save();
    $this->container->get('router.builder')->rebuild();
  }

  /**
   * Tests that the admin UI configuration options works.
   */
  public function testAdminUi() {

    // Check not allowed access.
    $account = $this->drupalCreateUser();
    $this->drupalLogin($account);
    $this->drupalGet('admin/config/system/breadcrumb-extra-field');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalLogout();

    $account = $this->drupalCreateUser(['administer breadcrumb extra field'], NULL, TRUE);
    $this->drupalLogin($account);

    // Check extra field visibility before configuration.
    $this->drupalGet('admin/structure/types/manage/article/display');
    $this->assertSession()->elementNotExists('xpath', '//tr[@data-drupal-selector="edit-fields-breadcrumb"]');

    // Check allowed access.
    $this->drupalGet('admin/config/system/breadcrumb-extra-field');
    $this->assertSession()->statusCodeEquals(200);

    // Check field to created content type.
    $this->assertSession()->fieldExists('Article');

    // Enable to articles.
    $this->submitForm(['Article' => TRUE], 'Save configuration');

    // Clear cache required.
    $this->resetAll();

    // Check extra field visibility after configuration.
    $this->drupalGet('admin/structure/types/manage/article/display');
    $this->assertSession()->elementExists('xpath', '//tr[@data-drupal-selector="edit-fields-breadcrumb"]');
    // Check label.
    $this->assertSession()->elementTextContains('css', '.entity-view-display-edit-form', 'Breadcrumb');
  }

  /**
   * Tests display visibility.
   */
  public function testDisplayVisivility() {

    // Create a node.
    $node = $this->drupalCreateNode(['type' => 'article']);
    $this->drupalGet('node/' . $node->id());

    // Check visibility before everything.
    $this->assertSession()->elementNotExists('xpath', '//article[contains(@class, "node--type-article") and contains(.//nav, "Breadcrumb")]');

    $account = $this->drupalCreateUser(['administer breadcrumb extra field'], NULL, TRUE);
    $this->drupalLogin($account);

    // Enable to articles.
    $this->drupalGet('admin/config/system/breadcrumb-extra-field');
    $this->submitForm(['Article' => TRUE], 'Save configuration');
    // Clear cache required.
    $this->resetAll();

    $this->drupalLogout();

    // Check visibility after module configuration but before display
    // configuration.
    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->elementNotExists('xpath', '//article[contains(@class, "node--type-article") and contains(.//nav, "Breadcrumb")]');

    // Make breadcrumb visible.
    $display = entity_get_display('node', 'article', 'default');
    $display->setComponent('breadcrumb', ['region' => 'content']);
    $display->save();

    // Check visibility.
    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->elementExists('xpath', '//article[contains(@class, "node--type-article") and contains(.//nav, "Breadcrumb")]');
  }

}
