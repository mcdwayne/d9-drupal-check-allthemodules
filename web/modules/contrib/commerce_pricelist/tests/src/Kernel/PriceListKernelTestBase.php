<?php

namespace Drupal\Tests\commerce_pricelist\Kernel;

use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * Provides a base class for price list kernel tests.
 */
abstract class PriceListKernelTestBase extends CommerceKernelTestBase {

  /**
   * Modules to enable.
   *
   * Note that when a child class declares its own $modules list, that list
   * doesn't override this one, it just extends it.
   *
   * @var array
   */
  public static $modules = [
    'path',
    'commerce_product',
    'commerce_pricelist',
  ];

  /**
   * A sample user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('commerce_product_variation');
    $this->installEntitySchema('commerce_product');
    $this->installEntitySchema('commerce_pricelist_item');
    $this->installEntitySchema('commerce_pricelist');
    $this->installConfig(['commerce_product']);

    $user = $this->createUser();
    $this->user = $this->reloadEntity($user);
  }

}
