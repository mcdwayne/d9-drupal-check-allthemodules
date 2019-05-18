<?php

namespace Drupal\uc_dropdown_attributes\Tests;

use Drupal\uc_store\Tests\UbercartTestBase;

/**
 * Test Dropdown Attribute functionality for kits.
 *
 * @group DropdownAttributes
 */
class UCDropdownAttributesKitTest extends UbercartTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('uc_dropdown_attributes', 'uc_product_kit');
  public static $adminPermissions = array(
    'administer nodes',
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
   * Test for dropdown attributes in product kits.
   */
  public function testKitAttributeDependency() {
    // Create two attributes.
    $data = array('display' => mt_rand(1, 3));
    $parent_attribute = $this->createAttribute($data);
    $child_attribute = $this->createAttribute($data);

    // Add a product.
    $product1 = $this->createProduct();
    $product2 = $this->createProduct();

    $this->drupalGet('node/add/product_kit');
    $title_key = 'title[0][value]';
    $body_key = 'body[0][value]';
    $edit = array(
      $title_key => $this->randomMachineName(32),
      $body_key => $this->randomMachineName(64),
      'products[]' => array(
        $product1->id(),
        $product2->id(),
      ),
    );
    $this->drupalPostForm('node/add/product_kit', $edit, 'Save and publish');

    // Attach the attributes to products.
    uc_attribute_subject_save($parent_attribute, 'product', $product1->id());
    uc_attribute_subject_save($child_attribute, 'product', $product1->id());
    uc_attribute_subject_save($parent_attribute, 'product', $product2->id());
    uc_attribute_subject_save($child_attribute, 'product', $product2->id());

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

    $nid = \Drupal::database()->select('node', 'n')
      ->condition('n.type', 'product_kit')
      ->fields('n', array('nid'))
      ->execute()
      ->fetchField();
    $this->drupalGet('node/' . $nid);
    $this->assertText($child_attribute->label,
      t('No dependency: Child attribute found.'));

    // Create dependent attribute.
    uc_dropdown_attributes_product_create_dependency(
      $product1->id(),
      $child_attribute->aid,
      $parent_attribute->aid,
      $options,
      1
    );
    uc_dropdown_attributes_product_create_dependency(
      $product2->id(),
      $child_attribute->aid,
      $parent_attribute->aid,
      $options,
      1
    );

    // Check for child attribute.
    $this->drupalGet('node/' . $nid);
    $this->assertNoText($child_attribute->label,
      t('Dependency: Child label not found'));
    $this->assertNoText($child_attribute->description,
      t('Dependency: Child description not found'));
    $this->assertRaw('style="display:none" type="text" id="edit-attributes',
      t('Dependency: Child attribute not found.'));
  }

}
