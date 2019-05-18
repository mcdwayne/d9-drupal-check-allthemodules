<?php

namespace Drupal\Tests\formfactorykits\Unit\Kits\Container;

use Drupal\Tests\formfactorykits\Unit\KitTestBase;

/**
 * @coversDefaultClass \Drupal\formfactorykits\Kits\Container\ContainerKit
 * @group kit
 */
class ContainerKitTest extends KitTestBase {
  public function testDefaults() {
    $container = $this->k->container();
    $this->assertArrayEquals([
      'container' => [
        '#type' => 'container',
      ],
    ], [
      $container->getID() => $container->getArray(),
    ]);
  }

  public function testCustomID() {
    $container = $this->k->container('foo');
    $this->assertEquals('foo', $container->getID());
  }
}
