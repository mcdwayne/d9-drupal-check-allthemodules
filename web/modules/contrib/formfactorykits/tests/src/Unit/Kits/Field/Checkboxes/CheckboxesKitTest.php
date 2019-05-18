<?php

namespace Drupal\Tests\formfactorykits\Unit\Kits\Field\Checkboxes;

use Drupal\Tests\formfactorykits\Unit\KitTestBase;

/**
 * @coversDefaultClass \Drupal\formfactorykits\Kits\Field\Checkboxes\CheckboxesKit
 * @group kit
 */
class CheckboxesKitTest extends KitTestBase {
  public function testDefaults() {
    $checkboxes = $this->k->checkboxes();
    $this->assertArrayEquals([
      'checkboxes' => [
        '#type' => 'checkboxes',
      ],
    ], [
      $checkboxes->getID() => $checkboxes->getArray(),
    ]);
  }

  public function testCustomID() {
    $checkboxes = $this->k->checkboxes('foo');
    $this->assertEquals('foo', $checkboxes->getID());
  }

  public function testTitle() {
    $checkboxes = $this->k->checkboxes()
      ->setTitle('Foo');
    $this->assertArrayEquals([
      'checkboxes' => [
        '#type' => 'checkboxes',
        '#title' => 'Foo',
      ],
    ], [
      $checkboxes->getID() => $checkboxes->getArray(),
    ]);
  }

  public function testDescription() {
    $checkboxes = $this->k->checkboxes()
      ->setDescription('Foo');
    $this->assertArrayEquals([
      'checkboxes' => [
        '#type' => 'checkboxes',
        '#description' => 'Foo',
      ],
    ], [
      $checkboxes->getID() => $checkboxes->getArray(),
    ]);
  }

  public function testOptions() {
    $checkboxes = $this->k->checkboxes()
      ->setOptions([
        'foo' => 'Foo',
        'bar' => 'Bar',
      ])
      ->appendOption(['baz' => 'Baz'])
      ->appendOption(['qux' => 'Qux']);
    $this->assertArrayEquals([
      'checkboxes' => [
        '#type' => 'checkboxes',
        '#options' => [
          'foo' => 'Foo',
          'bar' => 'Bar',
          'baz' => 'Baz',
          'qux' => 'Qux',
        ],
      ],
    ], [
      $checkboxes->getID() => $checkboxes->getArray(),
    ]);
  }

  public function testValue() {
    $checkboxes = $this->k->checkboxes()
      ->setValue(['foo', 'bar', 'baz']);
    $this->assertArrayEquals([
      'checkboxes' => [
        '#type' => 'checkboxes',
        '#value' => ['foo', 'bar', 'baz'],
      ],
    ], [
      $checkboxes->getID() => $checkboxes->getArray(),
    ]);
  }
}
