<?php

namespace Drupal\Tests\formfactorykits\Unit\Kits\Field\Text;

use Drupal\Tests\formfactorykits\Unit\KitTestBase;

/**
 * @coversDefaultClass \Drupal\formfactorykits\Kits\Field\Text\TextFieldKit
 * @group kit
 */
class TextFieldKitTest extends KitTestBase {
  public function testDefaults() {
    $textField = $this->k->textField();
    $this->assertArrayEquals([
      'textfield' => [
        '#type' => 'textfield',
      ],
    ], [
      $textField->getID() => $textField->getArray(),
    ]);
  }

  public function testCustomID() {
    $textField = $this->k->textField('foo');
    $this->assertEquals('foo', $textField->getID());
  }

  public function testValue() {
    $textField = $this->k->textField()
      ->setValue('Foo');
    $this->assertArrayEquals([
      'textfield' => [
        '#type' => 'textfield',
        '#value' => 'Foo',
      ],
    ], [
      $textField->getID() => $textField->getArray(),
    ]);
  }

  public function testDescription() {
    $textField = $this->k->textField()
      ->setDescription('Foo');
    $this->assertArrayEquals([
      'textfield' => [
        '#type' => 'textfield',
        '#description' => 'Foo',
      ],
    ], [
      $textField->getID() => $textField->getArray(),
    ]);
  }

  public function testMaxLength() {
    $textField = $this->k->textField()
      ->setMaxLength(10);
    $this->assertArrayEquals([
      'textfield' => [
        '#type' => 'textfield',
        '#maxlength' => 10,
      ],
    ], [
      $textField->getID() => $textField->getArray(),
    ]);
  }

  public function testSize() {
    $textField = $this->k->textField()
      ->setSize(10);
    $this->assertArrayEquals([
      'textfield' => [
        '#type' => 'textfield',
        '#size' => 10,
      ],
    ], [
      $textField->getID() => $textField->getArray(),
    ]);
  }

  public function testAutoCompleteRoute() {
    $textField = $this->k->textField()
      ->setAutoCompleteRoute('foo');
    $this->assertArrayEquals([
      'textfield' => [
        '#type' => 'textfield',
        '#autocomplete_route_name' => 'foo',
      ],
    ], [
      $textField->getID() => $textField->getArray(),
    ]);
  }

  public function testAutoCompleteRouteParameters() {
    $textField = $this->k->textField()
      ->setAutoCompleteRouteParameters(['foo', 'bar']);
    $this->assertArrayEquals([
      'textfield' => [
        '#type' => 'textfield',
        '#autocomplete_route_parameters' => ['foo', 'bar'],
      ],
    ], [
      $textField->getID() => $textField->getArray(),
    ]);
  }
}
