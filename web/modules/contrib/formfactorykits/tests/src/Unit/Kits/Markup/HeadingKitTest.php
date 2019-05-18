<?php

namespace Drupal\Tests\formfactorykits\Unit\Kits\Markup;

use Drupal\Tests\formfactorykits\Unit\KitTestBase;

/**
 * @coversDefaultClass \Drupal\formfactorykits\Kits\Markup\HeadingKit
 * @group kit
 */
class HeadingKitTest extends KitTestBase {
  public function testDefaults() {
    $heading = $this->k->heading();
    $this->assertArrayEquals([
      'heading' => [
        '#type' => 'markup',
      ],
    ], [
      $heading->getID() => $heading->getArray(),
    ]);
  }

  public function testCustomID() {
    $heading = $this->k->heading('foo');
    $this->assertEquals('foo', $heading->getID());
  }

  public function testValue() {
    $heading = $this->k->heading()
      ->setValue('Foo');
    $this->assertArrayEquals([
      'heading' => [
        '#type' => 'markup',
        '#markup' => '<h1>Foo</h1>',
      ],
    ], [
      $heading->getID() => $heading->getArray(),
    ]);
  }

  public function testNumber() {
    $heading = $this->k->heading()
      ->setNumber(2)
      ->setValue('Foo');
    $this->assertArrayEquals([
      'heading' => [
        '#type' => 'markup',
        '#markup' => '<h2>Foo</h2>',
      ],
    ], [
      $heading->getID() => $heading->getArray(),
    ]);
  }
}
