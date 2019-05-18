<?php

namespace Drupal\Tests\formfactorykits\Unit\Kits\Field;

use Drupal\Tests\formfactorykits\Unit\KitTestBase;

/**
 * @coversDefaultClass \Drupal\formfactorykits\Kits\Field\HiddenKit
 * @group kit
 */
class HiddenKitTest extends KitTestBase {
  public function testDefaults() {
    $hidden = $this->k->hidden();
    $this->assertArrayEquals([
      'hidden' => [
        '#type' => 'hidden',
      ],
    ], [
      $hidden->getID() => $hidden->getArray(),
    ]);
  }

  public function testCustomID() {
    $hidden = $this->k->hidden('foo');
    $this->assertEquals('foo', $hidden->getID());
  }

  public function testValue() {
    $hidden = $this->k->hidden()
      ->setValue('Foo');
    $this->assertArrayEquals([
      'hidden' => [
        '#type' => 'hidden',
        '#value' => 'Foo',
      ],
    ], [
      $hidden->getID() => $hidden->getArray(),
    ]);
  }
}
