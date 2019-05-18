<?php

namespace Drupal\Tests\commerce_product_variation_csv\Kernel;

use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;
use Drupal\Tests\commerce_product_variation_csv\Traits\ProductAttributeSetsTrait;

abstract class CommerceProductBulkTestBase extends CommerceKernelTestBase {
  use ProductAttributeSetsTrait;

  public static $modules = [
    'path',
    'file',
    'commerce_product',
    'commerce_product_variation_csv',
  ];

  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('commerce_product_attribute_value');
    $this->installEntitySchema('commerce_product_variation');
    $this->installEntitySchema('commerce_product');
    $this->installConfig(['commerce_product']);

    $this->attributeFieldManager = $this->container->get('commerce_product.attribute_field_manager');
  }
}
