<?php

namespace Drupal\Tests\formfactorykits\Unit\Kits\Field\Color;

use Drupal\Tests\formfactorykits\Unit\KitTestBase;

/**
 * @coversDefaultClass \Drupal\formfactorykits\Kits\Field\Color\ColorKit
 * @group kit
 */
class ColorKitTest extends KitTestBase {
  public function testDefaults() {
    $color = $this->k->color();
    $this->assertArrayEquals([
      'color' => [
        '#type' => 'color',
      ],
    ], [
      $color->getID() => $color->getArray(),
    ]);
  }

  public function testCustomID() {
    $color = $this->k->color('foo');
    $this->assertEquals('foo', $color->getID());
  }

  public function testTitle() {
    $color = $this->k->color()
      ->setTitle('Foo');
    $this->assertArrayEquals([
      'color' => [
        '#type' => 'color',
        '#title' => 'Foo',
      ],
    ], [
      $color->getID() => $color->getArray(),
    ]);
  }

  public function testDescription() {
    $color = $this->k->color()
      ->setDescription('Foo');
    $this->assertArrayEquals([
      'color' => [
        '#type' => 'color',
        '#description' => 'Foo',
      ],
    ], [
      $color->getID() => $color->getArray(),
    ]);
  }

  public function testValue() {
    $color = $this->k->color()
      ->setValue('foo');
    $this->assertArrayEquals([
      'color' => [
        '#type' => 'color',
        '#value' => 'foo',
      ],
    ], [
      $color->getID() => $color->getArray(),
    ]);
  }
}
