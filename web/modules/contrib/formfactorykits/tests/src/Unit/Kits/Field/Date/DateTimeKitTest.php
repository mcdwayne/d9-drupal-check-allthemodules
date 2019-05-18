<?php

namespace Drupal\Tests\formfactorykits\Unit\Kits\Field\Date;

use Drupal\Tests\formfactorykits\Unit\KitTestBase;

/**
 * @coversDefaultClass \Drupal\formfactorykits\Kits\Field\Date\DateTimeKit
 * @group kit
 */
class DateTimeKitTest extends KitTestBase {
  public function testDefaults() {
    $dateTime = $this->k->dateTime();
    $this->assertArrayEquals([
      'datetime' => [
        '#type' => 'datetime',
      ],
    ], [
      $dateTime->getID() => $dateTime->getArray(),
    ]);
  }

  public function testCustomID() {
    $dateTime = $this->k->dateTime('foo');
    $this->assertEquals('foo', $dateTime->getID());
  }

  public function testTitle() {
    $dateTime = $this->k->dateTime()
      ->setTitle('Foo');
    $this->assertArrayEquals([
      'datetime' => [
        '#type' => 'datetime',
        '#title' => 'Foo',
      ],
    ], [
      $dateTime->getID() => $dateTime->getArray(),
    ]);
  }

  public function testDescription() {
    $dateTime = $this->k->dateTime()
      ->setDescription('Foo');
    $this->assertArrayEquals([
      'datetime' => [
        '#type' => 'datetime',
        '#description' => 'Foo',
      ],
    ], [
      $dateTime->getID() => $dateTime->getArray(),
    ]);
  }

  public function testValue() {
    $dateTime = $this->k->dateTime()
      ->setValue('foo');
    $this->assertArrayEquals([
      'datetime' => [
        '#type' => 'datetime',
        '#value' => 'foo',
      ],
    ], [
      $dateTime->getID() => $dateTime->getArray(),
    ]);
  }

  public function testIncrement() {
    $dateTime = $this->k->dateTime()
      ->setIncrement(1);
    $this->assertArrayEquals([
      'datetime' => [
        '#type' => 'datetime',
        '#date_increment' => 1,
      ],
    ], [
      $dateTime->getID() => $dateTime->getArray(),
    ]);
  }

  public function testTimeZone() {
    $dateTime = $this->k->dateTime()
      ->setTimeZone(new \DateTimeZone('UTC'));
    $this->assertArrayEquals([
      'datetime' => [
        '#type' => 'datetime',
        '#date_timezone' => 'UTC',
      ],
    ], [
      $dateTime->getID() => $dateTime->getArray(),
    ]);
  }
}
