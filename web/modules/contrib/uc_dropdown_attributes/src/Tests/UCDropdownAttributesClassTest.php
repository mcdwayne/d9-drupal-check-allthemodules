<?php

namespace Drupal\uc_dropdown_attributes\Tests;

use Drupal\uc_store\Tests\UbercartTestBase;

/**
 * Test Dropdown Attribute functionality for classes.
 *
 * @group DropdownAttributes
 */
class UCDropdownAttributesClassTest extends UbercartTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('uc_dropdown_attributes');
  public static $adminPermissions = array(
    'administer attributes',
    'administer product attributes',
    'administer product options',
  );

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Test for dropdown attributes in classes.
   */
  public function testClassAttributeDependency() {
    // Create two attributes.
    $data = array('display' => mt_rand(1, 3));
    $parent_attribute = $this->createAttribute($data);
    $child_attribute = $this->createAttribute($data);

    // Add a product class.
    $product_class = $this->createProductClass();

    // Add a product.
    $product = $this->createProduct(array('type' => $product_class->id()));

    // Check product class name.
    $pcid = uc_dropdown_attributes_get_type($product->id());
    $this->assertEqual($pcid, $product_class->id());

    // Attach the attributes to a product.
    uc_attribute_subject_save($parent_attribute, 'class', $product_class->id());
    uc_attribute_subject_save($child_attribute, 'class', $product_class->id());

    $this->drupalGet('admin/structure/types/manage/' . $product_class->id() . '/attributes');

    // Add some options.
    $parent_options = array();
    $options = array();
    for ($i = 0; $i < 3; $i++) {
      $option = $this->createAttributeOption(array(
        'aid' => $parent_attribute->aid,
      ));
      $parent_options[$option->oid] = $option;
      if ($i < 2) {
        $options[$option->oid] = $option->oid;
      }
      if ($i == 0) {
        $oid = $option->oid;
      }
    }
    $child_options = array();
    for ($i = 0; $i < 3; $i++) {
      $option = $this->createAttributeOption(array(
        'aid' => $child_attribute->aid,
      ));
      $child_options[$option->oid] = $option;
    }

    // Check for child attribute.
    $this->drupalGet('node/' . $product->id());
    $this->assertText($child_attribute->label,
      t('No dependency: Child attribute found.'));

    // Create dependent attribute.
    $this->drupalGet('admin/structure/types/manage/' . $product_class->id() . '/dependencies');

    uc_dropdown_attributes_class_create_dependency(
      $product_class->id(),
      $child_attribute->aid,
      $parent_attribute->aid,
      $options,
      1
    );

    // Check type of dependency.
    $type = uc_dropdown_attributes_dependency_type($product->id());
    $this->assertEqual($type, 'class');

    // Confirm that the database is correct.
    $query = \Drupal::database()->select('uc_dropdown_products', 'products')
      ->fields('products')
      ->condition('nid', $product->id())
      ->condition('aid', $child_attribute->aid)
      ->execute();
    foreach ($query as $item) {
      $this->assertEqual($item->parent_aid, $parent_attribute->aid);
      $this->assertEqual(unserialize($item->parent_values), $options);
      $this->assertEqual($item->required, 1);
    }

    // Check for child attribute.
    $this->drupalGet('node/' . $product->id());
    $this->assertNoText($child_attribute->label,
      t('Dependency: Child label not found'));
    $this->assertNoText($child_attribute->description,
      t('Dependency: Child description not found'));
    $this->assertRaw('style="display:none" type="text" id="edit-attributes',
      t('Dependency: Child attribute not found.'));

  }

}
