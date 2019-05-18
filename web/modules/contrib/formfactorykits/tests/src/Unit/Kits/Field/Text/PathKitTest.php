<?php

namespace Drupal\Tests\formfactorykits\Unit\Kits\Field\Text;

use Drupal\Core\Render\Element\PathElement;
use Drupal\Tests\formfactorykits\Unit\Kits\Traits\StringTranslationTrait;
use Drupal\Tests\formfactorykits\Unit\KitTestBase;

/**
 * @coversDefaultClass \Drupal\formfactorykits\Kits\Field\Text\PathKit
 * @group kit
 */
class PathKitTest extends KitTestBase {
  use StringTranslationTrait;

  /**
   * @inheritdoc
   */
  public function getServices() {
    return [
      'string_translation' => $this->getTranslationManager(),
    ];
  }

  public function testDefaults() {
    $path = $this->k->path();
    $this->assertArrayEquals([
      'path' => [
        '#type' => 'path',
        '#title' => 'Path',
      ],
    ], [
      $path->getID() => $path->getArray(),
    ]);
  }

  public function testCustomID() {
    $path = $this->k->path('foo');
    $this->assertEquals('foo', $path->getID());
  }

  public function testTitle() {
    $path = $this->k->path()
      ->setTitle('Foo');
    $this->assertArrayEquals([
      'path' => [
        '#type' => 'path',
        '#title' => 'Foo',
      ],
    ], [
      $path->getID() => $path->getArray(),
    ]);
  }

  public function testDescription() {
    $path = $this->k->path()
      ->setDescription('foo');
    $this->assertArrayEquals([
      'path' => [
        '#type' => 'path',
        '#title' => 'Path',
        '#description' => 'foo',
      ],
    ], [
      $path->getID() => $path->getArray(),
    ]);
  }

  public function testDefaultValue() {
    $path = $this->k->path()
      ->setDefaultValue('foo');
    $this->assertArrayEquals([
      'path' => [
        '#type' => 'path',
        '#title' => 'Path',
        '#default_value' => 'foo',
      ],
    ], [
      $path->getID() => $path->getArray(),
    ]);
  }

  public function testConvertPath() {
    $path = $this->k->path()
      ->setConversion(PathElement::CONVERT_URL);
    $this->assertArrayEquals([
      'path' => [
        '#type' => 'path',
        '#title' => 'Path',
        '#convert_path' => PathElement::CONVERT_URL,
      ],
    ], [
      $path->getID() => $path->getArray(),
    ]);
  }
}
