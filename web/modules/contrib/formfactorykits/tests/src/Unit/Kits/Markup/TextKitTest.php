<?php

namespace Drupal\Tests\formfactorykits\Unit\Kits\Markup;

use Drupal\Tests\formfactorykits\Unit\KitTestBase;

/**
 * @coversDefaultClass \Drupal\formfactorykits\Kits\Markup\TextKit
 * @group kit
 */
class TextKitTest extends KitTestBase {
  public function testDefaults() {
    $text = $this->k->text();
    $this->assertArrayEquals([
      'text' => [
        '#type' => 'markup',
      ],
    ], [
      $text->getID() => $text->getArray(),
    ]);
  }

  public function testCustomID() {
    $text = $this->k->text('foo');
    $this->assertEquals('foo', $text->getID());
  }

  public function testText() {
    $text = $this->k->text()
      ->setValue('Foo');
    $this->assertArrayEquals([
      'text' => [
        '#type' => 'markup',
        '#markup' => 'Foo',
      ],
    ], [
      $text->getID() => $text->getArray(),
    ]);
  }

  public function testHtmlEscape() {
    $text = $this->k->text()
      ->setValue('<b>Foo</b>');
    $this->assertArrayEquals([
      'text' => [
        '#type' => 'markup',
        '#markup' => 'Foo',
      ],
    ], [
      $text->getID() => $text->getArray(),
    ]);
  }
}
