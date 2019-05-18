<?php

namespace Drupal\Tests\formfactorykits\Unit\Kits\Button;

use Drupal\Tests\formfactorykits\Unit\KitTestBase;

/**
 * @coversDefaultClass \Drupal\formfactorykits\Kits\Button\ButtonKit
 * @group kit
 */
class ButtonKitTest extends KitTestBase {
  public function testDefaults() {
    $button = $this->k->button();
    $this->assertArrayEquals([
      'button' => [
        '#type' => 'button',
      ],
    ], [
      $button->getID() => $button->getArray(),
    ]);
  }

  public function testCustomID() {
    $button = $this->k->button('foo');
    $this->assertEquals('foo', $button->getID());
  }

  public function testValue() {
    $button = $this->k->button()
      ->setValue('Foo');
    $this->assertArrayEquals([
      'button' => [
        '#type' => 'button',
        '#value' => 'Foo',
      ],
    ], [
      $button->getID() => $button->getArray(),
    ]);
  }

  public function testButtonType() {
    $button = $this->k->button()
      ->setButtonType('primary');
    $this->assertArrayEquals([
      'button' => [
        '#type' => 'button',
        '#button_type' => 'primary',
      ],
    ], [
      $button->getID() => $button->getArray(),
    ]);
  }

  public function testAjaxFunctionCallback() {
    $button = $this->k->button()
      ->setAjaxCallback('bar');
    $this->assertArrayEquals([
      'button' => [
        '#type' => 'button',
        '#ajax' => [
          'callback' => 'bar',
        ],
      ],
    ], [
      $button->getID() => $button->getArray(),
    ]);
  }

  public function testAjaxMethodCallback() {
    $button = $this->k->button()
      ->setAjaxCallback([self::class, 'bar']);
    $this->assertArrayEquals([
      'button' => [
        '#type' => 'button',
        '#ajax' => [
          'callback' => [self::class, 'bar'],
        ],
      ],
    ], [
      $button->getID() => $button->getArray(),
    ]);
  }
}
