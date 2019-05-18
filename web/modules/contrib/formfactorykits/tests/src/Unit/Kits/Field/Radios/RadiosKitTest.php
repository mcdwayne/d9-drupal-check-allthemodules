<?php

namespace Drupal\Tests\formfactorykits\Unit\Kits\Field\Radios;

use Drupal\Tests\formfactorykits\Unit\KitTestBase;

/**
 * @coversDefaultClass \Drupal\formfactorykits\Kits\Field\Radios\RadiosKit
 * @group kit
 */
class RadiosKitTest extends KitTestBase {
  public function testDefaults() {
    $radios = $this->k->radios();
    $this->assertArrayEquals([
      'radios' => [
        '#type' => 'radios',
      ],
    ], [
      $radios->getID() => $radios->getArray(),
    ]);
  }

  public function testCustomID() {
    $radios = $this->k->radios('foo');
    $this->assertEquals('foo', $radios->getID());
  }

  public function testTitle() {
    $radios = $this->k->radios()
      ->setTitle('Foo');
    $this->assertArrayEquals([
      'radios' => [
        '#type' => 'radios',
        '#title' => 'Foo',
      ],
    ], [
      $radios->getID() => $radios->getArray(),
    ]);
  }

  public function testDescription() {
    $radios = $this->k->radios()
      ->setDescription('Foo');
    $this->assertArrayEquals([
      'radios' => [
        '#type' => 'radios',
        '#description' => 'Foo',
      ],
    ], [
      $radios->getID() => $radios->getArray(),
    ]);
  }

  public function testOptions() {
    $radios = $this->k->radios()
      ->setOptions([
        'foo' => 'Foo',
        'bar' => 'Bar',
      ])
      ->appendOption(['baz' => 'Baz'])
      ->appendOption(['qux' => 'Qux']);
    $this->assertArrayEquals([
      'radios' => [
        '#type' => 'radios',
        '#options' => [
          'foo' => 'Foo',
          'bar' => 'Bar',
          'baz' => 'Baz',
          'qux' => 'Qux',
        ],
      ],
    ], [
      $radios->getID() => $radios->getArray(),
    ]);
  }

  public function testValue() {
    $radios = $this->k->radios()
      ->setValue('foo');
    $this->assertArrayEquals([
      'radios' => [
        '#type' => 'radios',
        '#value' => 'foo',
      ],
    ], [
      $radios->getID() => $radios->getArray(),
    ]);
  }
}
