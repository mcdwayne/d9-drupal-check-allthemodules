<?php

namespace Drupal\entity_comparison\Tests;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Entity\Entity\EntityViewMode;
use Drupal\entity_comparison\Entity\EntityComparison;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\simpletest\WebTestBase;

/**
 * Tests the entity comparison functionality.
 *
 * @group Entity comparison
 */
class EntityComparisonTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var string[]
   */
  public static $modules = ['entity_comparison', 'field_ui', 'node'];

  /**
   * Don't check for or validate config schema.
   *
   * @var bool
   */
  protected $strictConfigSchema = FALSE;

  /** User with privileges to do everything. */
  protected $adminUser;

  /** This user is allowed to use the comparison list  */
  protected $allowed_user;

  /** This user is denied to use the comparison list  */
  protected $denied_user;

  /** Permissions for administrator user. */
  public static $adminPermissions = array(
    'access administration pages',
    'administer entity comparison',
    'bypass node access',
    'administer content types',
    'administer node fields',
    'administer node form display',
    'administer node display',
    'access content',
    'administer display modes',
  );

  /**
   * Test entity comparison.
   *
   * @var \Drupal\entity_comparison\Entity\EntityComparisonInterface
   */
  protected $entity_comparison;

  /** @var  Store product contents */
  protected $products = array();

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Collect admin permissions.
    $class = get_class($this);
    $adminPermissions = [];
    while ($class) {
      if (property_exists($class, 'adminPermissions')) {
        $adminPermissions = array_merge($adminPermissions, $class::$adminPermissions);
      }
      $class = get_parent_class($class);
    }

    // Create a store administrator user account.
    $this->adminUser = $this->drupalCreateUser($adminPermissions);

    $this->drupalLogin($this->adminUser);

    // Create product content type
    $this->drupalCreateContentType(['type' => 'product', 'name' => 'Product']);

    // Add fields to product content type
    $fields = array(
      'price' => array(
        'type' => 'string',
        'widget_type' => 'string_textfield',
      ),
      'sku' => array(
        'type' => 'string',
        'widget_type' => 'string_textfield',
      ),
    );

    $this->addFieldsToEntity('node', 'product', $fields);

    // Create product contents
    $this->createProducts();

    // Create a test entity comparison for product.
    $this->entity_comparison = EntityComparison::create(array(
      //'uid' => $this->adminUser->id(),
      'label' => 'Product comparison',
      'id' => 'product_comparison',
      'add_link_text' => 'Add product to comparison list',
      'remove_link_text' => 'Remove product from the comparison',
      'limit' => 2,
      'entity_type' => 'node',
      'bundle_type' => 'product',
    ))->save();

    // Create custom view mode
    $this->createViewMode();

    // Update custom view mode
    $this->updateViewMode();

    drupal_flush_all_caches();
    $this->drupalLogout();
  }

  protected function createViewMode() {
    // Generate an id for the view mode
    $view_mode_id = 'node.product_product_comparison';
    $display_id = 'product_product_comparison';

    // Create new entity view mode
    $entity_view_mode = EntityViewMode::create(array(
      'id' => $view_mode_id,
      'label' => 'Product comparison',
      'targetEntityType' => 'node',
    ));

    // Save the entity view mode
    $entity_view_mode->save();

    // Rebuild routes if needed
    \Drupal::service('router.builder')->rebuildIfNeeded();

    $edit = ['display_modes_custom[' . $display_id . ']' => TRUE];
    $this->drupalPostForm('admin/structure/types/manage/product/display', $edit, t('Save'));
  }

  /**
   * Update view mode
   */
  protected function updateViewMode() {
    $manage_display = '/admin/structure/types/manage/product/display/product_product_comparison';
    $edit = [
      'fields[price][region]' => 'content',
      'fields[sku][region]' => 'content',
      'fields[link_for_entity_comparison_product_comparison][region]' => 'hidden',
    ];
    $this->drupalPostForm($manage_display, $edit, t('Save'));
  }

  /**
   * Create text product contents
   */
  protected function createProducts() {

    $products = array(
      0 => array(
        'type' => 'product',
        'title' => 'Product 1',
        'sku' => 'sku-1',
        'price' => '100$'
      ),
      1 => array(
        'type' => 'product',
        'title' => 'Product 2',
        'sku' => 'sku-2',
        'price' => '120$'
      ),
      2 => array(
        'type' => 'product',
        'title' => 'Product 3',
        'sku' => 'sku-3',
        'price' => '150$'
      ),
    );

    foreach($products as $product) {
      $this->products[] = $this->drupalCreateNode($product);
    }

  }

  /**
   * Add fields to product content type
   */
  protected function addFieldsToEntity($entity_type, $bundle_type, $fields) {

    foreach($fields as $field_name => $field) {

      // Create a field.
      $field_storage = FieldStorageConfig::create([
        'field_name' => $field_name,
        'entity_type' => $entity_type,
        'type' => $field['type']
      ]);

      $field_storage->save();
      FieldConfig::create([
        'field_storage' => $field_storage,
        'bundle' => $bundle_type,
        'label' => $field_name,
      ])->save();

      entity_get_form_display($entity_type, $bundle_type, 'default')
        ->setComponent($field_name, [
          'type' => $field['widget_type'],
          'settings' => [
            'placeholder' => 'A placeholder on ' . $field['type'],
          ],
        ])
        ->save();
      entity_get_display($entity_type, $bundle_type, 'full')
        ->setComponent($field_name)
        ->save();
    }

  }

  /**
   * Check that the admin user can see the entity comparison list
   */
  public function testEntityComparisonListPage() {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('/admin/structure/entity_comparison');
    $this->assertText('Product comparison');
  }

  /**
   * Check that the corresponding view mode is created, and enabled successfully
   */
  public function testViewmodeIsEnabled() {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('/admin/structure/types/manage/product/display/product_product_comparison');
    //$this->drupalGet('/admin/structure/types/manage/product/display');
    //$this->drupalGet('/admin/structure/display-modes/view');
    $this->assertText('product_comparison');
    $this->assertText('Link for entity comparison');
  }

  /**
   * Test the dynamic permission
   */
  public function testPermission() {

    // Allowed user
    $allowed_user = $this->drupalCreateUser(array('use product_comparison entity comparison', 'access content'));
    $this->drupalLogin($allowed_user);
    $this->drupalGet('node/' . $this->products[0]->id());
    $this->assertText('Add product to comparison list');
    $this->drupalLogout();

    // Denied user
    $denied_user = $this->drupalCreateUser(array('access content'));
    $this->drupalLogin($denied_user);
    $this->drupalGet('node/' . $this->products[0]->id());
    $this->assertNoText('Add product to comparison list');
    $this->drupalLogout();
  }

  /**
   * Test the limit function
   */
  public function testLimit() {
    $allowed_user = $this->drupalCreateUser(array('use product_comparison entity comparison', 'access content'));
    $this->drupalLogin($allowed_user);
    // Add first product
    $this->drupalGet('node/' . $this->products[0]->id());
    $this->clickLink('Add product to comparison list');
    $this->assertText('You have successfully added Product 1 to Product comparison list.', 'User can use the add link');

    $this->clickLink('Remove product from the comparison');
    $this->assertText('You have successfully removed Product 1 from Product comparison.', 'User can use the remove link');
    $this->clickLink('Add product to comparison list');

    // Add second product
    $this->drupalGet('node/' . $this->products[1]->id());
    $this->clickLink('Add product to comparison list');

    // Add first product
    $this->drupalGet('node/' . $this->products[2]->id());
    $this->clickLink('Add product to comparison list');
    $this->assertText('You can only add 2 items to the Product comparison list.', 'Limit function works great.');
  }

  public function testComparePage() {
    // Log in
    $allowed_user = $this->drupalCreateUser(array('use product_comparison entity comparison', 'access content'));
    $this->drupalLogin($allowed_user);

    // Add two product to the compare page
    $this->drupalGet('node/' . $this->products[0]->id());
    $this->clickLink('Add product to comparison list');
    $this->drupalGet('node/' . $this->products[1]->id());
    $this->clickLink('Add product to comparison list');

    // Got to the compare page
    $this->drupalGet('compare/product-comparison');
    $this->assertText('Product 1', 'Product 1 is in the list.');
    $this->assertText('Product 2', 'Product 2 is in the list.');

    $this->assertText('Remove product from the comparison', 'Remove links are visible');

    $this->clickLink('Remove product from the comparison', 0);

    $this->assertText('You have successfully removed Product 1 from Product comparison.', 'The product 1 is removed successfully.');
  }


/*
  public function testValami() {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('node/' . $this->products[0]->nid);
    $this->assertRaw('Product', 'New title label was displayed.');
  }
  */
}