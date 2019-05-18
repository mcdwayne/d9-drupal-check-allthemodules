<?php

namespace Drupal\Tests\formfactorykits\Unit\Kits\Field\Text;

use Drupal\Tests\formfactorykits\Unit\Kits\Traits\StringTranslationTrait;
use Drupal\Tests\formfactorykits\Unit\KitTestBase;

/**
 * @coversDefaultClass \Drupal\formfactorykits\Kits\Field\Text\UrlKit
 * @group kit
 */
class UrlKitTest extends KitTestBase {
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
    $url = $this->k->url();
    $this->assertArrayEquals([
      'url' => [
        '#type' => 'url',
        '#title' => 'URL',
      ],
    ], [
      $url->getID() => $url->getArray(),
    ]);
  }

  public function testCustomID() {
    $url = $this->k->url('foo');
    $this->assertEquals('foo', $url->getID());
  }

  public function testDefaultValue() {
    $url = $this->k->url()
      ->setDefaultValue('www.example.com');
    $this->assertArrayEquals([
      'url' => [
        '#type' => 'url',
        '#title' => 'URL',
        '#default_value' => 'www.example.com',
      ],
    ], [
      $url->getID() => $url->getArray(),
    ]);
  }

  public function testSize() {
    $url = $this->k->url()
      ->setSize(10);
    $this->assertArrayEquals([
      'url' => [
        '#type' => 'url',
        '#title' => 'URL',
        '#size' => 10,
      ],
    ], [
      $url->getID() => $url->getArray(),
    ]);
  }

  public function testPattern() {
    $url = $this->k->url()
      ->setPattern('*.example.com');
    $this->assertArrayEquals([
      'url' => [
        '#type' => 'url',
        '#title' => 'URL',
        '#pattern' => '*.example.com',
      ],
    ], [
      $url->getID() => $url->getArray(),
    ]);
  }
}
