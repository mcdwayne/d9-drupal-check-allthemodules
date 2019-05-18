<?php

namespace Drupal\Tests\formfactorykits\Unit\Kits\Container;

use Drupal\Tests\formfactorykits\Unit\KitTestBase;

/**
 * @coversDefaultClass \Drupal\formfactorykits\Kits\Container\DetailsKit
 * @group kit
 */
class DetailsKitTest extends KitTestBase {
  public function testDefaults() {
    $details = $this->k->details();
    $this->assertArrayEquals([
      'details' => [
        '#type' => 'details',
      ],
    ], [
      $details->getID() => $details->getArray(),
    ]);
  }

  public function testCustomID() {
    $details = $this->k->details('foo');
    $this->assertEquals('foo', $details->getID());
  }

  public function testOpen() {
    $details = $this->k->details()
      ->setOpen();
    $this->assertArrayEquals([
      'details' => [
        '#type' => 'details',
        '#open' => TRUE,
      ],
    ], [
      $details->getID() => $details->getArray(),
    ]);
  }
}
