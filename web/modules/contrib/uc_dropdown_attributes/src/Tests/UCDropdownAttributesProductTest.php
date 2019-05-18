<?php

namespace Drupal\uc_dropdown_attributes\Tests;

use Drupal\uc_store\Tests\UbercartTestBase;

/**
 * Test Dropdown Attribute functionality for products.
 *
 * @group DropdownAttributes
 */
class UCDropdownAttributesProductTest extends UbercartTestBase {

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
   * Test for dropdown attributes in products.
   */
  public function testProductAttributeDependency() {
    for ($display = 1; $display <= 3; $display++) {
      // Create two attributes.
      $data = array('display' => $display);
      $parent_attribute = $this->createAttribute($data);
      $child_display = mt_rand(0, 3);
      $data = array('display' => $child_display);
      $child_attribute = $this->createAttribute($data);

      // Add a product.
      $product = $this->createProduct();

      // Attach the attributes to a product.
      uc_attribute_subject_save($parent_attribute, 'product', $product->id());
      uc_attribute_subject_save($child_attribute, 'product', $product->id());

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
        if ($i == 2) {
          $parent_option = $option;
        }
      }
      $child_options = array();
      for ($i = 0; $i < 3; $i++) {
        $option = $this->createAttributeOption(array(
          'aid' => $child_attribute->aid,
        ));
        $child_options[$option->oid] = $option;
      }

      // Add options to product.
      $child_option = current($child_options);
      $edit = array(
        'attributes[' . $parent_attribute->aid . '][default]' => $parent_option->oid,
        'attributes[' . $child_attribute->aid . '][default]' => $child_option->oid,
      );
      foreach ($parent_options as $parent_option) {
        $key = 'attributes[' . $parent_attribute->aid . '][options][' .
          $parent_option->oid . '][select]';
        $edit[$key] = $parent_option->oid;
      }
      foreach ($child_options as $child_option) {
        $key = 'attributes[' . $child_attribute->aid . '][options][' .
          $child_option->oid . '][select]';
        $edit[$key] = $child_option->oid;
      }
      $this->drupalPostForm('node/' . $product->id() . '/edit/options', $edit, t('Save changes'));

      // Check for child attribute.
      $this->drupalGet('node/' . $product->id());
      $this->assertText($child_attribute->label,
        t('No dependency: Child attribute found.'));

      // Create dependent attribute.
      uc_dropdown_attributes_product_create_dependency(
        $product->id(),
        $child_attribute->aid,
        $parent_attribute->aid,
        $options,
        1
      );

      // Confirm that the database is correct.
      $type = uc_dropdown_attributes_dependency_type($product->id());
      $this->assertEqual($type, 'node');
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
      switch ($child_display) {
        case 0:
          $this->assertRaw('style="display:none" type="text" id="edit-attributes',
            t('Dependency: Child attribute not found.'));
          break;

        default:
          $this->assertRaw('style="display:none" id="edit-attributes',
            t('Dependency: Child attribute not found.'));
          break;
      }
    }
  }

}
