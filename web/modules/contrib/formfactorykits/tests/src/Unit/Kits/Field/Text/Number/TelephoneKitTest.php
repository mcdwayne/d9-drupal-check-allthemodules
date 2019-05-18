<?php

namespace Drupal\Tests\formfactorykits\Unit\Kits\Field\Text\Number;

use Drupal\Tests\formfactorykits\Unit\Kits\Traits\StringTranslationTrait;
use Drupal\Tests\formfactorykits\Unit\KitTestBase;

/**
 * @coversDefaultClass \Drupal\formfactorykits\Kits\Field\Text\Number\TelephoneKit
 * @group kit
 */
class TelephoneKitTest extends KitTestBase {
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
    $telephone = $this->k->telephone();
    $this->assertArrayEquals([
      'telephone' => [
        '#type' => 'tel',
        '#title' => 'Phone',
      ],
    ], [
      $telephone->getID() => $telephone->getArray(),
    ]);
  }

  public function testCustomID() {
    $telephone = $this->k->telephone('foo');
    $this->assertEquals('foo', $telephone->getID());
  }

  public function testTitle() {
    $telephone = $this->k->telephone()
      ->setTitle('Foo');
    $this->assertArrayEquals([
      'telephone' => [
        '#type' => 'tel',
        '#title' => 'Foo',
      ],
    ], [
      $telephone->getID() => $telephone->getArray(),
    ]);
  }

  public function testDescription() {
    $telephone = $this->k->telephone()
      ->setDescription('Foo');
    $this->assertArrayEquals([
      'telephone' => [
        '#type' => 'tel',
        '#title' => 'Phone',
        '#description' => 'Foo',
      ],
    ], [
      $telephone->getID() => $telephone->getArray(),
    ]);
  }

  public function testDefaultValue() {
    $telephone = $this->k->telephone()
      ->setDefaultValue(50);
    $this->assertArrayEquals([
      'telephone' => [
        '#type' => 'tel',
        '#title' => 'Phone',
        '#default_value' => 50,
      ],
    ], [
      $telephone->getID() => $telephone->getArray(),
    ]);
  }

  public function testSize() {
    $telephone = $this->k->telephone()
      ->setSize(10);
    $this->assertArrayEquals([
      'telephone' => [
        '#type' => 'tel',
        '#title' => 'Phone',
        '#size' => 10,
      ],
    ], [
      $telephone->getID() => $telephone->getArray(),
    ]);
  }

  public function testPattern() {
    $telephone = $this->k->telephone()
      ->setPattern('pattern');
    $this->assertArrayEquals([
      'telephone' => [
        '#type' => 'tel',
        '#title' => 'Phone',
        '#pattern' => 'pattern',
      ],
    ], [
      $telephone->getID() => $telephone->getArray(),
    ]);
  }
}
