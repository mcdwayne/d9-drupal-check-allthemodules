<?php

namespace Drupal\Tests\formfactorykits\Unit\Kits\Field\Date;

use Drupal\Tests\formfactorykits\Unit\KitTestBase;

/**
 * @coversDefaultClass \Drupal\formfactorykits\Kits\Field\Date\DateKit
 * @group kit
 */
class DateKitTest extends KitTestBase {
  public function testDefaults() {
    $date = $this->k->date();
    $this->assertArrayEquals([
      'date' => [
        '#type' => 'date',
      ],
    ], [
      $date->getID() => $date->getArray(),
    ]);
  }

  public function testCustomID() {
    $date = $this->k->date('foo');
    $this->assertEquals('foo', $date->getID());
  }

  public function testTitle() {
    $date = $this->k->date()
      ->setTitle('Foo');
    $this->assertArrayEquals([
      'date' => [
        '#type' => 'date',
        '#title' => 'Foo',
      ],
    ], [
      $date->getID() => $date->getArray(),
    ]);
  }

  public function testDescription() {
    $date = $this->k->date()
      ->setDescription('Foo');
    $this->assertArrayEquals([
      'date' => [
        '#type' => 'date',
        '#description' => 'Foo',
      ],
    ], [
      $date->getID() => $date->getArray(),
    ]);
  }

  public function testValue() {
    $date = $this->k->date()
      ->setValue('foo');
    $this->assertArrayEquals([
      'date' => [
        '#type' => 'date',
        '#value' => 'foo',
      ],
    ], [
      $date->getID() => $date->getArray(),
    ]);
  }
}
