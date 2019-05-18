<?php

namespace Drupal\Tests\formfactorykits\Unit\Kits\Field\Checkboxes;

use Drupal\Tests\formfactorykits\Unit\KitTestBase;

/**
 * @coversDefaultClass \Drupal\formfactorykits\Kits\Field\Checkboxes\CheckboxKit
 * @group kit
 */
class CheckboxKitTest extends KitTestBase {
  public function testDefaults() {
    $checkbox = $this->k->checkbox();
    $this->assertArrayEquals([
      'checkbox' => [
        '#type' => 'checkbox',
      ],
    ], [
      $checkbox->getID() => $checkbox->getArray(),
    ]);
  }

  public function testCustomID() {
    $checkbox = $this->k->checkbox('foo');
    $this->assertEquals('foo', $checkbox->getID());
  }

  public function testTitle() {
    $checkbox = $this->k->checkbox()
      ->setTitle('Foo');
    $this->assertArrayEquals([
      'checkbox' => [
        '#type' => 'checkbox',
        '#title' => 'Foo',
      ],
    ], [
      $checkbox->getID() => $checkbox->getArray(),
    ]);
  }

  public function testDescription() {
    $checkbox = $this->k->checkbox()
      ->setDescription('Foo');
    $this->assertArrayEquals([
      'checkbox' => [
        '#type' => 'checkbox',
        '#description' => 'Foo',
      ],
    ], [
      $checkbox->getID() => $checkbox->getArray(),
    ]);
  }

  public function testValue() {
    $checkbox = $this->k->checkbox()
      ->setValue('foo');
    $this->assertArrayEquals([
      'checkbox' => [
        '#type' => 'checkbox',
        '#value' => 'foo',
      ],
    ], [
      $checkbox->getID() => $checkbox->getArray(),
    ]);
  }
}
