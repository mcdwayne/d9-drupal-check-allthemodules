<?php

namespace Drupal\Tests\formfactorykits\Unit\Kits\Field\Text\Number;

use Drupal\Tests\formfactorykits\Unit\Kits\Traits\StringTranslationTrait;
use Drupal\Tests\formfactorykits\Unit\KitTestBase;

/**
 * @coversDefaultClass \Drupal\formfactorykits\Kits\Field\Text\Number\RangeKit
 * @group kit
 */
class RangeKitTest extends KitTestBase {
  use StringTranslationTrait;

  /**
   * @inheritdoc
   */
  public function getServices() {
    return [
      'string_translation' => $this->getTranslationManager(),
    ];
  }

  public function testDefaults() {
    $range = $this->k->range();
    $this->assertArrayEquals([
      'range' => [
        '#type' => 'range',
        '#title' => 'Range',
      ],
    ], [
      $range->getID() => $range->getArray(),
    ]);
  }

  public function testCustomID() {
    $range = $this->k->range('foo');
    $this->assertEquals('foo', $range->getID());
  }

  public function testTitle() {
    $range = $this->k->range()
      ->setTitle('Foo');
    $this->assertArrayEquals([
      'range' => [
        '#type' => 'range',
        '#title' => 'Foo',
      ],
    ], [
      $range->getID() => $range->getArray(),
    ]);
  }

  public function testDescription() {
    $range = $this->k->range()
      ->setDescription('Foo');
    $this->assertArrayEquals([
      'range' => [
        '#type' => 'range',
        '#title' => 'Range',
        '#description' => 'Foo',
      ],
    ], [
      $range->getID() => $range->getArray(),
    ]);
  }

  public function testDefaultValue() {
    $range = $this->k->range()
      ->setDefaultValue(50);
    $this->assertArrayEquals([
      'range' => [
        '#type' => 'range',
        '#title' => 'Range',
        '#default_value' => 50,
      ],
    ], [
      $range->getID() => $range->getArray(),
    ]);
  }

  public function testMinimum() {
    $range = $this->k->range()
      ->setMinimum(5);
    $this->assertArrayEquals([
      'range' => [
        '#type' => 'range',
        '#title' => 'Range',
        '#min' => 5,
      ],
    ], [
      $range->getID() => $range->getArray(),
    ]);
  }

  public function testMaximum() {
    $range = $this->k->range()
      ->setMaximum(10);
    $this->assertArrayEquals([
      'range' => [
        '#type' => 'range',
        '#title' => 'Range',
        '#max' => 10,
      ],
    ], [
      $range->getID() => $range->getArray(),
    ]);
  }

  public function testStep() {
    $range = $this->k->range()
      ->setStep(2);
    $this->assertArrayEquals([
      'range' => [
        '#type' => 'range',
        '#title' => 'Range',
        '#step' => 2,
      ],
    ], [
      $range->getID() => $range->getArray(),
    ]);
  }
}
