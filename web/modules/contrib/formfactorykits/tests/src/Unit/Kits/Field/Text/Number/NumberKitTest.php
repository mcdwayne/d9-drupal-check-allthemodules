<?php

namespace Drupal\Tests\formfactorykits\Unit\Kits\Field\Text\Number;

use Drupal\Tests\formfactorykits\Unit\KitTestBase;

/**
 * @coversDefaultClass \Drupal\formfactorykits\Kits\Field\Text\Number\NumberKit
 * @group kit
 */
class NumberKitTest extends KitTestBase {
  public function testDefaults() {
    $number = $this->k->number();
    $this->assertArrayEquals([
      'number' => [
        '#type' => 'number',
      ],
    ], [
      $number->getID() => $number->getArray(),
    ]);
  }

  public function testCustomID() {
    $number = $this->k->number('foo');
    $this->assertEquals('foo', $number->getID());
  }

  public function testTitle() {
    $number = $this->k->number()
      ->setTitle('Foo');
    $this->assertArrayEquals([
      'number' => [
        '#type' => 'number',
        '#title' => 'Foo',
      ],
    ], [
      $number->getID() => $number->getArray(),
    ]);
  }

  public function testDefaultValue() {
    $number = $this->k->number()
      ->setDefaultValue(50);
    $this->assertArrayEquals([
      'number' => [
        '#type' => 'number',
        '#default_value' => 50,
      ],
    ], [
      $number->getID() => $number->getArray(),
    ]);
  }

  public function testMinimum() {
    $number = $this->k->number()
      ->setMinimum(5);
    $this->assertArrayEquals([
      'number' => [
        '#type' => 'number',
        '#min' => 5,
      ],
    ], [
      $number->getID() => $number->getArray(),
    ]);
  }

  public function testMaximum() {
    $number = $this->k->number()
      ->setMaximum(10);
    $this->assertArrayEquals([
      'number' => [
        '#type' => 'number',
        '#max' => 10,
      ],
    ], [
      $number->getID() => $number->getArray(),
    ]);
  }

  public function testStep() {
    $number = $this->k->number()
      ->setStep(2);
    $this->assertArrayEquals([
      'number' => [
        '#type' => 'number',
        '#step' => 2,
      ],
    ], [
      $number->getID() => $number->getArray(),
    ]);
  }

  public function testSize() {
    $number = $this->k->number()
      ->setSize(20);
    $this->assertArrayEquals([
      'number' => [
        '#type' => 'number',
        '#size' => 20,
      ],
    ], [
      $number->getID() => $number->getArray(),
    ]);
  }
}
