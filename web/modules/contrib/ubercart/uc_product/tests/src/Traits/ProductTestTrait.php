<?php

namespace Drupal\Tests\uc_product\Traits;

use Drupal\node\Entity\NodeType;

/**
 * Utility functions to provide products for test purposes.
 *
 * This trait can only be used in classes which already use
 * RandomGeneratorTrait. RandomGeneratorTrait is used in all
 * the PHPUnit and Simpletest base classes.
 */
trait ProductTestTrait {

  /**
   * Creates a new product.
   *
   * @param array $product
   *   (optional) An associative array of product fields to change from the
   *   defaults, keys are product field names. For example, 'price' => '12.34'.
   *
   * @return \Drupal\node\NodeInterface
   *   Product node object.
   */
  protected function createProduct(array $product = []) {
    // Set the default required fields.
    $weight_units = ['lb', 'kg', 'oz', 'g'];
    $length_units = ['in', 'ft', 'cm', 'mm'];
    $product += [
      'type' => 'product',
      'model' => $this->randomMachineName(8),
      'cost' => mt_rand(1, 9999),
      'price' => mt_rand(1, 9999),
      'weight' => [
        0 => [
          'value' => mt_rand(1, 9999),
          'units' => array_rand(array_flip($weight_units)),
        ],
      ],
      'dimensions' => [
        0 => [
          'length' => mt_rand(1, 9999),
          'width' => mt_rand(1, 9999),
          'height' => mt_rand(1, 9999),
          'units' => array_rand(array_flip($length_units)),
        ],
      ],
      'pkg_qty' => mt_rand(1, 99),
      'default_qty' => 1,
      'shippable' => 1,
    ];

    $product['model'] = [['value' => $product['model']]];
    $product['price'] = [['value' => $product['price']]];

    return $this->drupalCreateNode($product);
  }

  /**
   * Creates a new product node type, AKA 'product class'.
   *
   * @todo Fix this after adding a proper API call for saving a product class,
   * so that we don't have to do this through the UI.
   *
   * @param array $data
   *   (optional) An associative array with possible keys of 'type', 'name',
   *   and 'description' to initialize the product class.
   *
   * @return \Drupal\node\Entity\NodeType
   *   Product class NodeType object.
   */
  protected function createProductClass(array $data = []) {
    $class = strtolower($this->randomMachineName(12));
    $edit = $data + [
      'type' => $class,
      'name' => $class,
      'description' => $this->randomMachineName(32),
      'uc_product[product]' => TRUE,
    ];
    $this->drupalPostForm('admin/structure/types/add', $edit, 'Save content type');

    return NodeType::load($edit['type']);
  }

}
