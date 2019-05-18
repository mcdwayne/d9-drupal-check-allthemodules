<?php

namespace Drupal\Tests\formfactorykits\Unit\Kits\Markup;

use Drupal\Tests\formfactorykits\Unit\KitTestBase;

/**
 * @coversDefaultClass \Drupal\formfactorykits\Kits\Markup\TextKit
 * @group kit
 */
class MarkupKitTest extends KitTestBase {
  public function testDefaults() {
    $markup = $this->k->markup();
    $this->assertArrayEquals([
      'markup' => [
        '#type' => 'markup',
      ],
    ], [
      $markup->getID() => $markup->getArray(),
    ]);
  }

  public function testCustomID() {
    $markup = $this->k->markup('foo');
    $this->assertEquals('foo', $markup->getID());
  }

  public function testMarkup() {
    $markup = $this->k->markup()
      ->setMarkup('Foo');
    $this->assertArrayEquals([
      'markup' => [
        '#type' => 'markup',
        '#markup' => 'Foo',
      ],
    ], [
      $markup->getID() => $markup->getArray(),
    ]);
  }
}
