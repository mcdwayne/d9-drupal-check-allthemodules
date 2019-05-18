<?php

namespace Drupal\Tests\commerce_addon\Kernel;

use Drupal\commerce_addon\Entity\Addon;
use Drupal\commerce_price\Price;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * Tests the `commerce_addon` entity.
 *
 * @group commerce_addon
 */
class AddonTest extends CommerceKernelTestBase {

  public static $modules = [
    'commerce_addon',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('commerce_addon');
  }

  /**
   * Tests the addon entity.
   */
  public function testAddon() {
    $addon = Addon::create([
      'title' => 'Enable super powers',
      'description' => 'Adding this will give your product super powers',
      'price' => new Price('25.00', 'USD'),
      'type' => 'default',
    ]);
    $addon->save();

    $this->assertEquals(new Price('25.00', 'USD'), $addon->getPrice());
    $this->assertEquals('Enable super powers', $addon->label());
    $this->assertEquals('Adding this will give your product super powers', $addon->getDescription());
  }

}
