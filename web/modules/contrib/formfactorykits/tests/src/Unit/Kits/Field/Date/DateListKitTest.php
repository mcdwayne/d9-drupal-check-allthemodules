<?php

namespace Drupal\Tests\formfactorykits\Unit\Kits\Field\Date;

use Drupal\Tests\formfactorykits\Unit\KitTestBase;

/**
 * @coversDefaultClass \Drupal\formfactorykits\Kits\Field\Date\DateListKit
 * @group kit
 */
class DateListKitTest extends KitTestBase {
  public function testDefaults() {
    $dateList = $this->k->dateList();
    $this->assertArrayEquals([
      'datelist' => [
        '#type' => 'datelist',
      ],
    ], [
      $dateList->getID() => $dateList->getArray(),
    ]);
  }

  public function testCustomID() {
    $dateList = $this->k->dateList('foo');
    $this->assertEquals('foo', $dateList->getID());
  }

  public function testTitle() {
    $dateList = $this->k->dateList()
      ->setTitle('Foo');
    $this->assertArrayEquals([
      'datelist' => [
        '#type' => 'datelist',
        '#title' => 'Foo',
      ],
    ], [
      $dateList->getID() => $dateList->getArray(),
    ]);
  }

  public function testDescription() {
    $dateList = $this->k->dateList()
      ->setDescription('Foo');
    $this->assertArrayEquals([
      'datelist' => [
        '#type' => 'datelist',
        '#description' => 'Foo',
      ],
    ], [
      $dateList->getID() => $dateList->getArray(),
    ]);
  }

  public function testValue() {
    $dateList = $this->k->dateList()
      ->setValue('foo');
    $this->assertArrayEquals([
      'datelist' => [
        '#type' => 'datelist',
        '#value' => 'foo',
      ],
    ], [
      $dateList->getID() => $dateList->getArray(),
    ]);
  }

  public function testIncrement() {
    $dateTime = $this->k->dateList()
      ->setIncrement(1);
    $this->assertArrayEquals([
      'datelist' => [
        '#type' => 'datelist',
        '#date_increment' => 1,
      ],
    ], [
      $dateTime->getID() => $dateTime->getArray(),
    ]);
  }

  public function testTimeZone() {
    $dateTime = $this->k->dateList()
      ->setDatePartOrder('key');
    $this->assertArrayEquals([
      'datelist' => [
        '#type' => 'datelist',
        '#date_part_order' => 'key',
      ],
    ], [
      $dateTime->getID() => $dateTime->getArray(),
    ]);
  }
}
