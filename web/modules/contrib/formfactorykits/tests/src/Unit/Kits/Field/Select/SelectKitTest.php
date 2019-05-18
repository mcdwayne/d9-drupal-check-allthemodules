<?php

namespace Drupal\Tests\formfactorykits\Unit\Kits\Field\Select;

use Drupal\Tests\formfactorykits\Unit\KitTestBase;

/**
 * @coversDefaultClass \Drupal\formfactorykits\Kits\Field\Select\SelectKit
 * @group kit
 */
class SelectKitTest extends KitTestBase {
  public function testDefaults() {
    $select = $this->k->select();
    $this->assertArrayEquals([
      'select' => [
        '#type' => 'select',
        '#empty_option' => '',
      ],
    ], [
      $select->getID() => $select->getArray(),
    ]);
  }

  public function testCustomID() {
    $select = $this->k->select('foo');
    $this->assertEquals('foo', $select->getID());
  }

  public function testTitle() {
    $select = $this->k->select()
      ->setTitle('Foo');
    $this->assertArrayEquals([
      'select' => [
        '#type' => 'select',
        '#empty_option' => '',
        '#title' => 'Foo',
      ],
    ], [
      $select->getID() => $select->getArray(),
    ]);
  }

  public function testDescription() {
    $select = $this->k->select()
      ->setDescription('Foo');
    $this->assertArrayEquals([
      'select' => [
        '#type' => 'select',
        '#empty_option' => '',
        '#description' => 'Foo',
      ],
    ], [
      $select->getID() => $select->getArray(),
    ]);
  }

  public function testOptions() {
    $select = $this->k->select()
      ->setOptions([
        'foo' => 'Foo',
        'bar' => 'Bar',
      ])
      ->appendOption(['baz' => 'Baz'])
      ->appendOption(['qux' => 'Qux']);
    $this->assertArrayEquals([
      'select' => [
        '#type' => 'select',
        '#empty_option' => '',
        '#options' => [
          'foo' => 'Foo',
          'bar' => 'Bar',
          'baz' => 'Baz',
          'qux' => 'Qux',
        ],
      ],
    ], [
      $select->getID() => $select->getArray(),
    ]);
  }

  public function testValue() {
    $select = $this->k->select()
      ->setValue('foo');
    $this->assertArrayEquals([
      'select' => [
        '#type' => 'select',
        '#empty_option' => '',
        '#value' => 'foo',
      ],
    ], [
      $select->getID() => $select->getArray(),
    ]);
  }
}
