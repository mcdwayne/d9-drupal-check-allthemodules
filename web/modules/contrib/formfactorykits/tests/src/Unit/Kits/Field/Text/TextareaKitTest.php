<?php

namespace Drupal\Tests\formfactorykits\Unit\Kits\Field\Text;

use Drupal\Tests\formfactorykits\Unit\KitTestBase;

/**
 * @coversDefaultClass \Drupal\formfactorykits\Kits\Field\Text\TextAreaKit
 * @group kit
 */
class TextAreaKitTest extends KitTestBase {
  public function testDefaults() {
    $textarea = $this->k->textArea();
    $this->assertArrayEquals([
      'textarea' => [
        '#type' => 'textarea',
      ],
    ], [
      $textarea->getID() => $textarea->getArray(),
    ]);
  }

  public function testCustomID() {
    $textarea = $this->k->textArea('foo');
    $this->assertEquals('foo', $textarea->getID());
  }

  public function testValue() {
    $textarea = $this->k->textArea()
      ->setValue('Foo');
    $this->assertArrayEquals([
      'textarea' => [
        '#type' => 'textarea',
        '#value' => 'Foo',
      ],
    ], [
      $textarea->getID() => $textarea->getArray(),
    ]);
  }

  public function testDescription() {
    $textarea = $this->k->textArea()
      ->setDescription('Foo');
    $this->assertArrayEquals([
      'textarea' => [
        '#type' => 'textarea',
        '#description' => 'Foo',
      ],
    ], [
      $textarea->getID() => $textarea->getArray(),
    ]);
  }
}
