<?php

namespace Drupal\Tests\formfactorykits\Unit\Kits\Field;

use Drupal\Tests\formfactorykits\Unit\KitTestBase;

/**
 * @coversDefaultClass \Drupal\formfactorykits\Kits\Field\ValueKit
 * @group kit
 */
class ValueKitTest extends KitTestBase {
  public function testDefaults() {
    $value = $this->k->value();
    $this->assertArrayEquals([
      'value' => [
        '#type' => 'value',
      ],
    ], [
      $value->getID() => $value->getArray(),
    ]);
  }

  public function testCustomID() {
    $value = $this->k->value('foo');
    $this->assertEquals('foo', $value->getID());
  }

  public function testValue() {
    $value = $this->k->value()
      ->setValue('Foo');
    $this->assertArrayEquals([
      'value' => [
        '#type' => 'value',
        '#value' => 'Foo',
      ],
    ], [
      $value->getID() => $value->getArray(),
    ]);
  }
}
