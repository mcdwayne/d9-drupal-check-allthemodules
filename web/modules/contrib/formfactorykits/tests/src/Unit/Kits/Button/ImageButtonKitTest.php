<?php

namespace Drupal\Tests\formfactorykits\Unit\Kits\Button;

use Drupal\Tests\formfactorykits\Unit\KitTestBase;

/**
 * @coversDefaultClass \Drupal\formfactorykits\Kits\Button\ImageButtonKit
 * @group kit
 */
class ImageButtonKitTest extends KitTestBase {
  public function testDefaults() {
    $imageButton = $this->k->imageButton();
    $this->assertArrayEquals([
      'image_button' => [
        '#type' => 'image_button',
      ],
    ], [
      $imageButton->getID() => $imageButton->getArray(),
    ]);
  }

  public function testCustomID() {
    $imageButton = $this->k->imageButton('foo');
    $this->assertEquals('foo', $imageButton->getID());
  }

  public function testValue() {
    $imageButton = $this->k->imageButton()
      ->setValue('Foo');
    $this->assertArrayEquals([
      'image_button' => [
        '#type' => 'image_button',
        '#value' => 'Foo',
      ],
    ], [
      $imageButton->getID() => $imageButton->getArray(),
    ]);
  }

  public function testButtonType() {
    $imageButton = $this->k->imageButton()
      ->setButtonType('primary');
    $this->assertArrayEquals([
      'image_button' => [
        '#type' => 'image_button',
        '#button_type' => 'primary',
      ],
    ], [
      $imageButton->getID() => $imageButton->getArray(),
    ]);
  }

  public function testAjaxFunctionCallback() {
    $imageButton = $this->k->imageButton()
      ->setAjaxCallback('bar');
    $this->assertArrayEquals([
      'image_button' => [
        '#type' => 'image_button',
        '#ajax' => [
          'callback' => 'bar',
        ],
      ],
    ], [
      $imageButton->getID() => $imageButton->getArray(),
    ]);
  }

  public function testAjaxMethodCallback() {
    $imageButton = $this->k->imageButton()
      ->setAjaxCallback([self::class, 'bar']);
    $this->assertArrayEquals([
      'image_button' => [
        '#type' => 'image_button',
        '#ajax' => [
          'callback' => [self::class, 'bar'],
        ],
      ],
    ], [
      $imageButton->getID() => $imageButton->getArray(),
    ]);
  }

  public function testSource() {
    $imageButton = $this->k->imageButton()
      ->setSource('source.gif');
    $this->assertArrayEquals([
      'image_button' => [
        '#type' => 'image_button',
        '#src' => 'source.gif',
      ],
    ], [
      $imageButton->getID() => $imageButton->getArray(),
    ]);
  }

  public function testAlternativeText() {
    $imageButton = $this->k->imageButton()
      ->setAlternativeText('Foo');
    $this->assertArrayEquals([
      'image_button' => [
        '#type' => 'image_button',
        '#attributes' => [
          'alt' => 'Foo'
        ],
      ],
    ], [
      $imageButton->getID() => $imageButton->getArray(),
    ]);
  }
}
